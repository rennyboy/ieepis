<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
 * UserResource for managing users in Filament admin panel
 *
 * Handles user management with role-based access control.
 * School Admins can only manage their own school's users.
 * Super Admins and SDO Admins can manage all users.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = "heroicon-o-users";

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make("name")->required()->maxLength(255),
            TextInput::make("email")
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make("password")
                ->password()
                ->required()
                ->minLength(8)
                ->hiddenOn(["edit"])
                ->dehydrateStateUsing(fn($state) => Hash::make($state)),
            Select::make("school_id")
                ->label("School")
                ->options(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    if (
                        $user instanceof User &&
                        $user->hasRole(["super-admin", "sdo-admin"])
                    ) {
                        /** @var Collection<int, School> $schools */
                        $schools = School::all();
                        return $schools->pluck("name", "id")->toArray();
                    } elseif ($user instanceof User && $user->school) {
                        return [$user->school->id => $user->school->name];
                    }
                    return [];
                })
                ->required()
                ->visible(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    return $user instanceof User &&
                        $user->hasRole(["super-admin", "sdo-admin"]);
                })
                ->disabled(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    return !(
                        $user instanceof User &&
                        $user->hasRole(["super-admin", "sdo-admin"])
                    );
                }),
            Forms\Components\Select::make("roles")
                ->multiple()
                ->relationship("roles", "name")
                ->preload()
                ->visible(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    return $user instanceof User &&
                        $user->hasRole(["super-admin", "sdo-admin"]);
                })
                ->disabled(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    return !(
                        $user instanceof User &&
                        $user->hasRole(["super-admin", "sdo-admin"])
                    );
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")->searchable(),
                TextColumn::make("email")->searchable(),
                TextColumn::make("school.name")->label("School")->searchable(),
                TextColumn::make("roles.name")->label("Roles")->badge(),
            ])
            ->filters([
                SelectFilter::make("school_id")
                    ->label("School")
                    ->options(function () {
                        /** @var Collection<int, School> $schools */
                        $schools = School::all();
                        return $schools->pluck("name", "id")->toArray();
                    })
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user instanceof User &&
                            $user->hasRole(["super-admin", "sdo-admin"]);
                    }),
                SelectFilter::make("role")
                    ->label("Role")
                    ->options(function () {
                        /** @var Collection<int, Role> $roles */
                        $roles = Role::all();
                        return $roles->pluck("name", "name")->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data["value"])) {
                            // Use whereHas to filter users by role without triggering IDE errors
                            return $query->whereHas("roles", function (
                                Builder $subQuery,
                            ) use ($data) {
                                $subQuery->where("name", $data["value"]);
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([
                EditAction::make(),
                Action::make("assignRole")
                    ->icon("heroicon-o-user-plus")
                    ->form([
                        Select::make("role")
                            ->options(function () {
                                /** @var Collection<int, Role> $roles */
                                $roles = Role::all();
                                return $roles->pluck("name", "name")->toArray();
                            })
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        if ($record instanceof User) {
                            $record->assignRole($data["role"]);
                        }
                    })
                    ->visible(
                        fn($record) => $record instanceof User &&
                            !$record->hasRole("super-admin"),
                    ),
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
            "index" => Pages\ListUsers::route("/"),
            "create" => Pages\CreateUser::route("/create"),
            "edit" => Pages\EditUser::route("/{record}/edit"),
        ];
    }

    public static function can(string $action, ?Model $record = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!($user instanceof User)) {
            return false;
        }

        if (
            $action === "view" ||
            $action === "create" ||
            $action === "delete" ||
            $action === "forceDelete" ||
            $action === "restore"
        ) {
            return $user->hasRole(["super-admin", "sdo-admin"]);
        }

        if ($action === "edit") {
            // Super Admins and SDO Admins can edit any user
            if ($user->hasRole(["super-admin", "sdo-admin"])) {
                return true;
            }

            // School Admins can only edit their own user profile
            if (
                $user->hasRole("school-admin") &&
                $record instanceof User &&
                $record->id === $user->id
            ) {
                return true;
            }

            return false;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if (!($user instanceof User)) {
            return $query->whereRaw("1=0");
        }

        // Scope users for SDO Admins to only see their school's users
        if ($user->hasRole("sdo-admin")) {
            return $query->where("school_id", $user->school_id);
        }

        // Scope users for School Admins to only see their own school's users
        if ($user->hasRole("school-admin")) {
            return $query->where("school_id", $user->school_id);
        }

        return $query;
    }
}
