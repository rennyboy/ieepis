<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationBadgeColor = 'danger';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['open', 'in-progress'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket Information')->schema([
                Forms\Components\TextInput::make('ticket_number')->label('Ticket No.')->disabled()->dehydrated(false),
                Forms\Components\Select::make('school_id')
                    ->label('School')->relationship('school', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('equipment_id')
                    ->label('Related Equipment')->relationship('equipment', 'model')
                    ->searchable()->preload()->nullable(),
                Forms\Components\Select::make('reporter_id')
                    ->label('Reported By')->relationship('reporter', 'full_name')
                    ->searchable()->preload()->required(),
                Forms\Components\TextInput::make('issue_title')->label('Issue Title')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('description')->required()->rows(4)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Status & Assignment')->schema([
                Forms\Components\Select::make('priority')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                    ->required()->default('medium'),
                Forms\Components\Select::make('status')
                    ->options(['open' => 'Open', 'in-progress' => 'In Progress', 'pending' => 'Pending', 'resolved' => 'Resolved', 'closed' => 'Closed'])
                    ->required()->default('open'),
                Forms\Components\Select::make('assigned_to_id')
                    ->label('Assigned Technician')->relationship('assignedTo', 'full_name')
                    ->searchable()->preload()->nullable(),
                Forms\Components\DateTimePicker::make('resolved_at')->label('Resolved At'),
                Forms\Components\Textarea::make('resolution_notes')->label('Resolution Notes')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->label('Ticket No.')->fontFamily('mono')->color('primary')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('issue_title')->label('Issue')->searchable()->limit(40)->weight('bold'),
                Tables\Columns\TextColumn::make('school.name')->label('School')->limit(25),
                Tables\Columns\TextColumn::make('equipment.model')->label('Equipment'),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()->colors(['success' => 'low', 'warning' => 'medium', 'danger' => fn ($state) => in_array($state, ['high', 'critical'])]),
                Tables\Columns\TextColumn::make('status')
                    ->badge()->colors(['warning' => 'open', 'primary' => 'in-progress', 'gray' => 'pending', 'success' => fn ($state) => in_array($state, ['resolved', 'closed'])]),
                Tables\Columns\TextColumn::make('assignedTo.full_name')->label('Assigned To'),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->date()->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')->label('Resolved')->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['open' => 'Open', 'in-progress' => 'In Progress', 'pending' => 'Pending', 'resolved' => 'Resolved']),
                Tables\Filters\SelectFilter::make('priority')->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']),
                Tables\Filters\SelectFilter::make('school')->relationship('school', 'name')->searchable()->preload(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit'   => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
