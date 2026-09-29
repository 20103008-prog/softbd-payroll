<?php

namespace App\Services\Payroll;

use App\Models\AttendancePolicy;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Services\Roster\ShiftResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service class for computing monthly payroll, attendance deductions, overtime, allowances, and payslips.
 */
class PayrollCalculator
{
    public function __construct(private ShiftResolver $shifts)
    {
    }

    /**
     * Build (or rebuild) all payslips for a payroll run from attendance logs and salary structures.
     *
     * Logic Flow:
     * 1. Retrieve attendance logs (check-in/check-out) grouped by employee for the payroll month.
     * 2. Exclude holidays and weekends from absence calculations.
     * 3. Calculate working days, present days, paid leave days, late arrivals, half days, and overtime.
     * 4. Calculate daily rate: (Monthly Basic Salary / Working Days).
     * 5. Calculate deductions for absent days and half days.
     * 6. Calculate recurring allowances (earnings) and fixed/percentage deductions (e.g. tax, provident fund).
     * 7. Compute Gross Salary = (Basic + Overtime + Allowances) and Net Salary = (Gross - Deductions).
     * 8. Persist payslips and snapshot line items inside a database transaction.
     */
    public function generate(PayrollRun $run): int
    {
        $policy = AttendancePolicy::current();
        $start = $run->periodStart();
        $end = $run->periodEnd();
        $standardDailyMinutes = (int) round(((float) $run->standard_daily_hours) * 60);

        // 1. Fetch per-day check-in / check-out times for every employee in the payroll period
        $days = DB::table('attendance_logs as al')
            ->selectRaw('al.employee_code as employee_code')
            ->selectRaw('DATE(al.punched_at) as work_date')
            ->selectRaw('MIN(al.punched_at) as check_in')
            ->selectRaw('CASE WHEN MAX(al.punched_at) > MIN(al.punched_at) THEN MAX(al.punched_at) END as check_out')
            ->where('al.punched_at', '>=', $start->format('Y-m-d').' 00:00:00')
            ->where('al.punched_at', '<=', $end->format('Y-m-d').' 23:59:59')
            ->groupByRaw('al.employee_code, DATE(al.punched_at)')
            ->get()
            ->groupBy('employee_code');

        // 2. Fetch public holidays in the period (excluded from absences and paid-leave counts)
        $holidaySet = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [Carbon::parse($d)->toDateString() => true])
            ->all();

        // 3. Fetch approved paid leave requests overlapping the payroll period
        $leaves = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', fn ($q) => $q->where('is_paid', true))
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get(['employee_id', 'start_date', 'end_date'])
            ->groupBy('employee_id');

        $structures = SalaryStructure::query()->with('employee.salaryComponents')->get();
        $assignmentMap = $this->shifts->assignmentsForPeriod(
            $structures->pluck('employee_id'),
            $start,
            $end
        );

        return DB::transaction(function () use ($run, $structures, $days, $policy, $standardDailyMinutes, $holidaySet, $leaves, $start, $end, $assignmentMap) {
            $run->payslips()->delete();

            $count = 0;

            foreach ($structures as $structure) {
                $employee = $structure->employee;
                if (! $employee) {
                    continue;
                }

                $employeeDays = $days->get($employee->employee_code, collect());

                $presentDays = $employeeDays->count();
                $presentSet = array_flip($employeeDays->pluck('work_date')->all());
                $lateDays = 0;
                $halfDays = 0;
                $overtimeMinutes = 0;

                // 4. Calculate attendance penalties and overtime for each worked day
                foreach ($employeeDays as $day) {
                    $shift = $this->shifts->shiftFromMap($assignmentMap, $employee->id, $day->work_date);
                    $late = $this->shifts->lateInfo($employee, $day->work_date, $day->check_in, $policy, $shift);
                    if ($late['late_minutes'] > 0) {
                        $lateDays++;
                    }
                    if ($late['half_day']) {
                        $halfDays++;
                    }

                    if ($day->check_out) {
                        $worked = Carbon::parse($day->check_in)->diffInMinutes(Carbon::parse($day->check_out));
                        $expected = $shift
                            ? $shift->expectedWorkedMinutes()
                            : $standardDailyMinutes;
                        $overtimeMinutes += max(0, $worked - $expected);
                    }
                }

                // 5. Count paid leave days excluding weekends and holidays
                $employeeWeekend = $employee->weekendDays();
                $leaveDates = [];
                foreach ($leaves->get($employee->id, collect()) as $leave) {
                    $ls = Carbon::parse($leave->start_date);
                    if ($ls->lt($start)) {
                        $ls = $start->copy();
                    }
                    $le = Carbon::parse($leave->end_date);
                    if ($le->gt($end)) {
                        $le = $end->copy();
                    }

                    for ($d = $ls->copy(); $d->lte($le); $d->addDay()) {
                        $ds = $d->toDateString();
                        if (in_array((int) $d->dayOfWeek, $employeeWeekend, true) || isset($holidaySet[$ds]) || isset($presentSet[$ds])) {
                            continue;
                        }
                        $leaveDates[$ds] = true;
                    }
                }
                $paidLeaveDays = count($leaveDates);

                // 6. Calculate daily rate and salary deductions
                $workingDays = (int) $run->working_days;
                $absentDays = max(0, $workingDays - $presentDays - $paidLeaveDays);
                $monthly = (float) $structure->monthly_salary;
                $perDay = $workingDays > 0 ? $monthly / $workingDays : 0;

                $absentDeduction = round($absentDays * $perDay, 2);
                $lateDeduction = round($halfDays * 0.5 * $perDay, 2);

                $overtimeHours = round($overtimeMinutes / 60, 2);
                $overtimeAmount = round($overtimeHours * (float) $structure->overtime_rate, 2);

                // 7. Resolve recurring salary components (allowances & deductions)
                $allowancesTotal = 0;
                $componentDeductions = 0;
                $items = [];
                foreach ($employee->salaryComponents as $component) {
                    $value = $component->resolve($monthly);
                    if ($value == 0.0) {
                        continue;
                    }
                    if ($component->isEarning()) {
                        $allowancesTotal += $value;
                        $items[] = ['name' => $component->name, 'type' => 'earning', 'amount' => $value];
                    } else {
                        $componentDeductions += $value;
                        $items[] = ['name' => $component->name, 'type' => 'deduction', 'amount' => $value];
                    }
                }
                $allowancesTotal = round($allowancesTotal, 2);
                $componentDeductions = round($componentDeductions, 2);

                // 8. Calculate final gross salary, total deductions, and net payable salary
                $grossSalary = round($monthly + $overtimeAmount + $allowancesTotal, 2);
                $totalDeductions = round($absentDeduction + $lateDeduction + $componentDeductions, 2);
                $netSalary = round($grossSalary - $totalDeductions, 2);

                // 9. Save payslip record into database
                $slip = Payslip::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee->name,
                    'monthly_salary' => $monthly,
                    'working_days' => $workingDays,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'paid_leave_days' => $paidLeaveDays,
                    'late_days' => $lateDays,
                    'half_days' => $halfDays,
                    'overtime_hours' => $overtimeHours,
                    'overtime_amount' => $overtimeAmount,
                    'allowances_total' => $allowancesTotal,
                    'absent_deduction' => $absentDeduction,
                    'late_deduction' => $lateDeduction,
                    'component_deductions' => $componentDeductions,
                    'gross_salary' => $grossSalary,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                ]);

                if (! empty($items)) {
                    $slip->items()->createMany($items);
                }

                $count++;
            }

            $run->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            return $count;
        });
    }
}
