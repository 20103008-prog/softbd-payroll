<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveReviewedNotification extends Notification
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
        $this->leave->loadMissing('leaveType');

        $status = $this->leave->status;
        $label = ucfirst($status);
        $type = $this->leave->leaveType?->name ?? 'Leave';
        $name = $notifiable->name ?? 'there';

        $mail = (new MailMessage)
            ->subject("Leave {$label}: {$type}")
            ->greeting('Hello '.$name.',')
            ->line("Your {$type} request has been {$status}.")
            ->line('Dates: '.$this->leave->start_date->format('M j, Y').' – '.$this->leave->end_date->format('M j, Y').' ('.$this->leave->days.' day(s))');

        if ($status === LeaveRequest::STATUS_APPROVED) {
            $mail->action('View my leave', url(route('me.leave')));
        }

        return $mail;
    }
}
