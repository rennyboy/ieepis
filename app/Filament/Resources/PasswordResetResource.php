<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PasswordResetResource\Pages;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PasswordResetResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Password Resets';

    protected static ?string $slug = 'password-resets';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->label('School')
                    ->relationship('employee.school', 'name')
                    ->visible(fn () => Auth::user()?->hasRole(['super-admin', 'sdo-admin'])),
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn (): array => \Illuminate\Support\Facades\Cache::remember('iEEPIS.roles_select_options', 3600, fn () => Role::query()->orderBy('name')->pluck('name', 'name')->toArray()))
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('roles', fn (Builder $q) => $q->where('name', $data['value']));
                    }),
            ])
            ->actions([
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading('Reset User Password')
                    ->modalDescription(fn (User $record) => "Set a new password for {$record->email}.")
                    ->form([
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->minLength(8),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required()
                            ->same('new_password'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->password = Hash::make($data['new_password']);
                        $record->save();

                        Notification::make()
                            ->title('Password reset')
                            ->body("Password for {$record->email} has been reset by " . Auth::user()?->name . '.')
                            ->sendToDatabase(collect([$record]));

                        Notification::make()->title('Password reset successfully')->success()->send();
                    })
                    ->visible(fn ($record) => $record instanceof User
                        && ! $record->hasRole('super-admin')
                        && (Auth::user()?->hasRole('super-admin'))),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasswordResets::route('/'),
        ];
    }

    public static function can(string $action, ?Model $record = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        // Only super-admin and sdo-admin can view this list, but only super-admin can actually reset as defined in the action visibility
        if ($action === 'viewAny' || $action === 'view') {
            return $user->hasRole(['super-admin', 'sdo-admin']);
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();

        if (! $user instanceof User) {
            return $query->whereRaw('1=0');
        }

        // Exclude super admins from the list so they can't be reset
        $query->whereDoesntHave('roles', function (Builder $q) {
            $q->where('name', 'super-admin');
        });

        return $query->when(
            $user->hasRole(['sdo-admin', 'school-admin']) && $user->school_id,
            fn (Builder $q) => $q->whereHas('employee', fn (Builder $eq) => $eq->where('school_id', $user->school_id)),
        );
    }
}
