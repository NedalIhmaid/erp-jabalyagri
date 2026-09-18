<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SalesRequestStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => __('general.pending'),
            self::InProgress => __('general.in_progress'),
            self::Approved => __('general.approved'),
            self::Rejected => __('general.rejected'),
            self::Cancelled => __('general.cancelled'),
            self::Returned => __('general.returned'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InProgress => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'danger',
            self::Returned => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Approved => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Returned => 'heroicon-o-arrow-uturn-left',
        };
    }
}
