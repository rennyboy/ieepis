<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $linkedEmployeeId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading('Reset User Password')
                ->modalDescription(fn (User $record) => "Set a new password for {$record->email}.")
                ->form([
                    \Filament\Forms\Components\TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->minLength(8),
                    \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->required()
                        ->same('new_password'),
                ])
                ->action(function (User $record, array $data): void {
                    $record->password = \Illuminate\Support\Facades\Hash::make($data['new_password']);
                    $record->save();

                    \Filament\Notifications\Notification::make()
                        ->title('Password reset')
                        ->body("Password for {$record->email} has been reset by " . \Illuminate\Support\Facades\Auth::user()?->name . '.')
                        ->sendToDatabase(collect([$record]));

                    \Filament\Notifications\Notification::make()->title('Password reset successfully')->success()->send();
                })
                ->visible(fn ($record) => $record instanceof User
                    && ! $record->hasRole('super-admin')
                    && (\Illuminate\Support\Facades\Auth::user()?->hasRole('super-admin'))),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->linkedEmployeeId = $data['employee_id'] ?? null;
        unset($data['employee_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->linkedEmployeeId === null) {
            return;
        }

        $userId = $this->record->getKey();

        Employee::query()->where('user_id', $userId)->update(['user_id' => null]);

        Employee::query()
            ->whereKey($this->linkedEmployeeId)
            ->update(['user_id' => $userId]);
    }
}
