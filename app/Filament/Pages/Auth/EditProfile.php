<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Custom profile page.
 *
 * - `name` is read-only: it's delegated from the linked Employee per the
 *   identity-unification model. To change the displayed name, edit the
 *   linked Employee record.
 * - Password change requires verification of the current password.
 */
class EditProfile extends BaseEditProfile
{
    public ?string $current_password = null;

    /**
     * Show the user's name as a non-editable placeholder, since it lives on
     * the linked Employee. Placeholders are not dehydrated, so this never
     * leaks into the save payload.
     */
    protected function getNameFormComponent(): Component
    {
        return Placeholder::make('name_display')
            ->label('Name')
            ->content(fn () => $this->getUser()->name ?: '—')
            ->helperText('Name is managed via your linked Employee record.');
    }

    /**
     * Add a current-password field. Required only if a new password is being
     * set; otherwise hidden.
     */
    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('current_password')
            ->label('Current password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->dehydrated(false)
            ->visible(fn (\Filament\Forms\Get $get): bool => filled($get('password')))
            ->rule(function () {
                return function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Hash::check((string) $value, (string) Auth::user()?->password)) {
                        $fail('The current password is incorrect.');
                    }
                };
            });
    }

    /**
     * Inject the current-password field between the email and password
     * components in the default form layout.
     */
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getCurrentPasswordFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile updated')
            ->body('Your changes have been saved.');
    }
}
