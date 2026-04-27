<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use App\Models\EquipmentAssignment;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only assignment history for a school.
 * All writes must go through AssignmentResource → AssignmentService.
 */
class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Assignment History';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('equipment.property_no')
                    ->label('Property No.')
                    ->fontFamily('mono')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Accountable Officer')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('custodian.full_name')->label('Custodian'),
                Tables\Columns\TextColumn::make('transaction_type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('supporting_doc_type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('supporting_doc_no')->label('Doc No.'),
                Tables\Columns\TextColumn::make('assigned_at')->date()->sortable(),
                Tables\Columns\TextColumn::make('returned_at')->date()->label('Returned'),
                Tables\Columns\TextColumn::make('assignedBy.name')->label('Assigned By'),
                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn (EquipmentAssignment $r) => $r->isActive()),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->defaultSort('assigned_at', 'desc');
    }
}
