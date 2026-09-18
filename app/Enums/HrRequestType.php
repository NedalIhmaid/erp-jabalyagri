<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum HrRequestType: string implements HasColor, HasIcon, HasLabel
{
    case AnnualLeave = 'annual_leave';
    case SickLeave = 'sick_leave';
    case UnpaidLeave = 'unpaid_leave';
    case BereavementFirst = 'bereavement_first';
    case BereavementSecond = 'bereavement_second';
    case MarriageLeave = 'marriage_leave';
    case MaternityLeave = 'maternity_leave';
    case PaternityLeave = 'paternity_leave';
    case EarlyDeparture = 'early_departure';
    case DepartureFromAnnual = 'departure_from_annual';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AnnualLeave => __('hr.types.annual_leave'),
            self::SickLeave => __('hr.types.sick_leave'),
            self::UnpaidLeave => __('hr.types.unpaid_leave'),
            self::BereavementFirst => __('hr.types.bereavement_first'),
            self::BereavementSecond => __('hr.types.bereavement_second'),
            self::MarriageLeave => __('hr.types.marriage_leave'),
            self::MaternityLeave => __('hr.types.maternity_leave'),
            self::PaternityLeave => __('hr.types.paternity_leave'),
            self::EarlyDeparture => __('hr.types.early_departure'),
            self::DepartureFromAnnual => __('hr.types.departure_from_annual'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AnnualLeave => 'info',
            self::SickLeave => 'danger',
            self::UnpaidLeave => 'warning',
            self::BereavementFirst => 'danger',
            self::BereavementSecond => 'warning',
            self::MarriageLeave => 'success',
            self::MaternityLeave => 'success',
            self::PaternityLeave => 'success',
            self::EarlyDeparture => 'gray',
            self::DepartureFromAnnual => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::AnnualLeave => 'heroicon-o-sun',
            self::SickLeave => 'heroicon-o-heart',
            self::UnpaidLeave => 'heroicon-o-banknotes',
            self::BereavementFirst, self::BereavementSecond => 'heroicon-o-heart',
            self::MarriageLeave => 'heroicon-o-heart',
            self::MaternityLeave => 'heroicon-o-face-smile',
            self::PaternityLeave => 'heroicon-o-face-smile',
            self::EarlyDeparture, self::DepartureFromAnnual => 'heroicon-o-clock',
        };
    }
}
