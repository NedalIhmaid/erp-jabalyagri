<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Enums\SalesRequestStatus;
use Database\Factories\SalesApprovalRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesApprovalRequest extends Model
{
    /** @use HasFactory<SalesApprovalRequestFactory> */
    use HasFactory;

    protected $casts = [
        'project_type' => ProjectType::class,
        'status' => SalesRequestStatus::class,
        'total_amount' => 'decimal:2',
    ];

    protected $fillable = [
        'request_number',
        'user_id',
        'warehouse_keeper_id',
        'client_name',
        'client_phone',
        'client_address',
        'region',
        'project_type',
        'project_size',
        'payment_method',
        'engineer_notes',
        'total_amount',
        'current_stage',
        'status',
        'rejection_reason',
        'rejected_by',
        'returned_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouseKeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_keeper_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'key');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesRequestItem::class);
    }

    public function approvalStages(): HasMany
    {
        return $this->hasMany(ApprovalStage::class)->orderBy('stage_number');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
