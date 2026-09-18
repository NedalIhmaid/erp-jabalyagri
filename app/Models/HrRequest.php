<?php

namespace App\Models;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrRequest extends Model
{
    /** @use HasFactory<\Database\Factories\HrRequestFactory> */
    use HasFactory;

    protected $casts = [
        'type' => HrRequestType::class,
        'status' => HrRequestStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_days' => 'decimal:1',
        'manager_action_at' => 'datetime',
        'gm_action_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'duration_days',
        'reason',
        'attachment',
        'status',
        'manager_id',
        'manager_action_at',
        'manager_comments',
        'gm_action_at',
        'gm_comments',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
