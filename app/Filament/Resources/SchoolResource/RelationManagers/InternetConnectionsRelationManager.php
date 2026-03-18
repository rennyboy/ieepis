<?php
namespace App\Filament\Resources\SchoolResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
class InternetConnectionsRelationManager extends RelationManager {
    protected static string $relationship = 'internetConnections';
    protected static ?string $title = 'Internet Connectivity';
    public function form(Form $form): Form { return $form->schema([]); }
    public function table(Table $table): Table {
        return $table->recordTitleAttribute('isp')->columns([
            Tables\Columns\TextColumn::make('isp')->badge(),
            Tables\Columns\TextColumn::make('plan_name'),
            Tables\Columns\TextColumn::make('actual_download_speed')->label('↓ Mbps'),
            Tables\Columns\TextColumn::make('actual_upload_speed')->label('↑ Mbps'),
            Tables\Columns\TextColumn::make('status')->badge()->colors(['success'=>'active','danger'=>'inactive']),
        ]);
    }
}
