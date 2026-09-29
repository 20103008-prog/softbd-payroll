<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Collection;

/**
 * LeaveBalanceService
 * 
 * Provides business logic for calculating employee leave quotas, approved used days, and remaining balances.
 */
class LeaveBalanceService
{
    /**
     * Calculate approved leave days already used by an employee for one leave type in a year,
     * optionally excluding a specific request (useful when approving/editing it).
     *
     * @param Employee $employee
     * @param LeaveType $type
     * @param int $year
     * @param int|null $excludeId
     * @return int
     */
    public function usedApprovedDays(Employee $employee, LeaveType $type, int $year, ?int $excludeId = null): int
    {
        // Query sum of approved days for employee and leave type in target year
        return (int) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $type->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->sum('days');
    }

    /**
     * Calculate leave balance per leave type for one employee in a given year.
     *
     * @param Employee $employee
     * @param int $year
     * @return array<int, array{type: LeaveType, quota: int, used: int, remaining: int}>
     */
    public function forEmployee(Employee $employee, int $year): array
    {
        // Aggregate total approved leave days grouped by leave_type_id
        $used = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, SUM(days) as used')
            ->groupBy('leave_type_id')
            ->pluck('used', 'leave_type_id');

        // Map over all active leave types to return quota, used days, and remaining balance
        return LeaveType::orderBy('name')->get()
            ->map(function (LeaveType $type) use ($used) {
                $u = (int) ($used[$type->id] ?? 0);

                return [
                    'type' => $type,
                    'quota' => (int) $type->annual_quota,
                    'used' => $u,
                    'remaining' => (int) $type->annual_quota - $u,
                ];
            })
            ->all();
    }

    /**
     * Build an approved leave days lookup map keyed by [employee_id][leave_type_id] for a year.
     *
     * @param  Collection<int, int>  $employeeIds
     * @param  int $year
     * @return array<int, array<int, int>>
     */
    public function usedMap(Collection $employeeIds, int $year): array
    {
        // Fetch aggregated used days for given employee collection
        $rows = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->whereIn('employee_id', $employeeIds)
            ->selectRaw('employee_id, leave_type_id, SUM(days) as used')
            ->groupBy('employee_id', 'leave_type_id')
            ->get();

        // Build 2D array map
        $map = [];
        foreach ($rows as $row) {
            $map[$row->employee_id][$row->leave_type_id] = (int) $row->used;
        }

        return $map;
    }
}
