<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProjectType: string implements HasColor, HasIcon, HasLabel
{
    case Farm       = 'farm';
    case Nursery    = 'nursery';
    case Greenhouse = 'greenhouse';
    case OpenField  = 'open_field';
    case Orchard    = 'orchard';
    case Other      = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Farm       => __('sales.project_types.farm'),
            self::Nursery    => __('sales.project_types.nursery'),
            self::Greenhouse => __('sales.project_types.greenhouse'),
            self::OpenField  => __('sales.project_types.open_field'),
            self::Orchard    => __('sales.project_types.orchard'),
            self::Other      => __('sales.project_types.other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Farm       => 'success',
            self::Nursery    => 'info',
            self::Greenhouse => 'warning',
            self::OpenField  => 'primary',
            self::Orchard    => 'success',
            self::Other      => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Farm       => 'heroicon-o-home',
            self::Nursery    => 'heroicon-o-sparkles',
            self::Greenhouse => 'heroicon-o-building-storefront',
            self::OpenField  => 'heroicon-o-sun',
            self::Orchard    => 'heroicon-o-beaker',
            self::Other      => 'heroicon-o-ellipsis-horizontal',
        };
    }
}
