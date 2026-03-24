<?php

namespace App\Http\Controllers;

use App\Models\SecurityWarning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityWarningController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canViewSecurityWarnings(), 403, 'Insufficient role.');

        $warningQuery = SecurityWarning::query()
            ->when($user->role === 'manager', fn ($query) => $query->where('location_id', $user->location_id));

        $warnings = (clone $warningQuery)
            ->with(['user', 'location'])
            ->latest()
            ->paginate(30);

        $summary = [
            'total' => (clone $warningQuery)->count(),
            'open' => (clone $warningQuery)->whereNull('resolved_at')->count(),
            'resolved' => (clone $warningQuery)->whereNotNull('resolved_at')->count(),
            'locations' => (clone $warningQuery)->distinct('location_id')->count('location_id'),
        ];

        return view('reports.security-warnings', compact('warnings', 'summary'));
    }

    public function resolve(Request $request, SecurityWarning $warning): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canViewSecurityWarnings(), 403, 'Insufficient role.');

        if ($user->role === 'manager' && (int) $warning->location_id !== (int) $user->location_id) {
            abort(403, 'You cannot resolve warnings outside your location.');
        }

        $warning->update(['resolved_at' => now()]);

        return back()->with('status', 'Warning marked as resolved.');
    }
}
