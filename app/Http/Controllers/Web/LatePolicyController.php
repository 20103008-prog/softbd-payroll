<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendancePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LatePolicyController extends Controller
{
    /**
     * Show the form for editing attendance late arrival policies and grace periods.
     */
    public function edit(): View
    {
        return view('settings.late-policy', [
            'policy' => AttendancePolicy::current(),
        ]);
    }

    /**
     * Update the system-wide attendance late policy rules in storage.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_start_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:600'],
            'half_day_after_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        $policy = AttendancePolicy::current();
        $policy->update([
            'work_start_time' => $validated['work_start_time'].':00',
            'grace_minutes' => $validated['grace_minutes'],
            'half_day_after_minutes' => $validated['half_day_after_minutes'] ?? null,
        ]);

        return redirect()->route('settings.late-policy')->with('success', 'Late policy updated.');
    }
}
