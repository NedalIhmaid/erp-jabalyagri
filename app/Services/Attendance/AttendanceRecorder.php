<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Turns inbound WhatsApp messages from employees into attendance records.
 *
 * Every message from a known employee refreshes their 24-hour session; a
 * shared location is stored as the day's check-in, or as a check-out once a
 * check-in already exists for that day.
 */
class AttendanceRecorder
{
    public function __construct(protected WhatsAppClient $client) {}

    /**
     * @param  string  $from  Sender number in any format; non-digits are stripped
     * @param  array{latitude: mixed, longitude: mixed, address?: string|null, label?: string|null}|null  $location
     */
    public function handleInbound(string $from, ?string $messageId, ?array $location): ?AttendanceRecord
    {
        $user = User::findByWhatsAppNumber($from);

        if (! $user) {
            return null;
        }

        // Bypasses model events and updated_at: this is delivery metadata, not a profile edit.
        User::query()->whereKey($user->getKey())->toBase()->update(['whatsapp_last_inbound_at' => now()]);

        if (! $this->isValidLocation($location)) {
            return null;
        }

        $type = $user->attendanceRecords()->today()->where('type', AttendanceType::CheckIn)->exists()
            ? AttendanceType::CheckOut
            : AttendanceType::CheckIn;

        try {
            $record = $user->attendanceRecords()->create([
                'type' => $type,
                'latitude' => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
                'address' => $this->limit($location['address'] ?? null),
                'label' => $this->limit($location['label'] ?? null),
                'provider_message_id' => $messageId ?: null,
                'recorded_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // The provider retried a webhook we already stored.
            return null;
        }

        $this->confirm($user, $record);

        return $record;
    }

    /**
     * The employee has just messaged us, so the session is open and a
     * free-form reply needs no approved template.
     */
    protected function confirm(User $user, AttendanceRecord $record): void
    {
        $to = $user->routeNotificationForWhatsapp();

        if (! $to || ! WhatsAppClient::isConfigured()) {
            return;
        }

        $text = __(
            $record->type === AttendanceType::CheckIn ? 'whatsapp.attendance.checked_in' : 'whatsapp.attendance.checked_out',
            [
                'name' => $user->name,
                'time' => $record->recorded_at->copy()->timezone(AttendanceRecord::timezone())->format('H:i'),
            ],
            $user->locale,
        );

        $result = $this->client->send($to, WhatsAppMessage::make('attendance_confirmation', [], $text), useTemplate: false);

        if (! $result->success) {
            Log::channel('whatsapp')->warning('[WhatsApp] Attendance confirmation failed', [
                'to' => PhoneNumber::mask($to),
                'status' => $result->status,
                'error' => $result->error,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $location
     */
    protected function isValidLocation(?array $location): bool
    {
        if ($location === null || ! is_numeric($location['latitude'] ?? null) || ! is_numeric($location['longitude'] ?? null)) {
            return false;
        }

        return abs((float) $location['latitude']) <= 90 && abs((float) $location['longitude']) <= 180;
    }

    protected function limit(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
