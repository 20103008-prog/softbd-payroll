<?php

namespace App\Services\Notify;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\User;
use App\Notifications\LeaveReviewedNotification;
use App\Notifications\LeaveSubmittedNotification;
use App\Notifications\PayslipReadyNotification;
use Illuminate\Support\Facades\Notification;

class HrNotifier
{
    public function leaveSubmitted(LeaveRequest $leave): void
    {
        $leave->loadMissing('employee');

        $recipients = User::query()
            ->whereIn('role', [
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN,
                User::ROLE_HR,
                User::ROLE_MANAGER,
            ])
            ->get()
            ->filter(function (User $user) use ($leave) {
                if (! $user->hasPermission('manage-leave')) {
                    return false;
                }

                return $user->canAccessEmployee($leave->employee);
            })
            ->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new LeaveSubmittedNotification($leave));
        }
    }

    public function leaveReviewed(LeaveRequest $leave): void
    {
        $leave->loadMissing('employee.user');

        $user = $leave->employee?->user;
        if ($user) {
            $user->notify(new LeaveReviewedNotification($leave));

            return;
        }

        $email = $leave->employee?->email;
        if ($email) {
            Notification::route('mail', $email)
                ->notify(new LeaveReviewedNotification($leave));
        }
    }

    public function payrollLocked(PayrollRun $run): void
    {
        $run->load(['payslips.employee.user']);

        foreach ($run->payslips as $payslip) {
            $user = $payslip->employee?->user;
            if ($user) {
                $user->notify(new PayslipReadyNotification($run, $payslip));
            }
        }
    }
}
