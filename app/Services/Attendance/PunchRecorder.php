<?php

namespace App\Services\Attendance;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\QueryException;

class PunchRecorder
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function lastToday(Employee $employee): ?AttendanceLog
    {
        return AttendanceLog::query()
            ->where('employee_code', $employee->employee_code)
            ->whereDate('punched_at', now()->toDateString())
            ->orderByDesc('punched_at')
            ->first();
    }

    /**
     * Device-like toggle: first punch of the day (or after out) is in, otherwise out.
     *
     * @throws QueryException duplicate unique (same second + status)
     */
    public function record(Employee $employee): AttendanceLog
    {
        // IP Restriction: Only allow official IP (127.0.0.1 / ::1)
        $allowedIp = '127.0.0.1';
        $clientIp = request()->ip();

        if (! in_array($clientIp, [$allowedIp, '::1'], true)) {
            abort(403, "Access Denied: Attendance punching is restricted to official IP ({$allowedIp}). Your IP: {$clientIp}");
        }

        $last = $this->lastToday($employee);
        $status = ($last === null || $last->status === 'out') ? 'in' : 'out';
        $punchedAt = now();

        $log = AttendanceLog::query()->create([
            'employee_code' => $employee->employee_code,
            'device_id' => $this->manualDevice()->id,
            'punched_at' => $punchedAt,
            'status' => $status,
        ]);

        $label = $status === 'in' ? 'Check In' : 'Check Out';
        $this->audit->log(
            'punch.created',
            "{$label} for {$employee->employee_code} at {$punchedAt->format('Y-m-d H:i:s')}",
            $log,
            [
                'employee_code' => $employee->employee_code,
                'punched_at' => $punchedAt->toDateTimeString(),
                'status' => $status,
            ]
        );

        return $log;
    }

    public function manualDevice(): Device
    {
        return Device::query()->firstOrCreate(
            ['ip_address' => '127.0.0.1', 'port' => 8000],
            ['name' => 'Manual Entry', 'is_active' => true]
        );
    }
}
