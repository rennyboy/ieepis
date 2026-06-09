<?php

namespace App\Filament\Resources;

use App\Enums\PhysicalCountStatus;
use App\Filament\Resources\PpePhysicalCountResource\Pages;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\PpePhysicalCount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PpePhysicalCountResource extends Resource
{
    protected static ?string $model = PpePhysicalCount::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'PPE Module';

    protected static ?string $navigationLabel = 'Physical Count';

    protected static ?string $modelLabel = 'Physical Count';

    protected static ?string $pluralModelLabel = 'Physical Counts';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $query = parent::getEloquentQuery()
            ->with(['school', 'conductedByEmployee', 'verifiedByEmployee']);

        $query->when(
            fn () => $user->hasRole('school-admin'),
            fn (Builder $q) => $q->where('school_id', $user->school_id),
        );

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Physical Count Information')
                ->schema([
                    Forms\Components\TextInput::make('count_number')
                        ->label('Count No.')
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenOn('create'),

                    Forms\Components\Select::make('school_id')
                        ->label('Office / School')
                        ->relationship('school', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('location')
                        ->label('Building / Location')
                        ->placeholder('e.g. Main Building, ICT Lab')
                        ->maxLength(255),

                    Forms\Components\DatePicker::make('inventory_date')
                        ->label('Inventory Date')
                        ->required()
                        ->default(now()),

                    Forms\Components\TextInput::make('inventory_period')
                        ->label('Inventory Period')
                        ->placeholder('e.g. FY2026 Q1')
                        ->maxLength(100),

                    Forms\Components\Select::make('conducted_by')
                        ->label('Conducted By')
                        ->relationship(
                            'conductedByEmployee',
                            'full_name',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\Select::make('verified_by')
                        ->label('Verified By')
                        ->relationship(
                            'verifiedByEmployee',
                            'full_name',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\Select::make('status')
                        ->options(PhysicalCountStatus::options())
                        ->default(PhysicalCountStatus::Draft)
                        ->required()
                        ->disabled(fn (?PpePhysicalCount $record) => $record && $record->status !== PhysicalCountStatus::Draft),

                    Forms\Components\Textarea::make('remarks')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 2]),

            Forms\Components\Section::make('PPE Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('equipment_id')
                                ->label('Link Equipment (optional)')
                                ->options(function (Forms\Get $get) {
                                    $schoolId = $get('../../school_id');
                                    return Equipment::query()
                                        ->when($schoolId, fn (Builder $q) => $q->where('school_id', $schoolId))
                                        ->limit(100)
                                        ->get()
                                        ->mapWithKeys(fn (Equipment $e) => [
                                            $e->id => trim("{$e->brand} {$e->model}") . " ({$e->property_no})",
                                        ]);
                                })
                                ->searchable()
                                ->nullable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                    if (! $state) {
                                        return;
                                    }
                                    $equipment = Equipment::find($state);
                                    if (! $equipment) {
                                        return;
                                    }
                                    $set('article', $equipment->equipment_type ?? $equipment->category ?? '');
                                    $set('description', trim("{$equipment->brand} {$equipment->model} - {$equipment->specifications}"));
                                    $set('property_number', $equipment->property_no ?? '');
                                    $set('unit_of_measure', $equipment->unit_of_measure ?? 'unit');
                                    $set('unit_value', $equipment->acquisition_cost ?? 0);
                                })
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('article')
                                ->label('Article')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('property_number')
                                ->label('Property Number')
                                ->required()
                                ->maxLength(100)
                                ->distinct(),

                            Forms\Components\TextInput::make('unit_of_measure')
                                ->label('Unit of Measure')
                                ->required()
                                ->default('pcs')
                                ->maxLength(50),

                            Forms\Components\TextInput::make('unit_value')
                                ->label('Unit Value')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::computeVariance($set, $get)),

                            Forms\Components\TextInput::make('quantity_property_card')
                                ->label('Qty per Property Card')
                                ->integer()
                                ->minValue(0)
                                ->required()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::computeVariance($set, $get)),

                            Forms\Components\TextInput::make('quantity_physical_count')
                                ->label('Qty per Physical Count')
                                ->integer()
                                ->minValue(0)
                                ->required()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::computeVariance($set, $get)),

                            Forms\Components\TextInput::make('shortage_quantity')
                                ->label('Shortage Qty')
                                ->disabled()
                                ->dehydrated(true)
                                ->default(0),

                            Forms\Components\TextInput::make('shortage_value')
                                ->label('Shortage Value')
                                ->disabled()
                                ->dehydrated(true)
                                ->default(0)
                                ->prefix('₱'),

                            Forms\Components\TextInput::make('overage_quantity')
                                ->label('Overage Qty')
                                ->disabled()
                                ->dehydrated(true)
                                ->default(0),

                            Forms\Components\TextInput::make('overage_value')
                                ->label('Overage Value')
                                ->disabled()
                                ->dehydrated(true)
                                ->default(0)
                                ->prefix('₱'),

                            Forms\Components\TextInput::make('remarks')
                                ->label('Remarks')
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])
                        ->columns(['default' => 4])
                        ->addActionLabel('Add PPE Item')
                        ->reorderable(false)
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => ($state['article'] ?? '') . ' — ' . ($state['property_number'] ?? ''))
                        ->defaultItems(0),
                ]),
        ]);
    }

    /**
     * Reactively compute shortage/overage from current repeater-item state.
     */
    private static function computeVariance(Forms\Set $set, Forms\Get $get): void
    {
        $card = (int) $get('quantity_property_card');
        $count = (int) $get('quantity_physical_count');
        $unitValue = (float) $get('unit_value');

        if ($count < $card) {
            $shortageQty = $card - $count;
            $set('shortage_quantity', $shortageQty);
            $set('shortage_value', round($shortageQty * $unitValue, 2));
            $set('overage_quantity', 0);
            $set('overage_value', 0);
        } elseif ($count > $card) {
            $overageQty = $count - $card;
            $set('overage_quantity', $overageQty);
            $set('overage_value', round($overageQty * $unitValue, 2));
            $set('shortage_quantity', 0);
            $set('shortage_value', 0);
        } else {
            $set('shortage_quantity', 0);
            $set('shortage_value', 0);
            $set('overage_quantity', 0);
            $set('overage_value', 0);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('count_number')
                    ->label('Count No.')
                    ->fontFamily('mono')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inventory_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('school.name')
                    ->label('Office / School')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PhysicalCountStatus $state): string => $state->label())
                    ->color(fn (PhysicalCountStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('conductedByEmployee.full_name')
                    ->label('Conducted By')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_sum_shortage_value')
                    ->label('Total Shortage')
                    ->sum('items', 'shortage_value')
                    ->money('PHP')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('items_sum_overage_value')
                    ->label('Total Overage')
                    ->sum('items', 'overage_value')
                    ->money('PHP')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PhysicalCountStatus::options()),

                Tables\Filters\SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('inventory_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('inventory_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('inventory_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PpePhysicalCount $record) => $record->status === PhysicalCountStatus::Draft),
                Tables\Actions\Action::make('print_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (PpePhysicalCount $record): string => route('ppe-physical-count.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (PpePhysicalCount $record): string => route('ppe-physical-count.excel', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPpePhysicalCounts::route('/'),
            'create' => Pages\CreatePpePhysicalCount::route('/create'),
            'edit' => Pages\EditPpePhysicalCount::route('/{record}/edit'),
            'view' => Pages\ViewPpePhysicalCount::route('/{record}'),
        ];
    }
}
