<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMaterial extends Model
{
    protected $fillable = ['name', 'unit', 'company_material_category_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompanyMaterialCategory::class, 'company_material_category_id');
    }
}
