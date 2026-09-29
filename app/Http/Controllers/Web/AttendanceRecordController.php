<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceRecordController extends Controller
{
    /* ========================================================================= */
    /* 1. EDIT DAY METHOD: DISPLAY DAILY ATTENDANCE PUNCH LOGS PAGE */
    /* ========================================================================= */
    public function editDay(string $code, string $date): View
    {
        // Parse date string into Y-m-d format
        $day = Carbon::parse($date)->toDateString();

        // Retrieve employee model by biometric employee code
        $employee = Employee::query()->with('user')->where('employee_code', $code)->first();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Check user permission to access this employee profile
        if ($employee) {
            abort_unless($user->canAccessEmployee($employee), 403);

            // Role-based editing restrictions
            $authRole = $user->role;
            $isSelf = ($user->employee?->employee_code === $code);
            $targetRole = $employee->user?->role;

            if ($isSelf) {
                abort(403, 'You cannot edit your own attendance.');
            }
            if ($authRole === \App\Models\User::ROLE_HR && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
                abort(403, 'HR Manager cannot edit Admin attendance.');
            }
            if ($authRole === \App\Models\User::ROLE_MANAGER && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_HR], true)) {
                abort(403, 'Manager cannot edit this attendance record.');
            }
        } elseif ($user->isTeamScoped()) {
            abort(403);
        }

        // Fetch all attendance punches for this employee on the specified date
        $punches = AttendanceLog::query()
            ->with('device')
            ->where('employee_code', $code)
            ->whereDate('punched_at', $day)
            ->orderBy('punched_at')
            ->get();

        // Render reports/day view with employee and punches data
        return view('reports.day', [
            'code' => $code,
            'date' => $day,
            'employee' => $employee,
            'punches' => $punches,
        ]);
    }

    /* ========================================================================= */
    /* 2. STORE PUNCH METHOD: MANUALLY ADD A NEW ATTENDANCE PUNCH */
    /* ========================================================================= */
    public function storePunch(Request $request, AuditLogger $audit): RedirectResponse
    {
        // Validate request inputs (employee_code, punched_at timestamp, status: in/out)
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:255'],
            'punched_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:32'],
        ]);

        $employee = Employee::query()->where('employee_code', $validated['employee_code'])->first();

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Security check: verify logged-in user can manage this employee
        if ($employee) {
            abort_unless($user->canAccessEmployee($employee), 403);
        } elseif ($user->isTeamScoped()) {
            abort(403);
        }

        // Create new AttendanceLog record with Manual Device placeholder
        try {
            $log = AttendanceLog::query()->create([
                'employee_code' => $validated['employee_code'],
                'device_id' => $this->manualDevice()->id,
                'punched_at' => Carbon::parse($validated['punched_at']),
                'status' => $validated['status'],
            ]);
        } catch (QueryException $e) {
            return back()->with('warning', 'A punch with the same time and status already exists for this employee.');
        }

        // Log audit event for newly created manual punch
        $audit->log('punch.created', "Added punch for {$validated['employee_code']} at {$validated['punched_at']}", $log, $validated);

        return back()->with('success', 'Punch added.');
    }

    /* ========================================================================= */
    /* 3. UPDATE PUNCH METHOD: EDIT TIMESTAMP OR STATUS OF AN EXISTING PUNCH */
    /* ========================================================================= */
    public function updatePunch(Request $request, AttendanceLog $attendanceLog, AuditLogger $audit): RedirectResponse
    {
        $employee = Employee::query()->where('employee_code', $attendanceLog->employee_code)->first();

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Permission check: ensure user can edit this employee's punch log
        if ($employee) {
            abort_unless($user->canAccessEmployee($employee), 403);
        } elseif ($user->isTeamScoped()) {
            abort(403);
        }

        // Validate updated time and status inputs
        $validated = $request->validate([
            'punched_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:32'],
        ]);

        // Capture state before modification for audit log comparison
        $before = $attendanceLog->only(['punched_at', 'status', 'employee_code']);

        // Update database record and catch duplicate entry errors
        try {
            $attendanceLog->update([
                'punched_at' => Carbon::parse($validated['punched_at']),
                'status' => $validated['status'],
            ]);
        } catch (QueryException $e) {
            return back()->with('warning', 'Another punch with the same time and status already exists.');
        }

        // Log audit trail for updated punch record
        $audit->log('punch.updated', "Updated punch #{$attendanceLog->id} for {$attendanceLog->employee_code}", $attendanceLog, [
            'before' => $before,
            'after' => $validated,
        ]);

        return back()->with('success', 'Punch updated.');
    }

    /* ========================================================================= */
    /* 4. DESTROY PUNCH METHOD: DELETE AN ATTENDANCE PUNCH RECORD */
    /* ========================================================================= */
    public function destroyPunch(AttendanceLog $attendanceLog, AuditLogger $audit): RedirectResponse
    {
        $employee = Employee::query()->where('employee_code', $attendanceLog->employee_code)->first();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Security check: verify user authority to delete attendance log
        if ($employee) {
            abort_unless($user->canAccessEmployee($employee), 403);
        } elseif ($user->isTeamScoped()) {
            abort(403);
        }

        $summary = "Deleted punch #{$attendanceLog->id} for {$attendanceLog->employee_code}";
        $props = $attendanceLog->only(['employee_code', 'punched_at', 'status']);

        // Remove record from database
        $attendanceLog->delete();

        // Log audit event for deleted punch
        $audit->log('punch.deleted', $summary, null, $props);

        return back()->with('success', 'Punch deleted.');
    }

    /* ========================================================================= */
    /* 5. HELPER FUNCTION: RETRIEVE OR CREATE PLACEHOLDER MANUAL DEVICE */
    /* ========================================================================= */
    private function manualDevice(): Device
    {
        return Device::query()->firstOrCreate(
            ['ip_address' => '0.0.0.0', 'port' => 0],
            ['name' => 'Manual Entry', 'is_active' => false]
        );
    }
}
