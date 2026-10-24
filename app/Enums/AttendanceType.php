<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AttendanceType: string implements HasColor, HasIcon, HasLabel
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CheckIn => __('attendance.check_in'),
            self::CheckOut => __('attendance.check_out'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CheckIn => 'success',
            self::CheckOut => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CheckIn => 'heroicon-o-arrow-right-end-on-rectangle',
            self::CheckOut => 'heroicon-o-arrow-left-start-on-rectangle',
        };
    }
}
