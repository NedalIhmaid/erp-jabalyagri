<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\HrRequest;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(protected HrRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return [...['mail', 'database'], ...WhatsAppChannel::enabledFor($notifiable)];
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $type = $this->request->type->getLabel();
        $employee = $this->request->user?->name ?? '—';
        $date = $this->request->start_date?->format('Y-m-d') ?? '—';

        return WhatsAppMessage::make(
            'hr_request_submitted',
            [$notifiable->name, $type, $employee, $date],
            __('whatsapp.hr_request_submitted', [
                'name' => $notifiable->name,
                'type' => $type,
                'employee' => $employee,
                'date' => $date,
            ]),
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('hr.new_request_submitted'))
            ->greeting(__('general.hello') . ' ' . $notifiable->name . ',')
            ->line(__('hr.new_hr_request_message'))
            ->line(__('hr.type') . ': ' . $this->request->type->getLabel())
            ->line(__('hr.start_date') . ': ' . $this->request->start_date->format('Y-m-d'))
            ->action(__('hr.view_request'), url('/hr-requests/' . $this->request->id))
            ->line(__('general.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hr_request_submitted',
            'request_id' => $this->request->id,
            'request_type' => $this->request->type->getLabel(),
            'message' => __('hr.new_hr_request_message'),
        ];
    }
}
