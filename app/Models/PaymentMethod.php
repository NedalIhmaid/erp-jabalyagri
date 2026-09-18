<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PaymentMethod extends Model
{
    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Request-level cache of all rows keyed by `key`. */
    protected static ?Collection $lookupCache = null;

    protected static function booted(): void
    {
        $clear = fn () => static::$lookupCache = null;
        static::saved($clear);
        static::deleted($clear);
    }

    /** Localized display name based on the active locale. */
    public function getLabelAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->name_ar ?: $this->name_en)
            : ($this->name_en ?: $this->name_ar);
    }

    /** All payment methods keyed by `key`, memoized for the request. */
    public static function lookup(): Collection
    {
        return static::$lookupCache ??= static::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('key');
    }

    /** Active options for a Select: `key => localized label`, ordered. */
    public static function options(): array
    {
        return static::lookup()
            ->filter(fn (self $m) => $m->is_active)
            ->mapWithKeys(fn (self $m) => [$m->key => $m->label])
            ->all();
    }

    /** Localized label for a stored key (includes inactive so old records still render). */
    public static function labelFor(?string $key): ?string
    {
        if (blank($key)) {
            return null;
        }

        return static::lookup()->get($key)?->label ?? $key;
    }

    /** Badge color for a stored key. */
    public static function colorFor(?string $key): ?string
    {
        if (blank($key)) {
            return null;
        }

        return static::lookup()->get($key)?->color ?? 'gray';
    }
}
