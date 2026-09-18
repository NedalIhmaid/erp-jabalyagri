<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyMaterialCategory extends Model
{
    protected $fillable = ['name', 'sort'];

    public function materials(): HasMany
    {
        return $this->hasMany(CompanyMaterial::class);
    }
}
