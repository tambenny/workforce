<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $locations = Location::query()
            ->withCount(['users', 'kiosks', 'schedules'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('allowed_ip', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('locations.index', compact('locations', 'search'));
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:locations,name'],
            'allowed_ip' => ['nullable', 'ip'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('location-logos', 'public');
        }

        Location::create([
            'name' => $data['name'],
            'allowed_ip' => $data['allowed_ip'] ?? null,
            'logo_path' => $logoPath,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('locations.index')->with('status', 'Location created.');
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:locations,name,' . $location->id],
            'allowed_ip' => ['nullable', 'ip'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $logoPath = $location->logo_path;
        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        if ($removeLogo && $logoPath) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('location-logos', 'public');
        }

        $location->update([
            'name' => $data['name'],
            'allowed_ip' => $data['allowed_ip'] ?? null,
            'logo_path' => $logoPath,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('locations.index')->with('status', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $links = [
            'users' => $location->users()->count(),
            'kiosks' => $location->kiosks()->count(),
            'punches' => $location->timePunches()->count(),
            'schedules' => $location->schedules()->count(),
            'warnings' => $location->securityWarnings()->count(),
        ];

        if (array_sum($links) > 0) {
            return back()->withErrors([
                'location' => 'Cannot delete location with linked records (users/kiosks/punches/schedules/warnings). Deactivate instead.',
            ]);
        }

        $location->delete();

        return redirect()->route('locations.index')->with('status', 'Location deleted.');
    }

    public function rotateKioskToken(Location $location): RedirectResponse
    {
        $plainToken = Str::random(48);

        $kiosk = Kiosk::query()
            ->where('location_id', $location->id)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if (! $kiosk) {
            $kiosk = Kiosk::create([
                'name' => $location->name . ' Front Desk Kiosk',
                'location_id' => $location->id,
                'kiosk_token_hash' => hash('sha256', $plainToken),
                'is_active' => true,
            ]);
        } else {
            $kiosk->update([
                'kiosk_token_hash' => hash('sha256', $plainToken),
                'is_active' => true,
            ]);
        }

        return redirect()
            ->route('locations.index')
            ->with('status', "Kiosk token rotated for {$location->name}.")
            ->with('kiosk_token_plain', $plainToken)
            ->with('kiosk_name', $kiosk->name)
            ->with('kiosk_url', route('kiosk.home', ['token' => $plainToken]));
    }
}
