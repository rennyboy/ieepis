<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Models\Division;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DistrictResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('District Information')
                ->icon('heroicon-o-map')
                ->schema([
                    Forms\Components\Select::make('division_id')
                        ->label('Division')
                        ->relationship('division', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('name')
                        ->label('District Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('code')
                        ->label('District Code')
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('region')
                        ->label('Region')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('District Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('division.name')
                    ->label('Division')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region'),
                Tables\Columns\TextColumn::make('schools_count')
                    ->label('Schools')
                    ->badge()
                    ->color('success')
                    ->counts('schools'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('division_id')
                    ->label('Division')
                    ->relationship('division', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }

    /**
     * Only Super Admin and Division Admin can manage districts.
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

        // Division Admins only see their own division's districts
        if ($user instanceof User && $user->hasRole('division-admin') && $user->division_id) {
            $divisionId = $user->division_id;
            $query->where(fn (Builder $q) => $q->where('division_id', $divisionId));
        }

        return $query;
    }
}
