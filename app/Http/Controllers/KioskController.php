<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Schedule;
use App\Models\TimePunch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KioskController extends Controller
{
    public function index(Request $request)
    {
        $kiosk = $request->attributes->get('kiosk');

        return view('kiosk.index', [
            'clockInRoute' => route('kiosk.clock-in'),
            'clockOutRoute' => route('kiosk.clock-out'),
            'kiosk' => $kiosk,
            'location' => $kiosk->location,
        ]);
    }

    public function cameraIndex(Request $request)
    {
        $kiosk = $request->attributes->get('kiosk');

        return view('kiosk.camera', [
            'clockInRoute' => route('kiosk.camera.clock-in'),
            'clockOutRoute' => route('kiosk.camera.clock-out'),
            'fallbackClockInRoute' => route('kiosk.clock-in'),
            'fallbackClockOutRoute' => route('kiosk.clock-out'),
            'kiosk' => $kiosk,
            'location' => $kiosk->location,
        ]);
    }

    public function identify(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => ['required', 'digits_between:4,12'],
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $kiosk = $request->attributes->get('kiosk');
        $user = $this->resolveUserByCredentials($request->string('staff_id')->toString(), $request->input('pin'));

        if (! $user) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
        }

        $hasOpenPunch = TimePunch::where('user_id', $user->id)
            ->whereNull('clock_out_at')
            ->exists();
        $matchedSchedule = $user->requires_schedule_for_clock
            ? $this->findMatchedSchedule($user->id, $kiosk->location_id, now())
            : null;
        $clockInBlockReason = (! $hasOpenPunch && $user->requires_schedule_for_clock)
            ? $this->clockInBlockReason($matchedSchedule, now())
            : null;
        $scheduleHint = ! $user->requires_schedule_for_clock
            ? 'Schedule check is not required for this staff.'
            : ($matchedSchedule
                ? sprintf(
                    'Scheduled %s-%s',
                    $matchedSchedule->starts_at->format('h:i A'),
                    $matchedSchedule->ends_at->format('h:i A')
                )
                : 'No approved schedule matched for now.');

        return response()->json([
            'name' => $user->name,
            'has_open_punch' => $hasOpenPunch,
            'next_action' => $hasOpenPunch ? 'clock-out' : 'clock-in',
            'schedule_hint' => $scheduleHint,
            'schedule_matched' => (bool) $matchedSchedule,
            'clock_in_allowed' => $hasOpenPunch ? false : $clockInBlockReason === null,
            'clock_in_block_reason' => $clockInBlockReason,
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        return $this->performClockIn($request, false);
    }

    public function clockOut(Request $request): JsonResponse
    {
        return $this->performClockOut($request, false);
    }

    public function cameraClockIn(Request $request): JsonResponse
    {
        return $this->performClockIn($request, true);
    }

    public function cameraClockOut(Request $request): JsonResponse
    {
        return $this->performClockOut($request, true);
    }

    private function performClockIn(Request $request, bool $requirePhoto): JsonResponse
    {
        $rules = [
            'staff_id' => ['required', 'digits_between:4,12'],
            'pin' => ['required', 'digits_between:4,6'],
        ];

        if ($requirePhoto) {
            $rules['photo'] = ['required', 'string'];
        }

        $request->validate($rules);

        $kiosk = $request->attributes->get('kiosk');
        $user = $this->resolveUserByCredentials($request->string('staff_id')->toString(), $request->input('pin'));

        if (! $user) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
        }

        $punch = DB::transaction(function () use ($request, $kiosk, $user, $requirePhoto) {
            $now = now();
            $open = TimePunch::where('user_id', $user->id)
                ->whereNull('clock_out_at')
                ->lockForUpdate()
                ->first();

            if ($open) {
                throw ValidationException::withMessages([
                    'pin' => 'Open punch already exists. Please clock out first.',
                ]);
            }

            $schedule = $user->requires_schedule_for_clock
                ? $this->findMatchedSchedule($user->id, $kiosk->location_id, $now)
                : null;
            if ($user->requires_schedule_for_clock) {
                $this->assertClockInAllowed($schedule, $now);
            }
            $violation = $user->requires_schedule_for_clock
                ? $this->buildClockInViolationNote($schedule, $now)
                : null;

            $punch = TimePunch::create([
                'user_id' => $user->id,
                'location_id' => $kiosk->location_id,
                'kiosk_id' => $kiosk->id,
                'schedule_id' => $schedule?->id,
                'source' => 'kiosk',
                'clock_in_at' => $now,
                'clock_in_photo_path' => $requirePhoto ? $this->storePunchPhoto($request->input('photo'), 'clock-in', $user->id, $now) : null,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'violation_note' => $violation,
            ]);

            AuditLog::create([
                'actor_user_id' => null,
                'target_user_id' => $user->id,
                'action' => 'CLOCK_IN',
                'entity_type' => TimePunch::class,
                'entity_id' => $punch->id,
                'after_data' => $punch->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $punch;
        });

        return response()->json([
            'message' => sprintf('Clock in successful at %s.', $punch->clock_in_at->format('h:i A')),
            'note' => $punch->violation_note,
            'punch_id' => $punch->id,
        ]);
    }

    private function performClockOut(Request $request, bool $requirePhoto): JsonResponse
    {
        $rules = [
            'staff_id' => ['required', 'digits_between:4,12'],
            'pin' => ['required', 'digits_between:4,6'],
        ];

        if ($requirePhoto) {
            $rules['photo'] = ['required', 'string'];
        }

        $request->validate($rules);

        $kiosk = $request->attributes->get('kiosk');
        $user = $this->resolveUserByCredentials($request->string('staff_id')->toString(), $request->input('pin'));

        if (! $user) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
        }

        $punch = DB::transaction(function () use ($request, $kiosk, $user, $requirePhoto) {
            $now = now();
            $open = TimePunch::where('user_id', $user->id)
                ->whereNull('clock_out_at')
                ->lockForUpdate()
                ->first();

            if (! $open) {
                throw ValidationException::withMessages([
                    'pin' => 'No open punch found. Please clock in first.',
                ]);
            }

            $before = $open->toArray();
            $schedule = $user->requires_schedule_for_clock ? $open->schedule : null;
            if ($user->requires_schedule_for_clock && ! $schedule) {
                $schedule = $this->findMatchedSchedule($user->id, $kiosk->location_id, $now);
            }
            $violation = $user->requires_schedule_for_clock
                ? $this->buildClockOutViolationNote($schedule, $now, $open->violation_note)
                : $open->violation_note;

            $open->update([
                'clock_out_at' => $now,
                'kiosk_id' => $kiosk->id,
                'location_id' => $kiosk->location_id,
                'schedule_id' => $schedule?->id,
                'source' => 'kiosk',
                'clock_out_photo_path' => $requirePhoto ? $this->storePunchPhoto($request->input('photo'), 'clock-out', $user->id, $now) : null,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'violation_note' => $violation,
            ]);

            AuditLog::create([
                'actor_user_id' => null,
                'target_user_id' => $user->id,
                'action' => 'CLOCK_OUT',
                'entity_type' => TimePunch::class,
                'entity_id' => $open->id,
                'before_data' => $before,
                'after_data' => $open->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $open;
        });

        return response()->json([
            'message' => sprintf('Clock out successful at %s.', $punch->clock_out_at?->format('h:i A')),
            'note' => $punch->violation_note,
            'punch_id' => $punch->id,
        ]);
    }

    private function findMatchedSchedule(int $userId, int $locationId, Carbon $now): ?Schedule
    {
        $candidates = Schedule::query()
            ->where('user_id', $userId)
            ->where('location_id', $locationId)
            ->where('status', 'approved')
            ->whereDate('shift_date', '>=', $now->copy()->subDay()->toDateString())
            ->whereDate('shift_date', '<=', $now->toDateString())
            ->orderBy('starts_at')
            ->get();

        foreach ($candidates as $candidate) {
            $windowStart = $candidate->starts_at->copy()->subHours(2);
            $windowEnd = $candidate->ends_at->copy()->addHours(4);
            if ($now->between($windowStart, $windowEnd)) {
                return $candidate;
            }
        }

        return null;
    }

    private function buildClockInViolationNote(?Schedule $schedule, Carbon $now): ?string
    {
        if (! $schedule) {
            return 'No approved schedule matched at clock in time.';
        }

        if ($now->lt($schedule->starts_at->copy()->subMinutes(15))) {
            return 'Early clock in.';
        }

        if ($now->gt($schedule->starts_at->copy()->addMinutes(10))) {
            return 'Late clock in.';
        }

        return null;
    }

    private function assertClockInAllowed(?Schedule $schedule, Carbon $now): void
    {
        $blockReason = $this->clockInBlockReason($schedule, $now);
        if ($blockReason !== null) {
            throw ValidationException::withMessages([
                'pin' => $blockReason,
            ]);
        }
    }

    private function clockInBlockReason(?Schedule $schedule, Carbon $now): ?string
    {
        if (! $schedule) {
            return 'No approved schedule matched at this time.';
        }

        $earliestAllowed = $schedule->starts_at->copy()->subMinutes(15);
        if ($now->lt($earliestAllowed)) {
            return sprintf(
                'Too early to clock in. Earliest allowed time is %s.',
                $earliestAllowed->format('h:i A')
            );
        }

        return null;
    }

    private function buildClockOutViolationNote(?Schedule $schedule, Carbon $now, ?string $currentNote): ?string
    {
        $notes = [];
        if ($currentNote) {
            $notes[] = $currentNote;
        }

        if (! $schedule) {
            $notes[] = 'No approved schedule matched at clock out time.';
            return implode(' | ', array_unique($notes));
        }

        if ($now->lt($schedule->ends_at->copy()->subMinutes(10))) {
            $notes[] = 'Early clock out.';
        } elseif ($now->gt($schedule->ends_at->copy()->addMinutes(15))) {
            $notes[] = 'Late clock out.';
        }

        if (empty($notes)) {
            return null;
        }

        return implode(' | ', array_unique($notes));
    }

    private function resolveUserByCredentials(string $staffId, string $pin): ?User
    {
        $user = User::query()
            ->where('staff_id', $staffId)
            ->where('is_active', true)
            ->where('pin_enabled', true)
            ->whereNotNull('pin_hash')
            ->first(['id', 'name', 'pin_hash', 'requires_schedule_for_clock']);

        if (! $user) {
            return null;
        }

        return Hash::check($pin, $user->pin_hash) ? $user : null;
    }

    private function storePunchPhoto(string $photoDataUrl, string $event, int $userId, Carbon $timestamp): string
    {
        if (! preg_match('/^data:image\/(?<type>jpeg|jpg|png);base64,(?<data>.+)$/', $photoDataUrl, $matches)) {
            throw ValidationException::withMessages([
                'photo' => 'Photo capture is required.',
            ]);
        }

        $binary = base64_decode($matches['data'], true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'photo' => 'Captured photo could not be processed.',
            ]);
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'photo' => 'Captured photo is too large.',
            ]);
        }

        $extension = $matches['type'] === 'jpeg' ? 'jpg' : $matches['type'];
        $path = sprintf(
            'kiosk-punches/%s/%s/%s-%s.%s',
            $timestamp->format('Y/m/d'),
            $userId,
            $event,
            Str::uuid()->toString(),
            $extension
        );

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
