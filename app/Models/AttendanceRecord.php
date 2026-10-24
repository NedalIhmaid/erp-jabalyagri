<?php

namespace App\Models;

use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceRecord extends Model
{
    protected $casts = [
        'type' => AttendanceType::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'recorded_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'label',
        'provider_message_id',
        'recorded_at',
    ];

    public static function timezone(): string
    {
        return (string) config('services.whatsapp.attendance.timezone', config('app.timezone'));
    }

    /**
     * Limits the query to the current working day in the attendance timezone.
     */
    public function scopeToday(Builder $query): Builder
    {
        $start = Carbon::now(static::timezone())->startOfDay();

        return $query->whereBetween('recorded_at', [
            $start->copy()->utc(),
            $start->copy()->endOfDay()->utc(),
        ]);
    }

    public function mapUrl(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
