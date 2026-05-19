<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Equipment;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\TicketStatus;
use App\Enums\TicketPriority;

class TicketResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $query = parent::getEloquentQuery()
            ->with(['school', 'equipment', 'reporter', 'assignedTo']);

        $query->when(
            fn () => $user->hasRole('school-admin'),
            fn (Builder $q) => $q->where('school_id', $user->school_id),
        );

        return $query;
    }

    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Documents & Tickets';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationBadgeColor = 'danger';

    public static function getNavigationBadge(): ?string
    {
        // Reuse the scoped query so the badge respects the school-admin
        // restriction in getEloquentQuery() instead of counting every school.
        $count = static::getEloquentQuery()
            ->whereIn('status', [
                TicketStatus::Open->value,
                TicketStatus::InProgress->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket Information')
                ->schema([
                    Forms\Components\TextInput::make('ticket_number')
                        ->label('Ticket No.')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('school_id')
                        ->label('School')
                        ->relationship('school', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('equipment_id')
                        ->label('Related Equipment')
                        ->relationship(
                            'equipment',
                            'model',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (Equipment $record): string => trim("{$record->brand} {$record->model}")." ({$record->property_no})",
                        )
                        ->searchable(['brand', 'model', 'property_no', 'serial_number'])
                        ->preload()
                        ->placeholder('Search by property no, serial, brand or model')
                        ->nullable(),
                    Forms\Components\Select::make('reporter_id')
                        ->label('Reported By')
                        ->relationship(
                            'reporter',
                            'full_name',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('issue_title')
                        ->label('Issue Title')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 2]),

            Forms\Components\Section::make('Status & Assignment')
                ->schema([
                    Forms\Components\Select::make('priority')
                        ->options(TicketPriority::options())
                        ->required()
                        ->default(TicketPriority::Medium),
                    Forms\Components\Select::make('status')
                        ->options(TicketStatus::options())
                        ->required()
                        ->default(TicketStatus::Open),
                    Forms\Components\Select::make('assigned_to_id')
                        ->label('Assigned Technician')
                        ->relationship(
                            'assignedTo',
                            'full_name',
                            fn (Builder $query, Forms\Get $get) => $query
                                ->when($get('school_id'), fn (Builder $q, $sid) => $q->where('school_id', $sid)),
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\DateTimePicker::make('resolved_at')->label(
                        'Resolved At',
                    ),
                    Forms\Components\Textarea::make('resolution_notes')
                        ->label('Resolution Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket No.')
                    ->fontFamily('mono')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_title')
                    ->label('Issue')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->limit(25),
                Tables\Columns\TextColumn::make('equipment.model')->label(
                    'Equipment',
                ),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => $state->label())
                    ->color(fn (TicketPriority $state): string => $state->color())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
                        $cases = collect(TicketPriority::cases());

                        return $query->orderByRaw(
                            'CASE priority'.$cases->map(fn () => ' WHEN ? THEN ?')->implode('').' END '.$direction,
                            $cases->flatMap(fn (TicketPriority $p) => [$p->value, $p->sortOrder()])->all(),
                        );
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TicketStatus $state): string => $state->label())
                    ->color(fn (TicketStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('assignedTo.full_name')->label(
                    'Assigned To',
                ),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(TicketStatus::options()),
                Tables\Filters\SelectFilter::make('priority')->options(TicketPriority::options()),
                Tables\Filters\SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
