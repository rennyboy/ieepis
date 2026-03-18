<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Open Support Tickets';

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::with(['school', 'equipment', 'assignedTo'])->whereIn('status', ['open', 'in-progress'])->latest())
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->label('Ticket No.')->fontFamily('mono')->color('primary'),
                Tables\Columns\TextColumn::make('issue_title')->label('Issue')->limit(40)->weight('bold'),
                Tables\Columns\TextColumn::make('school.name')->label('School')->limit(25),
                Tables\Columns\TextColumn::make('equipment.model')->label('Equipment'),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()->colors(['success' => 'low', 'warning' => 'medium', 'danger' => fn ($state) => in_array($state, ['high', 'critical'])]),
                Tables\Columns\TextColumn::make('status')
                    ->badge()->colors(['warning' => 'open', 'primary' => 'in-progress']),
                Tables\Columns\TextColumn::make('assignedTo.full_name')->label('Assigned To'),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->since(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
