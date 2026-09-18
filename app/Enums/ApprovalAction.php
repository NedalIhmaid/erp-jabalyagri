<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ApprovalAction: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Viewed = 'viewed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => __('general.pending'),
            self::Approved => __('general.approved'),
            self::Rejected => __('general.rejected'),
            self::Returned => __('general.returned'),
            self::Viewed => __('general.view'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Returned => 'warning',
            self::Viewed => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
            self::Returned => 'heroicon-o-arrow-uturn-left',
            self::Viewed => 'heroicon-o-eye',
        };
    }
}
