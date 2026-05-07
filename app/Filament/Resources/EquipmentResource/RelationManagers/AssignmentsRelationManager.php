<?php

namespace App\Filament\Resources\EquipmentResource\RelationManagers;

use App\Models\EquipmentAssignment;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only assignment history for a piece of equipment.
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with('documents'))
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Accountable Officer')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('custodian.full_name')->label('Custodian'),
                Tables\Columns\TextColumn::make('transaction_type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('supporting_doc_type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('supporting_doc_no')->label('Doc No.'),
                Tables\Columns\TextColumn::make('issuance_doc')
                    ->label('Issuance File')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (EquipmentAssignment $r) => $r->issuanceDocument()?->document_type?->value)
                    ->url(fn (EquipmentAssignment $r) => $r->issuanceDocument()?->file_url, true)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('return_doc')
                    ->label('Return File')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(fn (EquipmentAssignment $r) => $r->returnDocument()?->document_type?->value)
                    ->url(fn (EquipmentAssignment $r) => $r->returnDocument()?->file_url, true)
                    ->placeholder('—'),
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
