<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Gender;
use App\Services\WhatsApp\PhoneNumber;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'manager_id', 'is_active', 'locale', 'hire_date', 'gender'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const SALES_APPROVER_ROLES = [
        'warehouse_keeper',
        'sales_manager',
        'purchasing_manager',
        'financial_manager',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'hire_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function hasSalesApprovalRole(): bool
    {
        return $this->hasAnyRole(self::SALES_APPROVER_ROLES);
    }

    /**
     * Inactive users keep their history but stop receiving WhatsApp messages.
     */
    public function routeNotificationForWhatsapp(): ?string
    {
        return $this->is_active ? PhoneNumber::normalize($this->phone) : null;
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function dailyVisits(): HasMany
    {
        return $this->hasMany(DailyVisit::class);
    }

    public function salesRequests(): HasMany
    {
        return $this->hasMany(SalesApprovalRequest::class);
    }

    public function hrRequests(): HasMany
    {
        return $this->hasMany(HrRequest::class);
    }
}
