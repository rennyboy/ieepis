<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';
    protected static ?string $title = 'Support Tickets';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('issue_title')->required()->columnSpanFull(),
            Forms\Components\Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                ->default('medium'),
            Forms\Components\Select::make('status')
                ->options(['open' => 'Open', 'in-progress' => 'In Progress', 'resolved' => 'Resolved'])
                ->default('open'),
            Forms\Components\Textarea::make('description')->required()->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('issue_title')
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->fontFamily('mono')->color('primary'),
                Tables\Columns\TextColumn::make('issue_title')->weight('bold')->limit(35),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()->colors(['success' => 'low', 'warning' => 'medium', 'danger' => fn ($state) => in_array($state, ['high', 'critical'])]),
                Tables\Columns\TextColumn::make('status')
                    ->badge()->colors(['warning' => 'open', 'primary' => 'in-progress', 'success' => 'resolved']),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make()]);
    }
}
