<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\HrRequest;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrRequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(protected HrRequest $request, protected string $reason, protected string $rejectedBy)
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
            'hr_request_rejected',
            [$notifiable->name, $type, $this->rejectedBy, $this->reason],
            __('whatsapp.hr_request_rejected', [
                'name' => $notifiable->name,
                'type' => $type,
                'by' => $this->rejectedBy,
                'reason' => $this->reason,
            ]),
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('hr.request_rejected'))
            ->greeting(__('general.hello') . ' ' . $notifiable->name . ',')
            ->line(__('hr.request_rejected_message'))
            ->line(__('hr.type') . ': ' . $this->request->type->getLabel())
            ->line(__('general.reason') . ': ' . $this->reason)
            ->line(__('hr.rejected_by') . ': ' . $this->rejectedBy)
            ->action(__('hr.view_request'), url('/hr-requests/' . $this->request->id))
            ->line(__('general.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hr_request_rejected',
            'request_id' => $this->request->id,
            'request_type' => $this->request->type->getLabel(),
            'reason' => $this->reason,
            'rejected_by' => $this->rejectedBy,
            'message' => __('hr.request_rejected_message'),
        ];
    }
}
