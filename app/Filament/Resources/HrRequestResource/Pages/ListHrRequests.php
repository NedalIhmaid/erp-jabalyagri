<?php

namespace App\Filament\Resources\HrRequestResource\Pages;

use App\Filament\Resources\HrRequestResource;
use App\Filament\Widgets\LeaveBalanceStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListHrRequests extends ListRecords
{
    protected static string $resource = HrRequestResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LeaveBalanceStats::class,
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        if (! $user?->hasRole('sales_manager')) {
            return [];
        }

        return [
            'sales_manager' => Tab::make(__('general.my_requests'))
                ->query(fn (Builder $query): Builder => $query->where('user_id', $user->id)),
            'engineers' => Tab::make(__('general.engineer_requests'))
                ->query(fn (Builder $query): Builder => $query
                    ->where('manager_id', $user->id)
                    ->where('user_id', '!=', $user->id)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('general.create'))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['manager_id'] = auth()->user()->manager_id;
                    return $data;
                }),
        ];
    }
}
