<?php

namespace App\Models;

use App\Enums\ProductUnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'label',
        'unit_type',
        'unit_value',
        'price',
        'needs_price_review',
        'is_active',
    ];

    protected $casts = [
        'unit_type'          => ProductUnitType::class,
        'unit_value'         => 'decimal:3',
        'price'              => 'decimal:2',
        'needs_price_review' => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesRequestItems(): HasMany
    {
        return $this->hasMany(SalesRequestItem::class);
    }
}
