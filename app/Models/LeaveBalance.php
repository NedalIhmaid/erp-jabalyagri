<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'annual_total',
        'annual_used',
        'sick_total',
        'sick_used',
        'marriage_used',
        'maternity_used',
        'bereavement_used',
    ];

    protected $casts = [
        'year' => 'integer',
        'annual_total' => 'decimal:1',
        'annual_used' => 'decimal:1',
        'sick_total' => 'decimal:1',
        'sick_used' => 'decimal:1',
        'marriage_used' => 'boolean',
        'maternity_used' => 'integer',
        'bereavement_used' => 'integer',
    ];

    // Computed attributes
    public function getAnnualRemainingAttribute(): float
    {
        return max(0, (float) $this->annual_total - (float) $this->annual_used);
    }

    public function getSickRemainingAttribute(): float
    {
        return max(0, (float) $this->sick_total - (float) $this->sick_used);
    }

    public function getMarriageRemainingAttribute(): int
    {
        return $this->marriage_used ? 0 : 3;
    }

    public function getMaternityRemainingAttribute(): int
    {
        return max(0, 70 - $this->maternity_used);
    }

    public function getBereavementRemainingAttribute(): int
    {
        return max(0, 3 - ($this->bereavement_used % 4)); // 3 days per event
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
