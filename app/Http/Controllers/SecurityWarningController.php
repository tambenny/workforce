<?php

namespace App\Http\Controllers;

use App\Models\SecurityWarning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SecurityWarningController extends Controller
{
    public function index(): View
    {
        $warnings = SecurityWarning::with(['user', 'location'])
            ->latest()
            ->paginate(30);

        return view('reports.security-warnings', compact('warnings'));
    }

    public function resolve(SecurityWarning $warning): RedirectResponse
    {
        $warning->update(['resolved_at' => now()]);

        return back()->with('status', 'Warning marked as resolved.');
    }
}
