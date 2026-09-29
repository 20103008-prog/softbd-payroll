<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Services\Attendance\PunchRecorder;
use App\Services\Leave\LeaveBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SelfServiceController extends Controller
{
    private function employee(): Employee
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 403, 'Your account is not linked to an employee profile. Ask an admin to link it.');

        return $employee;
    }

    /**
     * Display the employee self-service portal attendance view.
     */
    public function attendance(Request $request, PunchRecorder $punches): View
    {
        $employee = $this->employee();

        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $logs = AttendanceLog::query()
            ->where('employee_code', $employee->employee_code)
            ->whereDate('punched_at', '>=', $from)
            ->whereDate('punched_at', '<=', $to)
            ->orderByDesc('punched_at')
            ->paginate(30)
            ->withQueryString();

        $todayLastPunch = $punches->lastToday($employee);

        return view('me.attendance', [
            'employee' => $employee,
            'logs' => $logs,
            'from' => $from,
            'to' => $to,
            'todayLastPunch' => $todayLastPunch,
        ]);
    }

    /**
     * Own Check In / Check Out — same pipeline as a device punch.
     */
    public function punchNow(PunchRecorder $punches): RedirectResponse
    {
        $employee = $this->employee();

        try {
            $log = $punches->record($employee);
        } catch (QueryException $e) {
            return back()->with('warning', 'A punch with the same time and status already exists.');
        }

        $label = $log->status === 'in' ? 'Check In' : 'Check Out';

        return back()->with('success', $label.' recorded at '.$log->punched_at->format('g:i A').'.');
    }

    public function leaveIndex(LeaveBalanceService $balances): View
    {
        $employee = $this->employee();
        $year = (int) now()->year;

        $requests = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('me.leave', [
            'employee' => $employee,
            'requests' => $requests,
            'balances' => $balances->forEmployee($employee, $year),
            'year' => $year,
        ]);
    }

    public function leaveCreate(): View
    {
        $employee = $this->employee();

        return view('me.leave-create', [
            'employee' => $employee,
            'leaveTypes' => LeaveType::orderBy('name')->get(),
        ]);
    }

    public function leaveStore(Request $request, LeaveBalanceService $balances, \App\Services\Notify\HrNotifier $notifier): RedirectResponse
    {
        $employee = $this->employee();

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'start_date.after' => 'Leave requests can only be made for future dates (after today).',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $type = LeaveType::findOrFail($validated['leave_type_id']);

        $days = $this->workingDays($start, $end, $employee->weekendDays());

        if ($days < 1) {
            return back()
                ->withInput()
                ->withErrors(['start_date' => "The selected range has no working days — your weekend ({$employee->weekendLabel()}) and holidays are excluded."]);
        }

        if ($type->annual_quota > 0) {
            $used = $balances->usedApprovedDays($employee, $type, $start->year);
            $remaining = $type->annual_quota - $used;
            if ($days > $remaining) {
                return back()
                    ->withInput()
                    ->withErrors(['leave_type_id' => "This exceeds your {$type->name} quota — {$remaining} day(s) remaining but {$days} requested."]);
            }
        }

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $validated['reason'] ?? null,
            'status' => LeaveRequest::STATUS_PENDING,
            'reviewed_at' => null,
        ]);

        $notifier->leaveSubmitted($leave);

        return redirect()->route('me.leave')->with('success', 'Leave request submitted for approval.');
    }

    public function payslips(): View
    {
        $employee = $this->employee();

        $slips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->whereIn('status', [
                PayrollRun::STATUS_GENERATED,
                PayrollRun::STATUS_LOCKED,
            ]))
            ->with('payrollRun')
            ->orderByDesc('id')
            ->paginate(12);

        return view('me.payslips', [
            'employee' => $employee,
            'slips' => $slips,
        ]);
    }

    public function payslip(Payslip $payslip): View
    {
        $employee = $this->employee();
        abort_unless($payslip->employee_id === $employee->id, 403);

        $run = $payslip->payrollRun;
        abort_unless($run && in_array($run->status, [PayrollRun::STATUS_GENERATED, PayrollRun::STATUS_LOCKED], true), 404);

        $payslip->load('items');

        return view('payroll.payslip', [
            'run' => $run,
            'slip' => $payslip,
            'company' => \App\Models\CompanySetting::current(),
            'backRoute' => route('me.payslips'),
            'pdfRoute' => route('me.payslip.pdf', $payslip),
        ]);
    }

    public function payslipPdf(Payslip $payslip): Response
    {
        $employee = $this->employee();
        abort_unless($payslip->employee_id === $employee->id, 403);

        $run = $payslip->payrollRun;
        abort_unless($run && in_array($run->status, [PayrollRun::STATUS_GENERATED, PayrollRun::STATUS_LOCKED], true), 404);

        $payslip->load('items');

        $pdf = Pdf::loadView('payroll.payslip-pdf', [
            'run' => $run,
            'slip' => $payslip,
            'company' => \App\Models\CompanySetting::current(),
        ]);

        $filename = 'payslip_'.$employee->employee_code.'_'.$run->periodStart()->format('Y_m').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  list<int>  $weekendDays
     */
    private function workingDays(Carbon $start, Carbon $end, array $weekendDays): int
    {
        $holidays = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        $count = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (in_array((int) $d->dayOfWeek, $weekendDays, true) || isset($holidays[$d->toDateString()])) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}
