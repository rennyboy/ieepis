<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovedUserResource\Pages;
use App\Models\ApprovedUser;
use App\Models\School;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApprovedUserResource extends Resource
{
    protected static ?string $model = ApprovedUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'User Approvals';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Registration Details')
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('role')
                        ->options([
                            'division-admin' => 'Division Admin',
                            'school-admin' => 'School Admin',
                            'technician' => 'Technician',
                        ])
                        ->required(),
                    Forms\Components\Select::make('school_id')
                        ->label('School')
                        ->options(School::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('pending')
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge(),
                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->hidden(fn (ApprovedUser $record) => $record->status === 'approved')
                    ->action(function (ApprovedUser $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'actioned_at' => now(),
                        ]);

                        // Sync with Users table if user already exists
                        $user = User::where('email', $record->email)->first();
                        if ($user) {
                            $user->update([
                                'approval_status' => 'approved',
                                'school_id' => $record->school_id,
                            ]);
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->hidden(fn (ApprovedUser $record) => $record->status === 'rejected')
                    ->requiresConfirmation()
                    ->action(function (ApprovedUser $record) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => Auth::id(),
                            'actioned_at' => now(),
                        ]);

                        $user = User::where('email', $record->email)->first();
                        if ($user) {
                            $user->update(['approval_status' => 'rejected']);
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovedUsers::route('/'),
            'create' => Pages\CreateApprovedUser::route('/create'),
            'edit' => Pages\EditApprovedUser::route('/{record}/edit'),
        ];
    }

    /**
     * Managed by Super Admin and Division Admin
     */
    public static function can(string $action, ?\Illuminate\Database\Eloquent\Model $record = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! ($user instanceof User)) {
            return false;
        }

        return $user->hasRole(['super-admin', 'division-admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user instanceof User && $user->hasRole('division-admin') && $user->division) {
            $query->where(fn (Builder $q) => $q->where('division', $user->division));
        }

        return $query;
    }
}
