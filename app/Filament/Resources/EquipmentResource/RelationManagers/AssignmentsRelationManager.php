<?php
// ─── app/Filament/Resources/EquipmentResource/RelationManagers/AssignmentsRelationManager.php

namespace App\Filament\Resources\EquipmentResource\RelationManagers;

use App\Models\EquipmentAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
    protected static ?string $title = 'Assignment History';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')
                ->label('Accountable Officer')
                ->relationship('employee', 'full_name', fn ($q) => $q->where('status', 'active'))
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('custodian_id')
                ->label('Custodian / End User')
                ->relationship('custodian', 'full_name')
                ->searchable()->preload()->nullable(),
            Forms\Components\Select::make('transaction_type')
                ->options(['Beginning Inventory' => 'Beginning Inventory', 'Issuance' => 'Issuance', 'Transfer' => 'Transfer', 'Return' => 'Return'])
                ->required()->default('Issuance'),
            Forms\Components\Select::make('supporting_doc_type')
                ->options(['PAR' => 'PAR', 'ICS' => 'ICS', 'RRSP' => 'RRSP', 'RRPE' => 'RRPE']),
            Forms\Components\TextInput::make('supporting_doc_no'),
            Forms\Components\DatePicker::make('assigned_at')->required()->default(now()),
            Forms\Components\DatePicker::make('returned_at')->label('Returned At (leave blank if active)'),
            Forms\Components\TextInput::make('assigned_by')->required(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Accountable Officer')->weight('bold'),
                Tables\Columns\TextColumn::make('custodian.full_name')->label('Custodian'),
                Tables\Columns\TextColumn::make('transaction_type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('supporting_doc_type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('supporting_doc_no'),
                Tables\Columns\TextColumn::make('assigned_at')->date()->sortable(),
                Tables\Columns\TextColumn::make('returned_at')->date()->label('Returned'),
                Tables\Columns\TextColumn::make('assigned_by'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn ($record) => is_null($record->returned_at)),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('assigned_at', 'desc');
    }
}
