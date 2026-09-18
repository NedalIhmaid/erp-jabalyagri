<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\SalesApprovalRequest;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(protected SalesApprovalRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return [...['mail', 'database'], ...WhatsAppChannel::enabledFor($notifiable)];
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::make(
            'sales_request_submitted',
            [$notifiable->name, $this->request->request_number, $this->request->client_name],
            __('whatsapp.sales_request_submitted', [
                'name' => $notifiable->name,
                'number' => $this->request->request_number,
                'client' => $this->request->client_name,
            ]),
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('sales.request_number') . ': ' . $this->request->request_number)
            ->greeting(__('general.hello') . ' ' . $notifiable->name . ',')
            ->line(__('sales.new_request_submitted'))
            ->line(__('sales.request_number') . ': ' . $this->request->request_number)
            ->line(__('sales.client_name') . ': ' . $this->request->client_name)
            ->action(__('sales.view_request'), url('/sales-approval-requests/' . $this->request->id))
            ->line(__('general.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sales_request_submitted',
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'client_name' => $this->request->client_name,
            'message' => __('sales.new_request_submitted') . ': ' . $this->request->request_number,
        ];
    }
}
