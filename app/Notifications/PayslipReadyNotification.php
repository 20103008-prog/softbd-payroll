<?php

namespace App\Notifications;

use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public PayrollRun $run, public Payslip $payslip)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payslip ready — '.$this->run->periodLabel())
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your payslip for '.$this->run->periodLabel().' is ready.')
            ->line('Net payable: ৳ '.number_format((float) $this->payslip->net_salary, 2))
            ->action('View payslip', url(route('me.payslip', $this->payslip)))
            ->line('You can also download a PDF from My portal.');
    }
}
