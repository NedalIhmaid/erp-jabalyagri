<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    protected static ?string $slug = 'settings';

    public static function getLabel(): string
    {
        return __('general.settings');
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['fi-settings-form'];
    }

    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()
            ->disabled()
            ->autofocus(false);
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->disabled();
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label(__('general.phone'))
            ->tel()
            ->maxLength(20)
            ->disabled();
    }

    protected function getLocaleFormComponent(): Component
    {
        return Select::make('locale')
            ->label('Language')
            ->options([
                'ar' => 'العربية',
                'en' => 'English',
            ])
            ->required();
    }

    protected function getDirectManagerFormComponent(): Component
    {
        return TextInput::make('direct_manager')
            ->label(__('general.direct_manager'))
            ->formatStateUsing(fn (): string => auth()->user()?->manager?->name ?? __('general.not_available'))
            ->disabled()
            ->dehydrated(false)
            ->visible(fn (): bool => auth()->user()?->hasRole('engineer') ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPhoneFormComponent(),
                $this->getDirectManagerFormComponent(),
                $this->getLocaleFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
