<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\HrRequest;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(protected HrRequest $request, protected string $approvedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return [...['mail', 'database'], ...WhatsAppChannel::enabledFor($notifiable)];
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $type = $this->request->type->getLabel();

        return WhatsAppMessage::make(
            'hr_request_approved',
            [$notifiable->name, $type, $this->approvedBy],
            __('whatsapp.hr_request_approved', [
                'name' => $notifiable->name,
                'type' => $type,
                'by' => $this->approvedBy,
            ]),
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('hr.request_approved'))
            ->greeting(__('general.hello') . ' ' . $notifiable->name . ',')
            ->line(__('hr.request_approved_message'))
            ->line(__('hr.type') . ': ' . $this->request->type->getLabel())
            ->line(__('hr.approved_by') . ': ' . $this->approvedBy)
            ->action(__('hr.view_request'), url('/hr-requests/' . $this->request->id))
            ->line(__('general.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hr_request_approved',
            'request_id' => $this->request->id,
            'request_type' => $this->request->type->getLabel(),
            'approved_by' => $this->approvedBy,
            'message' => __('hr.request_approved_message'),
        ];
    }
}
