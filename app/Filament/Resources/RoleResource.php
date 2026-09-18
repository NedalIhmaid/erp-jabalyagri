<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Services\AdminSafetyService;
use App\Services\AuditLogger;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as BaseRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Spatie\Permission\Models\Role;

class RoleResource extends BaseRoleResource
{
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.roles');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.roles');
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Role $record): bool => app(AdminSafetyService::class)->shouldHideRoleDelete($record))
                    ->using(function (Role $record): bool {
                        app(AdminSafetyService::class)->ensureRoleDeletionAllowed($record);

                        $before = app(AuditLogger::class)->roleState($record->fresh('permissions'));
                        $result = $record->delete();

                        if ($result) {
                            app(AuditLogger::class)->logRoleDeleted($record, auth()->user(), $before);
                        }

                        return $result;
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                        $records->each(function (Role $record) use ($action): void {
                            try {
                                app(AdminSafetyService::class)->ensureRoleDeletionAllowed($record);

                                $before = app(AuditLogger::class)->roleState($record->fresh('permissions'));

                                if (! $record->delete()) {
                                    $action->reportBulkProcessingFailure();

                                    return;
                                }

                                app(AuditLogger::class)->logRoleDeleted($record, auth()->user(), $before);
                            } catch (Halt) {
                                $action->reportBulkProcessingFailure();
                            }
                        });
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
