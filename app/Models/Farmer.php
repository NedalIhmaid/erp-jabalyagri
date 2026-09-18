<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Farmer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'location',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Request-level cache of all rows keyed by id. */
    protected static ?Collection $lookupCache = null;

    protected static function booted(): void
    {
        $clear = fn () => static::$lookupCache = null;
        static::saved($clear);
        static::deleted($clear);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(DailyVisit::class);
    }

    /** All farmers keyed by id, memoized for the request. */
    public static function lookup(): Collection
    {
        return static::$lookupCache ??= static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /** Active options for a Select: `id => name`, ordered. */
    public static function options(): array
    {
        return static::lookup()
            ->filter(fn (self $f) => $f->is_active)
            ->mapWithKeys(fn (self $f) => [$f->id => $f->name])
            ->all();
    }
}
