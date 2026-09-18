<?php

namespace App\Filament\Pages;

use App\Filament\Resources\HrRequestResource;
use App\Filament\Resources\LeaveBalanceResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SalesApprovalRequestResource;
use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\HrRequest;
use App\Models\LeaveBalance;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class AuditLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.audit-log';

    protected static ?string $slug = 'audit-log';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.audit_log');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationSort(): int
    {
        return 4;
    }

    public function getHeading(): string
    {
        return __('audit.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with(['causer', 'subject']))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('audit.logged_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label(__('audit.actor'))
                    ->state(fn (AuditLog $record) => $record->causer?->name ?? __('audit.system'))
                    ->sortable(),

                TextColumn::make('log_name')
                    ->label(__('audit.domain'))
                    ->badge()
                    ->state(fn (AuditLog $record) => $this->getDomainLabel($record->log_name))
                    ->color(fn (AuditLog $record) => $this->getDomainColor($record->log_name))
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('audit.action'))
                    ->badge()
                    ->state(fn (AuditLog $record) => $this->getActionLabel($record->description))
                    ->color('gray'),

                TextColumn::make('subject_label')
                    ->label(__('audit.subject'))
                    ->state(fn (AuditLog $record) => $this->getSubjectLabel($record))
                    ->url(fn (AuditLog $record) => $this->getSubjectUrl($record))
                    ->color(fn (AuditLog $record) => filled($this->getSubjectUrl($record)) ? 'primary' : 'gray'),

                TextColumn::make('summary')
                    ->label(__('audit.summary'))
                    ->state(fn (AuditLog $record) => (string) $record->getExtraProperty('summary', '—'))
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = '%'.$search.'%';

                        return $query->where(function (Builder $query) use ($like): void {
                            $query
                                ->where('description', 'like', $like)
                                ->orWhereRaw("json_extract(properties, '$.summary') like ?", [$like])
                                ->orWhereRaw("json_extract(properties, '$.context.request_number') like ?", [$like])
                                ->orWhereRaw("json_extract(properties, '$.context.employee_name') like ?", [$like])
                                ->orWhereRaw("json_extract(properties, '$.context.user_name') like ?", [$like])
                                ->orWhereRaw("json_extract(properties, '$.context.role_name') like ?", [$like])
                                ->orWhereRaw("json_extract(properties, '$.context.subject_label') like ?", [$like])
                                ->orWhereHasMorph('causer', [User::class], fn (Builder $query) => $query->where('name', 'like', $like));
                        });
                    }),
            ])
            ->filters([
                Filter::make('logged_at')
                    ->label(__('audit.logged_at'))
                    ->form([
                        DatePicker::make('from')->label(__('general.from')),
                        DatePicker::make('until')->label(__('general.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),

                SelectFilter::make('log_name')
                    ->label(__('audit.domain'))
                    ->options($this->getDomainOptions()),

                SelectFilter::make('description')
                    ->label(__('audit.action'))
                    ->options($this->getActionOptions()),

                SelectFilter::make('causer_id')
                    ->label(__('audit.actor'))
                    ->options(User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query
                            ->where('causer_type', User::class)
                            ->where('causer_id', $data['value']);
                    }),

                SelectFilter::make('subject_type')
                    ->label(__('audit.subject_type'))
                    ->options($this->getSubjectTypeOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    protected function getDomainOptions(): array
    {
        return [
            'sales' => $this->getDomainLabel('sales'),
            'hr' => $this->getDomainLabel('hr'),
            'admin' => $this->getDomainLabel('admin'),
            'leave' => $this->getDomainLabel('leave'),
        ];
    }

    protected function getActionOptions(): array
    {
        $actions = [
            'sales.request.created',
            'sales.request.resubmitted',
            'sales.stage.approved',
            'sales.request.rejected',
            'sales.request.returned',
            'sales.request.deleted',
            'hr.request.submitted',
            'hr.request.updated',
            'hr.request.deleted',
            'hr.request.manager_approved',
            'hr.request.manager_rejected',
            'hr.request.approved',
            'hr.request.rejected',
            'admin.user.created',
            'admin.user.updated',
            'admin.user.deleted',
            'admin.role.created',
            'admin.role.updated',
            'admin.role.deleted',
            'leave.balance.adjusted',
            'leave.balance.deducted',
            'leave.balance.restored',
        ];

        return collect($actions)
            ->mapWithKeys(fn (string $action) => [$action => $this->getActionLabel($action)])
            ->all();
    }

    protected function getSubjectTypeOptions(): array
    {
        return [
            SalesApprovalRequest::class => __('audit.subjects.sales_request'),
            HrRequest::class => __('audit.subjects.hr_request'),
            User::class => __('audit.subjects.user'),
            Role::class => __('audit.subjects.role'),
            LeaveBalance::class => __('audit.subjects.leave_balance'),
        ];
    }

    protected function getDomainLabel(?string $domain): string
    {
        return match ($domain) {
            'sales' => __('general.sales'),
            'hr' => __('general.hr'),
            'admin' => __('general.settings'),
            'leave' => __('leave.title'),
            default => (string) str($domain ?? __('general.unknown'))->headline(),
        };
    }

    protected function getDomainColor(?string $domain): string
    {
        return match ($domain) {
            'sales' => 'info',
            'hr' => 'warning',
            'admin' => 'gray',
            'leave' => 'success',
            default => 'gray',
        };
    }

    protected function getActionLabel(string $description): string
    {
        return (string) str($description)->replace('.', ' ')->headline();
    }

    protected function getSubjectLabel(AuditLog $record): string
    {
        $subject = $record->subject;

        if ($subject instanceof SalesApprovalRequest) {
            return $subject->request_number;
        }

        if ($subject instanceof HrRequest) {
            return "#{$subject->id}";
        }

        if ($subject instanceof User) {
            return $subject->name;
        }

        if ($subject instanceof Role) {
            return $subject->name;
        }

        if ($subject instanceof LeaveBalance) {
            return trim(($subject->user?->name ?? __('general.unknown')).' / '.$subject->year);
        }

        return (string) Arr::get($record->properties?->toArray() ?? [], 'context.subject_label', __('general.unknown'));
    }

    protected function getSubjectUrl(AuditLog $record): ?string
    {
        $subject = $record->subject;

        if ($subject instanceof SalesApprovalRequest && SalesApprovalRequestResource::canView($subject)) {
            return SalesApprovalRequestResource::getUrl('view', ['record' => $subject]);
        }

        if ($subject instanceof HrRequest && HrRequestResource::canView($subject)) {
            return HrRequestResource::getUrl('view', ['record' => $subject]);
        }

        if ($subject instanceof User && UserResource::canView($subject)) {
            return UserResource::getUrl('view', ['record' => $subject]);
        }

        if ($subject instanceof Role && RoleResource::canView($subject)) {
            return RoleResource::getUrl('view', ['record' => $subject]);
        }

        if (
            $subject instanceof LeaveBalance &&
            LeaveBalanceResource::canAccess() &&
            LeaveBalanceResource::canEdit($subject)
        ) {
            return LeaveBalanceResource::getUrl('edit', ['record' => $subject]);
        }

        return null;
    }
}
