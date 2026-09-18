<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\SalesApprovalRequest;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesRequestReturnedNotification extends Notification
{
    use Queueable;

    public function __construct(protected SalesApprovalRequest $request, protected string $reason, protected string $returnedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return [...['mail', 'database'], ...WhatsAppChannel::enabledFor($notifiable)];
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::make(
            'sales_request_returned',
            [$notifiable->name, $this->request->request_number, $this->returnedBy, $this->reason],
            __('whatsapp.sales_request_returned', [
                'name' => $notifiable->name,
                'number' => $this->request->request_number,
                'by' => $this->returnedBy,
                'reason' => $this->reason,
            ]),
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('sales.request_returned'))
            ->greeting(__('general.hello') . ' ' . $notifiable->name . ',')
            ->line(__('sales.request_returned_message'))
            ->line(__('sales.request_number') . ': ' . $this->request->request_number)
            ->line(__('general.reason') . ': ' . $this->reason)
            ->line(__('sales.returned_by') . ': ' . $this->returnedBy)
            ->action(__('sales.edit_request'), url('/sales-approval-requests/' . $this->request->id . '/edit'))
            ->line(__('general.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sales_request_returned',
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'reason' => $this->reason,
            'returned_by' => $this->returnedBy,
            'message' => __('sales.request_returned_message') . ': ' . $this->request->request_number,
        ];
    }
}
