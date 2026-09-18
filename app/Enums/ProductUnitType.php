<?php

namespace App\Enums;

enum ProductUnitType: string
{
    case Milliliter = 'ml';
    case Liter      = 'L';
    case Gram       = 'g';
    case Kilogram   = 'kg';
    case Ton        = 'ton';
    case Sack       = 'sack';
    case Packet     = 'packet';
    case Unit       = 'unit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Milliliter => __('مل'),
            self::Liter      => __('لتر'),
            self::Gram       => __('غرام'),
            self::Kilogram   => __('كغم'),
            self::Ton        => __('طن'),
            self::Sack       => __('شوال'),
            self::Packet     => __('بكيت'),
            self::Unit       => __('وحدة'),
        };
    }
}
