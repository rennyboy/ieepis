<?php
namespace App\Filament\Resources\EmployeeResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
class AssignmentsRelationManager extends RelationManager {
    protected static string $relationship = 'equipmentAssignments';
    protected static ?string $title = 'Assigned Equipment';
    public function form(Form $form): Form { return $form->schema([]); }
    public function table(Table $table): Table {
        return $table->recordTitleAttribute('id')->columns([
            Tables\Columns\TextColumn::make('equipment.property_no')->fontFamily('mono')->color('primary'),
            Tables\Columns\TextColumn::make('equipment.brand'),
            Tables\Columns\TextColumn::make('equipment.model')->weight('bold'),
            Tables\Columns\TextColumn::make('transaction_type')->badge(),
            Tables\Columns\TextColumn::make('assigned_at')->date(),
            Tables\Columns\TextColumn::make('returned_at')->date()->label('Returned'),
        ])->defaultSort('assigned_at','desc');
    }
}
