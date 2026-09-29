<?php

namespace App\Services\Reports;

use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Roster\ShiftResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * PeriodSummaryService
 * 
 * =========================================================================
 * [EXAM SUMMARY & PURPOSE]:
 * This service class generates data for the Attendance Summary Reports.
 * It processes 4 specific report tabs within a given date range:
 * 1. Late      - Calculates late arrival days for each employee.
 * 2. Absence   - Calculates total unexcused absence days.
 * 3. Leave     - Summarizes approved leave requests by leave type.
 * 4. Overtime  - Calculates total overtime hours worked beyond expected shift hours.
 * =========================================================================
 */
class PeriodSummaryService
{
    /** @var list<string> Supported summary report tabs */
    public const TABS = ['late', 'absence', 'leave', 'overtime'];

    /**
     * Dependency injection of ShiftResolver service to handle shift assignments and late rules.
     */
    public function __construct(private ShiftResolver $shifts)
    {
    }

    /**
     * [MAIN ENTRY POINT]: Builds report dataset for the specified tab within a date range.
     *
     * @param User $user The authenticated user requesting the report
     * @param string $from Start date of the report range (YYYY-MM-DD)
     * @param string $to End date of the report range (YYYY-MM-DD)
     * @param string $tab Selected report tab ('late', 'absence', 'leave', 'overtime')
     * @return array{from: string, to: string, tab: string, rows: list<array<string, mixed>>, total: int|float}
     * @throws InvalidArgumentException
     */
    public function buildTab(User $user, string $from, string $to, string $tab): array
    {
        // Validate tab: throw exception if tab name is invalid
        if (! in_array($tab, self::TABS, true)) {
            throw new InvalidArgumentException("Unknown summary tab [{$tab}].");
        }

        // Parse date range: set start to 00:00:00 and end to 23:59:59
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        // Fetch active employees accessible to the current user
        $employees = $this->employees($user);

        // Execute processing method corresponding to the selected tab using PHP 8 match expression
        $result = match ($tab) {
            'late' => $this->buildLate($employees, $start, $end),
            'absence' => $this->buildAbsence($employees, $start, $end),
            'leave' => $this->buildLeave($employees, $start, $end),
            'overtime' => $this->buildOvertime($employees, $start, $end),
        };

        // Construct final report response array
        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'tab' => $tab,
            'rows' => $result['rows'], // Array of employee report rows
            'total' => $result['total'], // Aggregate metric total (e.g. total late days or overtime hours)
            'employees' => $employees->count(), // Total employee count
        ];
    }

    /**
     * Retrieves active managed employees filtered by user scope permissions.
     */
    private function employees(User $user): Collection
    {
        $query = Employee::query()
            ->with('department') // Eager-load department relation
            ->where('status', 'active') // Only include active employees
            ->orderBy('name');
        
        // Apply user authorization scope filter
        $user->scopeManagedEmployees($query);

        return $query->get();
    }

    /* =========================================================================
     * 1. LATE TAB: Processes Late Arrivals Report
     * ========================================================================= */
    private function buildLate(Collection $employees, Carbon $start, Carbon $end): array
    {
        $policy = AttendancePolicy::current(); // Active global attendance policy
        $days = $this->attendanceDays($employees, $start, $end); // Grouped attendance logs
        $assignmentMap = $this->shifts->assignmentsForPeriod($employees->pluck('id'), $start, $end); // Preloaded shift assignments

        $rows = [];
        $total = 0;

        // Iterate through each employee
        foreach ($employees as $employee) {
            // Get employee attendance punch days (defaults to empty collection if none)
            $employeeDays = $days->get($employee->employee_code, collect());
            $lateDays = 0;

            // Evaluate check-in time against assigned shift or policy for each attendance day
            foreach ($employeeDays as $day) {
                // Find assigned shift for the work date
                $shift = $this->shifts->shiftFromMap($assignmentMap, $employee->id, $day->work_date);
                // Calculate late arrival minutes
                $late = $this->shifts->lateInfo($employee, $day->work_date, $day->check_in, $policy, $shift);
                
                // Increment late counter if late minutes exceed zero
                if ($late['late_minutes'] > 0) {
                    $lateDays++;
                }
            }

            // Append employee to report if they have at least 1 late day
            if ($lateDays > 0) {
                $rows[] = [
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department?->name,
                    'late_days' => $lateDays, // Total count of late days
                    'present_days' => $employeeDays->count(), // Total present days
                ];
                $total += $lateDays; // Accumulate grand total late days
            }
        }

        // Sort employees by highest late days first (Descending Order)
        usort($rows, fn ($a, $b) => $b['late_days'] <=> $a['late_days']);

        return ['rows' => $rows, 'total' => $total];
    }

    /* =========================================================================
     * 2. ABSENCE TAB: Processes Unexcused Absences Report
     * ========================================================================= */
    private function buildAbsence(Collection $employees, Carbon $start, Carbon $end): array
    {
        $holidaySet = $this->holidaySet($start, $end); // Public/company holiday dates lookup map
        $days = $this->attendanceDays($employees, $start, $end); // Biometric attendance punch logs
        $leaves = $this->approvedLeaves($employees, $start, $end); // Approved leave applications

        $rows = [];
        $total = 0;

        foreach ($employees as $employee) {
            $employeeDays = $days->get($employee->employee_code, collect());
            // Create lookup dictionary of dates employee was present in office
            $presentSet = array_flip($employeeDays->pluck('work_date')->all());
            // Create lookup dictionary of approved paid leave dates
            $paidLeaveDates = $this->paidLeaveDates($employee, $leaves->get($employee->id, collect()), $start, $end, $holidaySet);

            // [WORKING DAYS CALCULATION]: Count actual scheduled working days excluding weekends & holidays
            $workingDays = 0;
            for ($d = $start->copy()->startOfDay(); $d->lte($end); $d->addDay()) {
                $ds = $d->toDateString();
                if ($employee->isWeekend($d) || isset($holidaySet[$ds])) {
                    continue; // Exclude non-working days
                }
                $workingDays++;
            }

            // [ABSENCE FORMULA]: (Working Days) - (Present Days) - (Approved Paid Leave Days)
            $absentDays = max(0, $workingDays - count($presentSet) - count($paidLeaveDates));

            // Append employee to report if absent days > 0
            if ($absentDays > 0) {
                $rows[] = [
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department?->name,
                    'working_days' => $workingDays, // Total expected working days
                    'present_days' => $employeeDays->count(), // Total days present in office
                    'paid_leave_days' => count($paidLeaveDates), // Total approved paid leave days
                    'absent_days' => $absentDays, // Calculated unexcused absent days
                ];
                $total += $absentDays;
            }
        }

        // Sort employees by highest absent days first
        usort($rows, fn ($a, $b) => $b['absent_days'] <=> $a['absent_days']);

        return ['rows' => $rows, 'total' => $total];
    }

    /* =========================================================================
     * 3. LEAVE TAB: Processes Approved Leave Report
     * ========================================================================= */
    private function buildLeave(Collection $employees, Carbon $start, Carbon $end): array
    {
        $holidaySet = $this->holidaySet($start, $end);
        $leaves = $this->approvedLeaves($employees, $start, $end);

        $rows = [];
        $total = 0;

        foreach ($employees as $employee) {
            // Loop through each approved leave request for the employee
            foreach ($leaves->get($employee->id, collect()) as $leave) {
                // Clamp leave start date if it starts before report start date
                $ls = Carbon::parse($leave->start_date);
                if ($ls->lt($start)) {
                    $ls = $start->copy()->startOfDay();
                }
                // Clamp leave end date if it extends beyond report end date
                $le = Carbon::parse($leave->end_date);
                if ($le->gt($end)) {
                    $le = $end->copy()->startOfDay();
                }

                // Count valid leave days (excluding weekends and public holidays)
                $typeDays = 0;
                for ($d = $ls->copy(); $d->lte($le); $d->addDay()) {
                    $ds = $d->toDateString();
                    if ($employee->isWeekend($d) || isset($holidaySet[$ds])) {
                        continue;
                    }
                    $typeDays++;
                }

                if ($typeDays > 0) {
                    $rows[] = [
                        'employee_name' => $employee->name,
                        'employee_code' => $employee->employee_code,
                        'department' => $employee->department?->name,
                        'leave_type' => $leave->leaveType?->name ?? '—', // Name of leave type (Casual, Sick, Annual, etc.)
                        'days' => $typeDays, // Total days taken for this leave record
                        'is_paid' => (bool) ($leave->leaveType?->is_paid), // Whether this leave type is paid or unpaid
                    ];
                    $total += $typeDays;
                }
            }
        }

        // Sort rows by leave days descending
        usort($rows, fn ($a, $b) => $b['days'] <=> $a['days']);

        return ['rows' => $rows, 'total' => $total];
    }

    /* =========================================================================
     * 4. OVERTIME TAB: Processes Overtime Hours Report
     * ========================================================================= */
    private function buildOvertime(Collection $employees, Carbon $start, Carbon $end): array
    {
        $standardDailyMinutes = 8 * 60; // Default expected daily work duration: 8 hours = 480 minutes
        $days = $this->attendanceDays($employees, $start, $end);
        $assignmentMap = $this->shifts->assignmentsForPeriod($employees->pluck('id'), $start, $end);

        $rows = [];
        $totalMinutes = 0;

        foreach ($employees as $employee) {
            $employeeDays = $days->get($employee->employee_code, collect());
            $otMinutes = 0; // Initialize total overtime minutes counter

            foreach ($employeeDays as $day) {
                // Ignore punch log if check-out time is missing
                if (! $day->check_out) {
                    continue;
                }
                // Determine shift and calculate worked minutes (Difference between check-in and check-out)
                $shift = $this->shifts->shiftFromMap($assignmentMap, $employee->id, $day->work_date);
                $worked = Carbon::parse($day->check_in)->diffInMinutes(Carbon::parse($day->check_out));
                // Get expected work duration from shift definition (or default to 480 mins)
                $expected = $shift ? $shift->expectedWorkedMinutes() : $standardDailyMinutes;
                
                // Add excess worked minutes to overtime accumulation
                $otMinutes += max(0, $worked - $expected);
            }

            // Append employee if accumulated overtime minutes > 0
            if ($otMinutes > 0) {
                $hours = round($otMinutes / 60, 2); // Convert overtime minutes into hours (e.g. 90 mins -> 1.5 hrs)
                $rows[] = [
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department?->name,
                    'overtime_hours' => $hours, // Total calculated overtime hours
                    'present_days' => $employeeDays->count(), // Total attendance days
                ];
                $totalMinutes += $otMinutes;
            }
        }

        // Sort employees by highest overtime hours first
        usort($rows, fn ($a, $b) => $b['overtime_hours'] <=> $a['overtime_hours']);

        return ['rows' => $rows, 'total' => round($totalMinutes / 60, 2)];
    }

    /* =========================================================================
     * HELPER METHODS: Attendance Punch, Leave, and Holiday Processing
     * ========================================================================= */

    /**
     * Queries biometric logs (`attendance_logs`) to extract daily Check-In (MIN timestamp)
     * and Check-Out (MAX timestamp) for each employee.
     */
    private function attendanceDays(Collection $employees, Carbon $start, Carbon $end): Collection
    {
        return DB::table('attendance_logs as al')
            ->selectRaw('al.employee_code as employee_code')
            ->selectRaw('DATE(al.punched_at) as work_date')
            ->selectRaw('MIN(al.punched_at) as check_in') // Earliest punch of the day
            ->selectRaw('CASE WHEN MAX(al.punched_at) > MIN(al.punched_at) THEN MAX(al.punched_at) END as check_out') // Latest punch if distinct
            ->where('al.punched_at', '>=', $start->format('Y-m-d').' 00:00:00')
            ->where('al.punched_at', '<=', $end->format('Y-m-d').' 23:59:59')
            ->when($employees->isNotEmpty(), fn ($q) => $q->whereIn('al.employee_code', $employees->pluck('employee_code')))
            ->when($employees->isEmpty(), fn ($q) => $q->whereRaw('0 = 1'))
            ->groupByRaw('al.employee_code, DATE(al.punched_at)')
            ->get()
            ->groupBy('employee_code'); // Group collection by employee_code
    }

    /**
     * Fetches approved leave requests covering the period grouped by employee_id.
     */
    private function approvedLeaves(Collection $employees, Carbon $start, Carbon $end): Collection
    {
        if ($employees->isEmpty()) {
            return collect();
        }

        return LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED) // Only include approved leaves
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->whereIn('employee_id', $employees->pluck('id'))
            ->with('leaveType')
            ->get()
            ->groupBy('employee_id');
    }

    /**
     * Returns a key-value dictionary (Set) of holiday dates within the period for fast O(1) lookup.
     */
    private function holidaySet(Carbon $start, Carbon $end): array
    {
        return Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [Carbon::parse($d)->toDateString() => true])
            ->all();
    }

    /**
     * Extracts dictionary of valid paid leave dates for an employee, ignoring weekend and holiday overlaps.
     */
    private function paidLeaveDates(Employee $employee, Collection $leaves, Carbon $start, Carbon $end, array $holidaySet): array
    {
        $paidLeaveDates = [];

        foreach ($leaves as $leave) {
            // Skip unpaid leave types
            if (! $leave->leaveType?->is_paid) {
                continue;
            }

            $ls = Carbon::parse($leave->start_date);
            if ($ls->lt($start)) {
                $ls = $start->copy()->startOfDay();
            }
            $le = Carbon::parse($leave->end_date);
            if ($le->gt($end)) {
                $le = $end->copy()->startOfDay();
            }

            // Iterate through each date in the leave range
            for ($d = $ls->copy(); $d->lte($le); $d->addDay()) {
                $ds = $d->toDateString();
                // Exclude weekends and public holidays from paid leave day count
                if ($employee->isWeekend($d) || isset($holidaySet[$ds])) {
                    continue;
                }
                $paidLeaveDates[$ds] = true;
            }
        }

        return $paidLeaveDates;
    }
}
