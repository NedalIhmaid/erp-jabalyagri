<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum HrRequestStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case ManagerApproved = 'manager_approved';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => __('general.pending'),
            self::ManagerApproved => __('hr.status.manager_approved'),
            self::Approved => __('general.approved'),
            self::Rejected => __('general.rejected'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::ManagerApproved => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::ManagerApproved => 'heroicon-o-check-badge',
            self::Approved => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }
}
