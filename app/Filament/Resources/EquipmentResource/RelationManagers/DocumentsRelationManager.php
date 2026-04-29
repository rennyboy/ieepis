<?php

namespace App\Filament\Resources\EquipmentResource\RelationManagers;

use App\Enums\DocumentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $title = 'Documents';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('document_type')
                ->options(DocumentType::options())
                ->required(),
            Forms\Components\TextInput::make('document_no'),
            Forms\Components\DatePicker::make('document_date'),
            Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')
                ->label('File')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                ->directory('documents')->required()->columnSpanFull(),
        ])->columns(['default' => 2]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->description('Upload paperwork (PAR, ICS, IAR, etc.) here. Use "Link to Existing" toggle to link documents.')
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('title')
                    ->weight('bold')
                    ->limit(40),
                Tables\Columns\TextColumn::make('document_no')
                    ->label('Doc No.')
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('document_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->since(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\Action::make('view_file')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => !empty($record->file_path))
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn ($record) => !empty($record->file_path))
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
