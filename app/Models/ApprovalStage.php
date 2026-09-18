<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStage extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalStageFactory> */
    use HasFactory;

    protected $casts = [
        'action' => ApprovalAction::class,
        'acted_at' => 'datetime',
    ];

    protected $fillable = [
        'sales_approval_request_id',
        'stage_number',
        'role',
        'approver_id',
        'action',
        'comments',
        'acted_at',
    ];

    public function salesApprovalRequest(): BelongsTo
    {
        return $this->belongsTo(SalesApprovalRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
