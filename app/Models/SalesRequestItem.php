<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\SalesRequestItemFactory> */
    use HasFactory;

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected $fillable = [
        'sales_approval_request_id',
        'product_id',
        'company_material_id',
        'product_unit_id',
        'product_name',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'notes',
    ];

    public function salesApprovalRequest(): BelongsTo
    {
        return $this->belongsTo(SalesApprovalRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function companyMaterial(): BelongsTo
    {
        return $this->belongsTo(CompanyMaterial::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function getDisplayProductNameAttribute(): string
    {
        return $this->companyMaterial?->name
            ?? $this->product?->name
            ?? (string) $this->product_name;
    }
}
