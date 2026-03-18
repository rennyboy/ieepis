<?php
namespace App\Filament\Resources\SchoolResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
class EmployeesRelationManager extends RelationManager {
    protected static string $relationship = 'employees';
    protected static ?string $title = 'Personnel';
    public function form(Form $form): Form {
        return $form->schema([
            Forms\Components\TextInput::make('employee_number')->required(),
            Forms\Components\TextInput::make('first_name')->required(),
            Forms\Components\TextInput::make('last_name')->required(),
            Forms\Components\TextInput::make('position')->required(),
            Forms\Components\Select::make('employment_type')->options(['teaching'=>'Teaching','non-teaching'=>'Non-Teaching'])->required(),
            Forms\Components\Select::make('status')->options(['active'=>'Active','inactive'=>'Inactive'])->default('active'),
        ])->columns(2);
    }
    public function table(Table $table): Table {
        return $table->recordTitleAttribute('full_name')->columns([
            Tables\Columns\TextColumn::make('full_name')->weight('bold')->searchable(['first_name','last_name']),
            Tables\Columns\TextColumn::make('employee_number')->fontFamily('mono'),
            Tables\Columns\TextColumn::make('position'),
            Tables\Columns\TextColumn::make('employment_type')->badge(),
            Tables\Columns\TextColumn::make('status')->badge()->colors(['success'=>'active','danger'=>'inactive']),
        ])->headerActions([Tables\Actions\CreateAction::make()])
          ->actions([Tables\Actions\EditAction::make()]);
    }
}
