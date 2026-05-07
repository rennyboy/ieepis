<?php

namespace App\Filament\Resources;

use App\Enums\TransactionType;
use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AssignmentResource extends Resource
{
    protected static ?string $model = EquipmentAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'ICT Inventory';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Assignments';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assignment Details')
                ->schema([
                    Forms\Components\Select::make('school_id')
                        ->label('School')
                        ->relationship('school', 'name')
                        ->disabled(function () {
                            /** @var \App\Models\User $user */
                            $user = Auth::user();

                            return ! $user->hasRole('super-admin');
                        })
                        ->default(fn () => Auth::user()?->school_id)
                        ->dehydrated()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('equipment_id', null);
                            $set('employee_id', null);
                            $set('custodian_id', null);
                        })
                        ->required(),
                    Forms\Components\Select::make('equipment_id')
                        ->label('Equipment')
                        ->relationship(
                            'equipment',
                            'model',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->where('accountability_status', 'unassigned')
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (Equipment $record): string => "{$record->brand} {$record->model} ({$record->property_no})",
                        )
                        ->searchable(['brand', 'model', 'property_no', 'serial_number'])
                        ->preload()
                        ->required()
                        ->disabled(fn (Forms\Get $get) => blank($get('school_id')))
                        ->placeholder(fn (Forms\Get $get) => blank($get('school_id'))
                            ? 'Pick a school first'
                            : 'Select equipment')
                        ->disabledOn('edit'),
                    Forms\Components\Select::make('employee_id')
                        ->label('Accountable Officer')
                        ->relationship(
                            'employee',
                            'full_name',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->where('status', 'active')
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (Forms\Get $get) => blank($get('school_id')))
                        ->placeholder(fn (Forms\Get $get) => blank($get('school_id'))
                            ? 'Pick a school first'
                            : 'Select officer'),
                    Forms\Components\Select::make('custodian_id')
                        ->label('Custodian / End User (if different)')
                        ->relationship(
                            'custodian',
                            'full_name',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->where('status', 'active')
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Forms\Get $get) => blank($get('school_id')))
                        ->placeholder(fn (Forms\Get $get) => blank($get('school_id'))
                            ? 'Pick a school first'
                            : 'Optional')
                        ->nullable(),
                    Forms\Components\Select::make('transaction_type')
                        ->options(TransactionType::options())
                        ->required()
                        ->default(TransactionType::Issuance),
                    Forms\Components\Select::make('supporting_doc_type')
                        ->label('Supporting Document Type')
                        ->options([
                            'PAR' => 'PAR',
                            'ICS' => 'ICS',
                            'RRSP' => 'RRSP',
                            'RRPE' => 'RRPE',
                        ]),
                    Forms\Components\TextInput::make('supporting_doc_no')->label('Document No.'),
                    Forms\Components\DatePicker::make('assigned_at')
                        ->label('Date Assigned')
                        ->required()
                        ->default(now()),
                    Forms\Components\DatePicker::make('custodian_received_at')
                        ->label('Date Received by Custodian'),
                    Forms\Components\Placeholder::make('assigned_by_display')
                        ->label('Assigned By')
                        ->content(fn () => Auth::user()?->name ?? '—')
                        ->dehydrated(false),
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                ])
                ->columns(['default' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment.property_no')
                    ->label('Property No.')
                    ->fontFamily('mono')
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment.brand')->label('Brand'),
                Tables\Columns\TextColumn::make('equipment.model')
                    ->label('Model')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Accountable Officer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('custodian.full_name')->label('Custodian'),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Transaction')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state): string => $state->label())
                    ->color(fn (TransactionType $state): string => $state->color()),
                Tables\Columns\TextColumn::make('supporting_doc_type')
                    ->label('Doc Type')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('supporting_doc_no')->label('Doc No.'),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Assigned')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('returned_at')->label('Returned')->date(),
                Tables\Columns\TextColumn::make('assignedBy.name')
                    ->label('Assigned By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn (EquipmentAssignment $r) => $r->isActive()),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_only')
                    ->label('Active Only')
                    ->query(fn ($query) => $query->whereNull('returned_at'))
                    ->default(),
                Tables\Filters\SelectFilter::make('transaction_type')->options(TransactionType::options()),
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('School')
                    ->relationship('school', 'name')
                    ->visible(fn () => Auth::user()?->hasRole('super-admin')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('assigned_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['equipment', 'employee', 'custodian', 'school', 'assignedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    /**
     * Role-based authorization. SchoolScope already isolates rows per school;
     * this gate decides which actions each role can perform within their scope.
     *
     * - super-admin / sdo-admin: full access
     * - school-admin: view + create + update (return flow + corrections)
     * - technician: view only
     * - viewer: view only
     * - delete/forceDelete/restore: super-admin / sdo-admin only — assignment
     *   history is audit-bearing and should not be removed casually
     */
    public static function can(string $action, ?Model $record = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'sdo-admin'])) {
            return true;
        }

        if (in_array($action, ['view', 'viewAny'], true)) {
            return $user->hasRole(['school-admin', 'technician', 'viewer']);
        }

        if (in_array($action, ['create', 'update'], true)) {
            return $user->hasRole('school-admin');
        }

        return false;
    }
}
