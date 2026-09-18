<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyVisit extends Model
{
    /** @use HasFactory<\Database\Factories\DailyVisitFactory> */
    use HasFactory;

    protected $casts = [
        'visit_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $fillable = [
        'user_id',
        'farmer_id',
        'visit_date',
        'client_name',
        'location_text',
        'latitude',
        'longitude',
        'client_phone',
        'visit_reason',
        'visit_results',
        'visit_photo',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
