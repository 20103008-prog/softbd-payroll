<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use App\Services\Payroll\PayrollCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    /**
     * Display a listing of generated payroll runs with summary payout totals.
     */
    public function index(): View
    {
        $runs = PayrollRun::query()
            ->withCount('payslips')
            ->withSum('payslips', 'net_salary')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12);

        return view('payroll.index', [
            'runs' => $runs,
            'defaultMonth' => now()->format('Y-m'),
            'defaultWorkingDays' => $this->suggestedWorkingDays((int) now()->year, (int) now()->month),
            'salariedCount' => SalaryStructure::count(),
        ]);
    }

    /**
     * Generate or regenerate payroll for the specified month and year.
     * Uses PayrollCalculator to compute attendance, overtime, allowances, and net salary.
     */
    public function store(Request $request, PayrollCalculator $calculator): RedirectResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'working_days' => ['nullable', 'integer', 'min:1', 'max:31'],
            'standard_daily_hours' => ['required', 'numeric', 'min:1', 'max:24'],
        ]);

        [$year, $month] = array_map('intval', explode('-', $validated['period']));

        $workingDays = $validated['working_days'] ?? $this->suggestedWorkingDays($year, $month);

        if (SalaryStructure::count() === 0) {
            return redirect()
                ->route('payroll.salaries')
                ->with('warning', 'Set at least one employee salary before generating payroll.');
        }

        $existing = PayrollRun::query()->where('year', $year)->where('month', $month)->first();
        if ($existing?->isLocked()) {
            return back()
                ->withInput()
                ->with('error', "Payroll for {$existing->periodLabel()} is locked and cannot be regenerated.");
        }

        $run = PayrollRun::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'working_days' => $workingDays,
                'standard_daily_hours' => $validated['standard_daily_hours'],
            ]
        );

        // Generate payslips for all eligible employees using the PayrollCalculator service
        $created = $calculator->generate($run);

        return redirect()
            ->route('payroll.show', $run)
            ->with('success', "Payroll generated for {$run->periodLabel()} — {$created} payslip(s).");
    }

    /**
     * Display detailed breakdown of payslips and gross/net payout totals for a payroll run.
     */
    public function show(PayrollRun $run): View
    {
        $run->load(['payslips' => fn ($q) => $q->orderBy('employee_name')]);

        $totals = [
            'gross' => $run->payslips->sum('gross_salary'),
            'deductions' => $run->payslips->sum('total_deductions'),
            'net' => $run->payslips->sum('net_salary'),
            'overtime' => $run->payslips->sum('overtime_amount'),
        ];

        return view('payroll.show', [
            'run' => $run,
            'totals' => $totals,
        ]);
    }

    /**
     * Lock a payroll run to prevent further modifications and notify employees.
     */
    public function lock(PayrollRun $run, \App\Services\Notify\HrNotifier $notifier): RedirectResponse
    {
        if ($run->isLocked()) {
            return back()->with('warning', 'This payroll run is already locked.');
        }

        if ($run->payslips()->count() === 0) {
            return back()->with('warning', 'Generate payslips before locking payroll.');
        }

        $run->update([
            'status' => PayrollRun::STATUS_LOCKED,
            'locked_at' => now(),
        ]);

        $notifier->payrollLocked($run->fresh());

        return back()->with('success', "Payroll for {$run->periodLabel()} is locked. Employees with portal accounts were emailed.");
    }

    /**
     * Unlock a previously locked payroll run.
     */
    public function unlock(PayrollRun $run): RedirectResponse
    {
        if (! $run->isLocked()) {
            return back()->with('warning', 'This payroll run is not locked.');
        }

        $run->update([
            'status' => PayrollRun::STATUS_GENERATED,
            'locked_at' => null,
        ]);

        return back()->with('success', "Payroll for {$run->periodLabel()} unlocked — you can regenerate if needed.");
    }

    public function payslip(PayrollRun $run, Payslip $payslip): View
    {
        abort_unless($payslip->payroll_run_id === $run->id, 404);

        $payslip->load('items');

        return view('payroll.payslip', [
            'run' => $run,
            'slip' => $payslip,
            'company' => CompanySetting::current(),
            'backRoute' => route('payroll.show', $run),
            'pdfRoute' => route('payroll.payslip.pdf', [$run, $payslip]),
        ]);
    }

    public function payslipPdf(PayrollRun $run, Payslip $payslip): Response
    {
        abort_unless($payslip->payroll_run_id === $run->id, 404);

        $payslip->load('items');

        $pdf = Pdf::loadView('payroll.payslip-pdf', [
            'run' => $run,
            'slip' => $payslip,
            'company' => CompanySetting::current(),
        ]);

        $filename = 'payslip_'.$payslip->employee_code.'_'.$run->periodStart()->format('Y_m').'.pdf';

        return $pdf->download($filename);
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        if ($run->isLocked()) {
            return back()->with('error', "Locked payroll for {$run->periodLabel()} cannot be deleted. Unlock it first.");
        }

        $label = $run->periodLabel();
        $run->delete();

        return redirect()
            ->route('payroll.index')
            ->with('success', "Payroll for {$label} deleted.");
    }

    public function exportCsv(PayrollRun $run): StreamedResponse
    {
        $run->load(['payslips' => fn ($q) => $q->orderBy('employee_name')]);

        $filename = 'payroll_'.$run->periodStart()->format('Y_m').'.csv';

        return response()->streamDownload(function () use ($run) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Employee Code',
                'Employee Name',
                'Monthly Salary',
                'Working Days',
                'Present',
                'Absent',
                'Paid Leave',
                'Late Days',
                'Half Days',
                'Overtime Hours',
                'Overtime Amount',
                'Allowances',
                'Absent Deduction',
                'Late Deduction',
                'Other Deductions',
                'Gross',
                'Total Deductions',
                'Net Salary',
            ]);

            foreach ($run->payslips as $slip) {
                fputcsv($out, [
                    $slip->employee_code,
                    $slip->employee_name,
                    number_format((float) $slip->monthly_salary, 2, '.', ''),
                    $slip->working_days,
                    $slip->present_days,
                    $slip->absent_days,
                    $slip->paid_leave_days,
                    $slip->late_days,
                    $slip->half_days,
                    number_format((float) $slip->overtime_hours, 2, '.', ''),
                    number_format((float) $slip->overtime_amount, 2, '.', ''),
                    number_format((float) $slip->allowances_total, 2, '.', ''),
                    number_format((float) $slip->absent_deduction, 2, '.', ''),
                    number_format((float) $slip->late_deduction, 2, '.', ''),
                    number_format((float) $slip->component_deductions, 2, '.', ''),
                    number_format((float) $slip->gross_salary, 2, '.', ''),
                    number_format((float) $slip->total_deductions, 2, '.', ''),
                    number_format((float) $slip->net_salary, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function suggestedWorkingDays(int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $holidays = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [Carbon::parse($d)->toDateString() => true])
            ->all();

        $count = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array((int) $date->dayOfWeek, Employee::DEFAULT_WEEKEND_DAYS, true)) {
                continue;
            }
            if (isset($holidays[$date->toDateString()])) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}
