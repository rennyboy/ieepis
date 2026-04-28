<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Resources\SchoolResource\RelationManagers;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\District;
use App\Models\Division;
use App\Models\User;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Organization';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('School Identification')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('School / Office Name')
                        ->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('school_code')
                        ->label('School Code')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('school_id_number')
                        ->label('School ID Number'),
                    Forms\Components\Select::make('governance_level')
                        ->label('Governance Level')
                        ->options(['Central' => 'Central', 'Regional' => 'Regional', 'SDO' => 'School Division Office', 'School' => 'School'])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                        ->default('active')->required(),
                ])->columns(['default' => 2]),

            Forms\Components\Section::make('Location')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\TextInput::make('region')->label('Regional Office'),
                    Forms\Components\Select::make('division') // Kept 'division' as the string field for legacy compatibility, but using Division model for options
                        ->label('Division')
                        ->options(fn() => Division::pluck('name', 'name'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Forms\Set $set) => $set('district_id', null)),
                    Forms\Components\Select::make('district_id')
                        ->label('District')
                        ->relationship('district', 'name')
                        ->options(function (Forms\Get $get) {
                            $divisionName = $get('division');
                            return District::query()
                                ->when($divisionName, fn($q) => $q->whereHas('division', fn($dq) => $dq->where('name', $divisionName)))
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->exists('districts', 'id')
                        ->rules([
                            fn (Forms\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $divisionName = $get('division');
                                if ($divisionName) {
                                    $district = District::find($value);
                                    if ($district && $district->division->name !== $divisionName) {
                                        $fail("The selected district must be within the {$divisionName} division.");
                                    }
                                }
                            },
                        ])
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('province')->label('Province')->required(),
                    Forms\Components\TextInput::make('city_municipality')->label('City / Municipality')->required(),
                    Forms\Components\TextInput::make('barangay')->label('Barangay'),
                    Forms\Components\TextInput::make('street')->label('Street'),
                    Forms\Components\TextInput::make('legislative_district')->label('Legislative District'),
                    Forms\Components\TextInput::make('psgc')->label('PSGC Code'),
                    Forms\Components\TextInput::make('latitude')->label('Latitude')->numeric(),
                    Forms\Components\TextInput::make('longitude')->label('Longitude')->numeric(),
                    Forms\Components\TextInput::make('travel_time_minutes')
                        ->label('Travel Time to Nearest City Center (minutes)')->numeric(),
                ])->columns(['default' => 3]),

            Forms\Components\Section::make('Contact Information')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('head_name')->label('School Head / Principal'),
                    Forms\Components\TextInput::make('head_email')->label('Head Email')->email(),
                    Forms\Components\TextInput::make('head_mobile')->label('Head Mobile'),
                    Forms\Components\TextInput::make('admin_staff_name')->label('Inventory Clerk / Admin Staff'),
                    Forms\Components\TextInput::make('admin_staff_email')->label('Admin Staff Email')->email(),
                    Forms\Components\TextInput::make('admin_staff_mobile')->label('Admin Staff Mobile'),
                    Forms\Components\TextInput::make('email')->label('School Email')->email(),
                    Forms\Components\TextInput::make('landline')->label('Landline'),
                    Forms\Components\TextInput::make('mobile_1')->label('Mobile 1'),
                    Forms\Components\TextInput::make('mobile_2')                        ->label('Mobile 2'),
                ])->columns(['default' => 3]),

            Forms\Components\Section::make('Classification & Accessibility')
                ->icon('heroicon-o-map')
                ->schema([
                    Forms\Components\Toggle::make('is_very_remote')->label('Considered Very Remote'),
                    Forms\Components\Select::make('is_gidca')
                        ->label('GIDCA Classification')
                        ->options(['None' => 'None', 'Geographically Isolated' => 'Geographically Isolated', 'Disadvantaged' => 'Disadvantaged', 'Conflict-Affected' => 'Conflict-Affected']),
                    Forms\Components\Textarea::make('recent_developments')
                        ->label('Recent Developments')->rows(3)->columnSpanFull(),
                ])->columns(['default' => 2]),

            Forms\Components\Section::make('Logo')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->image()->directory('schools/logos')
                        ->imagePreviewHeight('100')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('division')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('school_code')->badge()->color('info')->sortable(),
                Tables\Columns\TextColumn::make('district')->searchable(),
                Tables\Columns\TextColumn::make('city_municipality')->label('Municipality'),
                Tables\Columns\TextColumn::make('head_name')->label('School Head')->searchable(),
                Tables\Columns\TextColumn::make('equipment_count')
                    ->label('Equipment')
                    ->badge()->color('primary')
                    ->getStateUsing(fn (School $record) => $record->equipment()->count()),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Personnel')
                    ->badge()->color('warning')
                    ->counts('employees'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('division')
                    ->label('Division')
                    ->options(fn() => Division::pluck('name', 'name')),
                Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive']),
                Tables\Filters\SelectFilter::make('governance_level')
                    ->options(['Central' => 'Central', 'Regional' => 'Regional', 'SDO' => 'SDO', 'School' => 'School']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('School Profile')->schema([
                Infolists\Components\ImageEntry::make('logo')->circular()->height(80),
                Infolists\Components\TextEntry::make('name')->label('School Name')->weight('bold'),
                Infolists\Components\TextEntry::make('school_code')->badge(),
                Infolists\Components\TextEntry::make('governance_level'),
                Infolists\Components\TextEntry::make('district'),
                Infolists\Components\TextEntry::make('city_municipality')->label('Municipality'),
                Infolists\Components\TextEntry::make('province'),
                Infolists\Components\TextEntry::make('head_name')->label('School Head'),
                Infolists\Components\TextEntry::make('email'),
                Infolists\Components\TextEntry::make('mobile_1'),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])->columns(['default' => 3]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmployeesRelationManager::class,
            RelationManagers\EquipmentRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
            RelationManagers\InternetConnectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'view'   => Pages\ViewSchool::route('/{record}'),
            'edit'   => Pages\EditSchool::route('/{record}/edit'),
        ];
    }

    public static function can(string $action, ?\Illuminate\Database\Eloquent\Model $record = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) return false;

        if ($action === 'create') {
            return $user->hasRole(['super-admin', 'division-admin']);
        }

        return parent::can($action, $record);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();

        if (!$user) return $query;

        // School admins and technicians see only their assigned school
        $query->when(
            fn() => $user->hasRole(["school-admin", "technician"]),
            fn(Builder $q) => $q->where("id", $user->school_id),
        );

        // Division Admins see schools in their division
        $query->when(
            fn() => $user->hasRole("division-admin") && $user->division,
            fn(Builder $q) => $q->where("division", $user->division),
        );

        return $query;
    }
}