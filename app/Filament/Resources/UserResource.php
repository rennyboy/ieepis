<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * UserResource — manages auth identities.
 *
 * Personal/organizational data lives on Employee. The form picks an existing
 * Employee (by full_name) and the resulting User is auto-linked via
 * `employees.user_id`. Reads of `$user->name` / `$user->school_id` delegate
 * through the employee.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('employee_id')
                ->label('Linked Employee')
                ->helperText('User identity = this employee. Personal info (name, school) is edited via the Employee record.')
                ->options(function (?Model $record): array {
                    /** @var User|null $authUser */
                    $authUser = Auth::user();

                    $query = Employee::query()
                        ->whereDoesntHave('user', fn (Builder $q) => $record ? $q->where('users.id', '!=', $record->id) : null)
                        ->where('status', 'active');

                    if ($authUser && ! $authUser->hasRole(['super-admin', 'sdo-admin'])) {
                        $query->where('school_id', $authUser->school_id);
                    }

                    return $query->orderBy('full_name')->pluck('full_name', 'id')->toArray();
                })
                ->searchable()
                ->preload()
                ->required()
                ->afterStateHydrated(function (Select $component, ?Model $record): void {
                    if ($record instanceof User) {
                        $component->state($record->employee?->id);
                    }
                })
                ->dehydrated(false),

            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->password()
                ->required()
                ->minLength(8)
                ->hiddenOn('edit')
                ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

            Forms\Components\Select::make('roles')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->visible(fn () => Auth::user()?->hasRole(['super-admin', 'sdo-admin']))
                ->disabled(fn () => ! Auth::user()?->hasRole(['super-admin', 'sdo-admin'])),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')->label('Name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('employee.school.name')->label('School')->searchable(),
                TextColumn::make('roles.name')->label('Roles')->badge(),
                TextColumn::make('approval_status')->badge()->colors([
                    'warning' => 'pending',
                    'success' => 'approved',
                    'danger' => 'rejected',
                ]),
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
                EditAction::make(),
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
                Action::make('reassignEmployee')
                    ->label('Reassign Employee')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Reassign User to a Different Employee')
                    ->form([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(fn (): array => Employee::query()
                                ->whereDoesntHave('user')
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->toArray())
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        Employee::query()->where('user_id', $record->id)->update(['user_id' => null]);
                        Employee::query()->whereKey($data['employee_id'])->update(['user_id' => $record->id]);

                        Notification::make()
                            ->title('User reassigned')
                            ->body("User {$record->email} is now linked to a different employee.")
                            ->sendToDatabase(\Illuminate\Support\Collection::make([$record]));

                        Notification::make()->title('User reassigned')->success()->send();
                    })
                    ->visible(fn ($record) => $record instanceof User && ! $record->hasRole('super-admin')),
                Action::make('assignRole')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('role')
                            ->options(fn (): array => \Illuminate\Support\Facades\Cache::remember('iEEPIS.roles_select_options', 3600, fn () => Role::query()->orderBy('name')->pluck('name', 'name')->toArray()))
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        if ($record instanceof User) {
                            $record->assignRole($data['role']);
                        }
                    })
                    ->visible(fn ($record) => $record instanceof User && ! $record->hasRole('super-admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function can(string $action, ?Model $record = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if (in_array($action, ['view', 'create', 'delete', 'forceDelete', 'restore'], true)) {
            return $user->hasRole(['super-admin', 'sdo-admin']);
        }

        if ($action === 'resetPassword') {
            return $user->hasRole('super-admin');
        }

        if ($action === 'edit') {
            if ($user->hasRole(['super-admin', 'sdo-admin'])) {
                return true;
            }

            return $user->hasRole('school-admin')
                && $record instanceof User
                && $record->id === $user->id;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        if (! $user instanceof User) {
            return $query->whereRaw('1=0');
        }

        return $query->when(
            $user->hasRole(['sdo-admin', 'school-admin']) && $user->school_id,
            fn (Builder $q) => $q->whereHas('employee', fn (Builder $eq) => $eq->where('school_id', $user->school_id)),
        );
    }
}
