<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\SalesApprovalRequest;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesRequestStageAdvancedNotification extends Notification
{
    use Queueable;

    public function __construct(protected SalesApprovalRequest $request, protected int $stageNumber) {}

    public function via(object $notifiable): array
    {
        return [...['mail', 'database'], ...WhatsAppChannel::enabledFor($notifiable)];
    }

    protected function stageName(): string
    {
        return match ($this->stageNumber) {
            1 => __('roles.warehouse_keeper'),
            2 => __('roles.financial_manager'),
            3 => __('roles.purchasing_manager'),
            default => (string) $this->stageNumber,
        };
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::make(
            'sales_request_stage_advanced',
            [$notifiable->name, $this->request->request_number, $this->stageName()],
            __('whatsapp.sales_request_stage_advanced', [
                'name' => $notifiable->name,
                'number' => $this->request->request_number,
                'stage' => $this->stageName(),
            ]),
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('sales.request_advanced_to_stage').' '.$this->stageNumber)
            ->greeting(__('general.hello').' '.$notifiable->name.',')
            ->line(__('sales.request_advanced_message'))
            ->line(__('sales.request_number').': '.$this->request->request_number)
            ->line(__('sales.current_stage').': '.$this->stageName())
            ->action(__('sales.view_request'), url('/sales-approval-requests/'.$this->request->id))
            ->line(__('general.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sales_request_stage_advanced',
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'stage_number' => $this->stageNumber,
            'message' => __('sales.request_advanced_message').' - '.$this->request->request_number,
        ];
    }
}
