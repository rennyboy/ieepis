<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'ICT Inventory';
    protected static ?int $navigationSort = 5;

    public static array $docTypes = [
        'PAR'  => 'PAR – Property Acknowledgment Receipt',
        'ICS'  => 'ICS – Inventory Custodian Slip',
        'IAR'  => 'IAR – Inspection and Acceptance Report',
        'DR'   => 'DR – Delivery Receipt',
        'OR'   => 'OR – Official Receipt',
        'SI'   => 'SI – Sales Invoice',
        'WMR'  => 'WMR – Waste Material Report',
        'RRSP' => 'RRSP – Report on the Remedies of Seized Properties',
        'RRPE' => 'RRPE – Report on Physical Count of Property & Equipment',
        'Other'=> 'Other',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Document Details')->schema([
                Forms\Components\Select::make('school_id')
                    ->label('School')->relationship('school', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('equipment_id')
                    ->label('Related Equipment')->relationship('equipment', 'model')
                    ->searchable()->preload()->nullable(),
                Forms\Components\Select::make('employee_id')
                    ->label('Related Employee')->relationship('employee', 'full_name')
                    ->searchable()->preload()->nullable(),
                Forms\Components\Select::make('document_type')
                    ->options(self::$docTypes)->required(),
                Forms\Components\TextInput::make('document_no')->label('Document No.'),
                Forms\Components\DatePicker::make('document_date')->label('Document Date'),
                Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('File Upload')->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Upload File (PDF or Image)')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->directory(fn ($get) => 'schools/' . ($get('school_id') ?? 'general') . '/documents')
                    ->maxSize(10240) // 10MB
                    ->required()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_type')->label('Type')
                    ->badge()->color('info')->sortable(),
                Tables\Columns\TextColumn::make('document_no')->label('No.')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('title')->searchable()->weight('bold')->limit(45),
                Tables\Columns\TextColumn::make('school.name')->label('School')->limit(25),
                Tables\Columns\TextColumn::make('equipment.model')->label('Equipment'),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee'),
                Tables\Columns\TextColumn::make('document_date')->label('Date')->date()->sortable(),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('Uploaded By'),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')->options(self::$docTypes),
                Tables\Filters\SelectFilter::make('school')->relationship('school', 'name')->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit'   => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
