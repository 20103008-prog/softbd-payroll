<?php

namespace App\Services\Roster;

use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * ShiftResolver
 * 
 * =========================================================================
 * [EXAM SUMMARY & PURPOSE]:
 * This service class determines assigned work shifts for employees on given dates
 * and evaluates late entry status and half-day penalties based on shift timing
 * or global attendance policy fallback.
 * =========================================================================
 */
class ShiftResolver
{
    /**
     * Fetches active shift assignment for an employee on a specific date.
     * Checks if effective_from <= date AND (effective_to >= date OR NULL).
     *
     * @param Employee $employee Target employee model
     * @param Carbon|string $date Target evaluation date
     * @return EmployeeShiftAssignment|null
     */
    public function assignmentFor(Employee $employee, Carbon|string $date): ?EmployeeShiftAssignment
    {
        // Format date string to YYYY-MM-DD
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        // Query active assignment covering date $d ordered by latest effective_from
        return EmployeeShiftAssignment::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $d)
            ->where(function ($q) use ($d) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $d);
            })
            ->orderByDesc('effective_from') // Most recent assignment takes priority
            ->first();
    }

    /**
     * Resolves assigned Shift model for an employee on a specific date.
     *
     * @param Employee $employee
     * @param Carbon|string $date
     * @return Shift|null
     */
    public function shiftFor(Employee $employee, Carbon|string $date): ?Shift
    {
        return $this->assignmentFor($employee, $date)?->shift;
    }

    /**
     * Preloads shift assignments covering a date range for multiple employees,
     * grouped by employee_id for optimal batch query performance.
     *
     * @param  Collection<int, int>|array<int, int>  $employeeIds
     * @param  Carbon $start
     * @param  Carbon $end
     * @return Collection<int, Collection<int, EmployeeShiftAssignment>>
     */
    public function assignmentsForPeriod($employeeIds, Carbon $start, Carbon $end): Collection
    {
        // Sanitize and filter array of employee IDs
        $ids = collect($employeeIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        // Fetch shift assignments for all employee IDs within the period in a single query
        return EmployeeShiftAssignment::query()
            ->with('shift')
            ->whereIn('employee_id', $ids)
            ->where('effective_from', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $start->toDateString());
            })
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('employee_id'); // Group by employee_id for memory lookup
    }

    /**
     * Finds matching shift from preloaded assignments map for an employee on a date (O(1) memory lookup).
     *
     * @param Collection $byEmployee Preloaded assignment collection grouped by employee_id
     * @param int $employeeId
     * @param Carbon|string $date
     * @return Shift|null
     */
    public function shiftFromMap(Collection $byEmployee, int $employeeId, Carbon|string $date): ?Shift
    {
        $d = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $list = $byEmployee->get($employeeId, collect());

        foreach ($list as $assignment) {
            /** @var EmployeeShiftAssignment $assignment */
            if ($assignment->covers($d)) { // Check if assignment date range covers $d
                return $assignment->shift;
            }
        }

        return null;
    }

    /**
     * Evaluates late minutes and half-day status for an employee check-in log.
     * First checks assigned Shift grace minutes; if no shift assigned, falls back to global AttendancePolicy.
     *
     * @param Employee|null $employee
     * @param string|null $workDate Work date string (YYYY-MM-DD)
     * @param string|null $checkIn Check-in timestamp string (YYYY-MM-DD HH:MM:SS)
     * @param AttendancePolicy|null $policy Optional AttendancePolicy model override
     * @param Shift|null $shift Optional Shift model override
     * @return array{late_minutes:int, half_day:bool, shift:?Shift, source:string}
     */
    public function lateInfo(
        ?Employee $employee,
        ?string $workDate,
        ?string $checkIn,
        ?AttendancePolicy $policy = null,
        ?Shift $shift = null
    ): array {
        // Return zero late minutes if work date or check-in timestamp is missing
        if (! $workDate || ! $checkIn) {
            return ['late_minutes' => 0, 'half_day' => false, 'shift' => null, 'source' => 'none'];
        }

        // Load active global policy if not provided
        $policy ??= AttendancePolicy::current();

        // Resolve employee shift if not explicitly provided
        if ($shift === null && $employee) {
            $shift = $this->shiftFor($employee, $workDate);
        }

        /* -------------------------------------------------------------
         * OPTION 1: Evaluate Late Status Against Assigned Shift Timing
         * ------------------------------------------------------------- */
        if ($shift) {
            $start = $shift->startAt($workDate); // Shift start time on work date
            $cutoff = $start->copy()->addMinutes($shift->grace_minutes); // Grace time cutoff limit
            $arrived = Carbon::parse($checkIn); // Employee actual arrival time

            // Not late if arrival time is within grace cutoff
            if ($arrived->lessThanOrEqualTo($cutoff)) {
                return ['late_minutes' => 0, 'half_day' => false, 'shift' => $shift, 'source' => 'shift'];
            }

            // Calculate late minutes difference between arrival time and shift start time
            $lateMinutes = $start->diffInMinutes($arrived);
            // Flag half-day if late minutes exceed global policy threshold
            $halfDay = $policy->half_day_after_minutes !== null
                && $lateMinutes > $policy->half_day_after_minutes;

            return [
                'late_minutes' => $lateMinutes,
                'half_day' => $halfDay,
                'shift' => $shift,
                'source' => 'shift',
            ];
        }

        /* -------------------------------------------------------------
         * OPTION 2: Fallback Evaluation Against Global AttendancePolicy Timing
         * ------------------------------------------------------------- */
        $start = Carbon::parse($workDate.' '.$policy->work_start_time); // Policy standard start time
        $cutoff = $start->copy()->addMinutes($policy->grace_minutes); // Policy grace time cutoff
        $arrived = Carbon::parse($checkIn);

        // Not late if arrival time is within policy grace cutoff
        if ($arrived->lessThanOrEqualTo($cutoff)) {
            return ['late_minutes' => 0, 'half_day' => false, 'shift' => null, 'source' => 'policy'];
        }

        // Calculate late minutes and half-day status against policy timing
        $lateMinutes = $start->diffInMinutes($arrived);
        $halfDay = $policy->half_day_after_minutes !== null
            && $lateMinutes > $policy->half_day_after_minutes;

        return [
            'late_minutes' => $lateMinutes,
            'half_day' => $halfDay,
            'shift' => null,
            'source' => 'policy',
        ];
    }
}
