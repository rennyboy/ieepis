<?php
namespace App\Filament\Resources\SchoolResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
class EquipmentRelationManager extends RelationManager {
    protected static string $relationship = 'equipment';
    protected static ?string $title = 'Equipment';
    public function form(Form $form): Form { return $form->schema([]); }
    public function table(Table $table): Table {
        return $table->recordTitleAttribute('model')->columns([
            Tables\Columns\TextColumn::make('property_no')->fontFamily('mono')->color('primary'),
            Tables\Columns\TextColumn::make('brand'),
            Tables\Columns\TextColumn::make('model')->weight('bold'),
            Tables\Columns\TextColumn::make('equipment_type')->badge(),
            Tables\Columns\TextColumn::make('accountability_status')->badge(),
            Tables\Columns\IconColumn::make('is_functional')->boolean()->label('Functional'),
            Tables\Columns\TextColumn::make('condition')->badge(),
        ])->actions([Tables\Actions\ViewAction::make()]);
    }
}
