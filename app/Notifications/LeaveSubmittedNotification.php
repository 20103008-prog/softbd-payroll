<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leave)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->leave->loadMissing(['employee', 'leaveType']);

        $employee = $this->leave->employee;
        $type = $this->leave->leaveType?->name ?? 'Leave';
        $name = $notifiable->name ?? 'there';

        $mail = (new MailMessage)
            ->subject("Leave request: {$employee?->name} ({$type})")
            ->greeting('Hello '.$name.',')
            ->line(($employee?->name ?? 'An employee').' submitted a leave request.')
            ->line("Type: {$type}")
            ->line('Dates: '.$this->leave->start_date->format('M j, Y').' – '.$this->leave->end_date->format('M j, Y').' ('.$this->leave->days.' day(s))');

        if ($this->leave->reason) {
            $mail->line('Reason: '.$this->leave->reason);
        }

        return $mail
            ->action('Review leave', url(route('leave.index', ['status' => 'pending'])))
            ->line('Please approve or reject this request in HRMS.');
    }
}
