<?php

namespace App\Console\Commands;

use App\Enums\AttendanceType;
use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendAttendanceReminders extends Command
{
    protected $signature = 'attendance:remind {type : check_in or check_out}';

    protected $description = 'Remind employees over WhatsApp to share their location for check-in or check-out';

    /**
     * Hourly permissions do not take the employee out for the whole day.
     */
    private const PARTIAL_DAY_TYPES = [
        HrRequestType::EarlyDeparture,
        HrRequestType::DepartureFromAnnual,
    ];

    public function handle(WhatsAppClient $client): int
    {
        $type = AttendanceType::tryFrom((string) $this->argument('type'));

        if (! $type) {
            $this->error('Type must be check_in or check_out.');

            return self::INVALID;
        }

        if (! WhatsAppClient::isConfigured()) {
            $this->warn('WhatsApp is not configured; no reminders sent.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($this->recipients($type) as $user) {
            $to = $user->routeNotificationForWhatsapp();

            if (! $to) {
                continue;
            }

            // Inside the 24-hour session a free-form message is delivered and
            // costs nothing; outside it only an approved template gets through.
            $useTemplate = config('services.whatsapp.mode') !== 'text' && ! $user->hasOpenWhatsAppSession();
            $message = $this->message($user, $type);

            $result = $client->send($to, $message, $useTemplate, (int) config('services.whatsapp.retries', 2));

            if ($result->success) {
                $sent++;

                continue;
            }

            $failed++;

            Log::channel('whatsapp')->warning('[WhatsApp] Attendance reminder failed', [
                'to' => PhoneNumber::mask($to),
                'type' => $type->value,
                'template' => $useTemplate ? $client->templateLabel($message) : null,
                'status' => $result->status,
                'error' => $result->error,
            ]);
        }

        $this->info("Sent {$sent} {$type->value} reminder(s), {$failed} failed.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    protected function recipients(AttendanceType $type): Collection
    {
        $today = Carbon::now(AttendanceRecord::timezone())->toDateString();
        $excludedRoles = config('services.whatsapp.attendance.excluded_roles', []);

        $query = User::query()
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->when($excludedRoles !== [], fn (Builder $query) => $query->whereDoesntHave(
                'roles',
                fn (Builder $roles) => $roles->whereIn('name', $excludedRoles),
            ))
            ->whereDoesntHave('hrRequests', fn (Builder $leave) => $leave
                ->where('status', HrRequestStatus::Approved)
                ->whereNotIn('type', self::PARTIAL_DAY_TYPES)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today));

        if ($type === AttendanceType::CheckIn) {
            return $query
                ->whereDoesntHave('attendanceRecords', fn (Builder $records) => $records->today())
                ->get();
        }

        return $query
            ->whereHas('attendanceRecords', fn (Builder $records) => $records->today()->where('type', AttendanceType::CheckIn))
            ->whereDoesntHave('attendanceRecords', fn (Builder $records) => $records->today()->where('type', AttendanceType::CheckOut))
            ->get();
    }

    protected function message(User $user, AttendanceType $type): WhatsAppMessage
    {
        $templateKey = $type === AttendanceType::CheckIn
            ? 'attendance_check_in_reminder'
            : 'attendance_check_out_reminder';

        return WhatsAppMessage::make(
            $templateKey,
            [$user->name],
            __('whatsapp.attendance.'.$templateKey, ['name' => $user->name], $user->locale),
        );
    }
}
