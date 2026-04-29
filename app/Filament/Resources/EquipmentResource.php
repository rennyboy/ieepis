<?php

namespace App\Filament\Resources;

use App\Enums\DocumentType;
use App\Filament\Resources\EquipmentResource\Pages;
use App\Filament\Resources\EquipmentResource\RelationManagers;
use App\Exports\EquipmentExport;
use App\Imports\EquipmentImport;
use App\Models\Document;
use App\Models\Equipment;
use Filament\Actions as PageActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'ICT Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'model';

    // Shared dropdown options
    public static array $equipmentTypes = [
        'Laptop' => 'Laptop',
        'Desktop' => 'Desktop',
        'Tablet' => 'Tablet',
        'Printer' => 'Printer',
        'Scanner' => 'Scanner',
        'Photocopier' => 'Photocopier',
        'Projector' => 'Projector',
        'Monitor' => 'Monitor',
        'UPS' => 'UPS',
        'Network Switch' => 'Network Switch',
        'Router' => 'Router',
        'Access Point' => 'Access Point',
        'Server' => 'Server',
        'CCTV Camera' => 'CCTV Camera',
        'Smart TV' => 'Smart TV',
        'External Drive' => 'External Drive',
        'Web Camera' => 'Web Camera',
        'Headset' => 'Headset',
        'Speaker' => 'Speaker',
        'Others' => 'Others',
    ];

    public static array $brands = [
        'HP' => 'HP',
        'Dell' => 'Dell',
        'Lenovo' => 'Lenovo',
        'Acer' => 'Acer',
        'Asus' => 'Asus',
        'Apple' => 'Apple',
        'Samsung' => 'Samsung',
        'Epson' => 'Epson',
        'Canon' => 'Canon',
        'Brother' => 'Brother',
        'Cisco' => 'Cisco',
        'Ubiquiti' => 'Ubiquiti',
        'TP-Link' => 'TP-Link',
        'Hikvision' => 'Hikvision',
        'Dahua' => 'Dahua',
        'Others' => 'Others',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Equipment Details')
                ->tabs([
                    // ── TAB 1: Identification
                    Forms\Components\Tabs\Tab::make('Identification')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Forms\Components\Select::make('school_id')
                                ->label('School / Office')
                                ->relationship('school', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull()
                                ->default(fn () => auth()->user()->school_id)
                                ->disabled(fn () => ! in_array('super-admin', auth()->user()->getRoleNames()->toArray())),

                            Forms\Components\TextInput::make('property_no')
                                ->label('Property No.')
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make(
                                'old_property_no',
                            )->label('Old / Previous Property No.'),
                            Forms\Components\TextInput::make('serial_number')
                                ->label('Serial Number')
                                ->required(),
                            Forms\Components\Select::make('item_type')
                                ->label('Item Type')
                                ->options([
                                    'Device Type' => 'Device Type',
                                    'Equipment' => 'Equipment',
                                    'Hardware' => 'Hardware',
                                    'Software' => 'Software',
                                    'Peripherals' => 'Peripherals',
                                ])
                                ->required(),
                            Forms\Components\Select::make('equipment_type')
                                ->label('Equipment / Device Type')
                                ->options(self::$equipmentTypes)
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('brand')
                                ->label('Brand / Manufacturer')
                                ->options(self::$brands)
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make(
                                'model',
                            )->required(),
                            Forms\Components\Select::make(
                                'unit_of_measure',
                            )->options([
                                'Unit' => 'Unit',
                                'Set' => 'Set',
                                'Pack' => 'Pack',
                                'Piece' => 'Piece',
                            ]),
                            Forms\Components\Textarea::make('specifications')
                                ->label('Technical Specifications')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(['default' => 2]),

                    // ── TAB 2: Classification & DCP
                    Forms\Components\Tabs\Tab::make('Classification')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Forms\Components\Select::make('category')
                                ->label('Category (COA)')
                                ->options([
                                    'High-Value' => 'High-Value (≥ ₱50,000)',
                                    'Low-Value' => 'Low-Value (< ₱50,000)',
                                ])
                                ->required(),
                            Forms\Components\Select::make('classification')
                                ->options([
                                    'Machinery and Equipment for ICT' => 'Machinery and Equipment for ICT',
                                ])
                                ->default('Machinery and Equipment for ICT'),
                            Forms\Components\TextInput::make(
                                'gl_sl_code',
                            )->label('GL-SL Code (Chart of Accounts)'),
                            Forms\Components\TextInput::make(
                                'uacs_code',
                            )->label('UACS Code'),
                            Forms\Components\Toggle::make('is_dcp')
                                ->label('DCP Equipment')
                                ->live()
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('dcp_package')
                                ->label('DCP Package Name')
                                ->visible(fn (Get $get) => $get('is_dcp')),
                            Forms\Components\TextInput::make('dcp_year')
                                ->label('DCP Year')
                                ->visible(fn (Get $get) => $get('is_dcp'))
                                ->numeric(),
                        ])
                        ->columns(['default' => 2]),

                    // ── TAB 3: Acquisition
                    Forms\Components\Tabs\Tab::make('Acquisition')
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Forms\Components\Select::make('mode_of_acquisition')
                                ->options([
                                    'Purchased' => 'Purchased',
                                    'Donation' => 'Donation',
                                    'Grant' => 'Grant',
                                ])
                                ->required()
                                ->live(),
                            Forms\Components\Select::make(
                                'source_of_acquisition',
                            )->options([
                                'Central Office' => 'Central Office',
                                'MOOE' => 'MOOE',
                                'SEF' => 'SEF',
                                'LGU' => 'LGU',
                                'PTA' => 'PTA',
                                'Donation' => 'Donation',
                                'Grant' => 'Grant',
                                'Others' => 'Others',
                            ]),
                            Forms\Components\TextInput::make('donor')
                                ->label('Donor (if Donation)')
                                ->visible(
                                    fn (Get $get) => $get(
                                        'mode_of_acquisition',
                                    ) === 'Donation',
                                ),
                            Forms\Components\Select::make(
                                'source_of_funds',
                            )->options([
                                'General Fund' => 'General Fund',
                                'Special Education Fund' => 'Special Education Fund',
                                'LGU Fund' => 'LGU Fund',
                                'GOIP' => 'GOIP',
                                'Others' => 'Others',
                            ]),
                            Forms\Components\TextInput::make('acquisition_cost')
                                ->label('Acquisition Cost (₱)')
                                ->numeric()
                                ->prefix('₱'),
                            Forms\Components\DatePicker::make(
                                'acquisition_date',
                            )->label('Acquisition Date'),
                            Forms\Components\DatePicker::make(
                                'received_date',
                            )->label('Date Received'),
                            Forms\Components\TextInput::make(
                                'estimated_useful_life',
                            )
                                ->label('Estimated Useful Life (years)')
                                ->numeric(),
                            Forms\Components\TextInput::make('supplier')->label(
                                'Supplier / Distributor',
                            ),
                            Forms\Components\TextInput::make(
                                'pmp_reference_no',
                            )->label('PMP Reference Item No.'),
                            Forms\Components\Select::make(
                                'supporting_doc_type_acquisition',
                            )
                                ->label('Supporting Document Type')
                                ->options([
                                    'OR' => 'OR',
                                    'SI' => 'SI',
                                    'DR' => 'DR',
                                    'IAR' => 'IAR',
                                    'RRSP' => 'RRSP',
                                ]),
                            Forms\Components\TextInput::make(
                                'supporting_doc_no_acquisition',
                            )->label('Document No.'),
                        ])
                        ->columns(['default' => 2]),

                    // ── TAB 4: Warranty & Condition
                    Forms\Components\Tabs\Tab::make('Condition')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Forms\Components\Toggle::make('under_warranty')
                                ->label('Under Warranty')
                                ->live(),
                            Forms\Components\DatePicker::make(
                                'warranty_end_date',
                            )
                                ->label('End of Warranty Date')
                                ->visible(
                                    fn (Get $get) => $get('under_warranty'),
                                ),
                            Forms\Components\Toggle::make('is_functional')
                                ->label('Functional')
                                ->default(true),
                            Forms\Components\Select::make('condition')
                                ->options([
                                    'Good' => 'Good',
                                    'Fair' => 'Fair',
                                    'Poor' => 'Poor',
                                    'Unserviceable' => 'Unserviceable',
                                ])
                                ->required(),
                            Forms\Components\Select::make(
                                'accountability_status',
                            )
                                ->label('Accountability / Disposition Status')
                                ->options([
                                    'Normal' => 'Normal',
                                    'assigned' => 'Assigned',
                                    'unassigned' => 'Unassigned',
                                    'Transferred' => 'Transferred',
                                    'Stolen' => 'Stolen',
                                    'Lost' => 'Lost',
                                    'Damaged' => 'Damaged due to calamity',
                                    'For Disposal' => 'For Disposal',
                                ])
                                ->required(),
                            Forms\Components\Select::make(
                                'equipment_condition_coa',
                            )
                                ->label('Equipment Condition (COA)')
                                ->options([
                                    'New' => 'New',
                                    'Good' => 'Good',
                                    'Fair' => 'Fair',
                                    'Worn Out' => 'Worn Out',
                                    'Unserviceable' => 'Unserviceable',
                                ]),
                            Forms\Components\TextInput::make(
                                'equipment_location',
                            )
                                ->label('Equipment Location')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('remarks')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(['default' => 2]),

                    // ── TAB 5: Issuance / Assignment
                    Forms\Components\Tabs\Tab::make('Issuance')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Forms\Components\Select::make('document_id')
                                ->label('Linked Document (DR/PAR/ICS)')
                                ->options(fn () => \App\Models\Document::whereIn('document_type', ['DR', 'PAR', 'ICS'])
                                    ->orderBy('title')
                                    ->pluck('title', 'id'))
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            Forms\Components\Select::make(
                                'transaction_type',
                            )->options([
                                'Beginning Inventory' => 'Beginning Inventory',
                                'Issuance' => 'Issuance',
                                'Transfer' => 'Transfer',
                                'Return' => 'Return',
                                'Disposal' => 'Disposal',
                            ]),
                            Forms\Components\Select::make(
                                'supporting_doc_type_issuance',
                            )
                                ->label('Supporting Document Type')
                                ->options([
                                    'PAR' => 'PAR',
                                    'ICS' => 'ICS',
                                    'RRSP' => 'RRSP',
                                    'RRPE' => 'RRPE',
                                    'WMR' => 'WMR',
                                ]),
                            Forms\Components\TextInput::make(
                                'supporting_doc_no_issuance',
                            )->label('Document No.'),
                        ])
                        ->columns(['default' => 2]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property_no')
                    ->label('Property No.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('brand')->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('equipment_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->school?->name),
                Tables\Columns\TextColumn::make(
                    'activeAssignment.employee.full_name',
                )
                    ->label('Accountable Officer')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('acquisition_cost')
                    ->label('Cost')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_dcp')
                    ->label('DCP')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_functional')
                    ->label('Functional')
                    ->boolean(),
                Tables\Columns\TextColumn::make('condition')
                    ->badge()
                    ->colors([
                        'success' => 'Good',
                        'warning' => 'Fair',
                        'danger' => fn ($state) => in_array($state, [
                            'Poor',
                            'Unserviceable',
                        ]),
                    ]),
                Tables\Columns\TextColumn::make('accountability_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'Normal',
                        'info' => 'assigned',
                        'warning' => 'unassigned',
                        'danger' => fn ($state) => in_array($state, [
                            'Stolen',
                            'Lost',
                            'For Disposal',
                        ]),
                    ]),
                Tables\Columns\TextColumn::make('document.title')
                    ->label('Linked Doc')
                    ->limit(25)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('warranty_status')
                    ->label('Warranty')
                    ->getStateUsing(fn (Equipment $r) => $r->warranty_status)
                    ->badge()
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Expired',
                        'gray' => 'No Warranty',
                    ]),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label('Tickets')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenance_tickets_count')
                    ->counts('maintenanceTickets')
                    ->label('Maintenance Logs')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignments_count')
                    ->counts('assignments')
                    ->label('Transfers')
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('equipment_type')->options(
                    self::$equipmentTypes,
                ),
                Tables\Filters\SelectFilter::make(
                    'accountability_status',
                )->options([
                    'Normal' => 'Normal',
                    'assigned' => 'Assigned',
                    'unassigned' => 'Unassigned',
                    'For Disposal' => 'For Disposal',
                ]),
                Tables\Filters\SelectFilter::make('condition')->options([
                    'Good' => 'Good',
                    'Fair' => 'Fair',
                    'Poor' => 'Poor',
                    'Unserviceable' => 'Unserviceable',
                ]),
                Tables\Filters\TernaryFilter::make('is_dcp')->label('DCP Only'),
                Tables\Filters\TernaryFilter::make('is_functional')->label(
                    'Functional Only',
                ),
            ])
            ->heading(new \Illuminate\Support\HtmlString(view('filament.components.export-button', [
                'route' => 'equipment.pdf.bulk',
                'label' => 'Export Inventory (PDF)',
            ])->render()))
            ->headerActions([
                Tables\Actions\Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(route('equipment.excel.export')),
                Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-download')
                    ->url(route('equipment.excel.template')),
                Tables\Actions\Action::make('importEquipment')
                    ->label('Import Excel')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
                    ->modalHeading('Import Equipment from Excel')
                    ->modalDescription('Upload an Excel file (.xlsx, .xls, or .csv) to import equipment.')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Select File')
                            ->directory('imports')
                            ->acceptedFileTypes(['.xlsx', '.xls', '.csv'])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            Excel::import(new EquipmentImport, $data['file']);
                            
                            Notification::make()
                                ->success()
                                ->title('Import Successful')
                                ->body('Equipment data has been imported.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Import Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                self::sharedDocumentViewAction(),
                self::sharedDocumentAttachAction(),
                Tables\Actions\Action::make('qrcode')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->modalContent(
                        fn (Equipment $record) => view(
                            'filament.equipment.qrcode',
                            compact('record'),
                        ),
                    )
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Tabs::make('Equipment Details')
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('Overview')
                        ->schema([
                            Infolists\Components\TextEntry::make('property_no')
                                ->label('Property No.')
                                ->badge(),
                            Infolists\Components\TextEntry::make(
                                'serial_number',
                            )->label('Serial No.'),
                            Infolists\Components\TextEntry::make('brand'),
                            Infolists\Components\TextEntry::make('model'),
                            Infolists\Components\TextEntry::make(
                                'equipment_type',
                            )
                                ->label('Type')
                                ->badge(),
                            Infolists\Components\TextEntry::make(
                                'school.name',
                            )->label('School'),
                            Infolists\Components\TextEntry::make(
                                'specifications',
                            )->columnSpanFull(),
                            Infolists\Components\TextEntry::make(
                                'acquisition_cost',
                            )
                                ->label('Cost')
                                ->money('PHP'),
                            Infolists\Components\TextEntry::make(
                                'acquisition_date',
                            )
                                ->label('Acquired')
                                ->date(),
                            Infolists\Components\TextEntry::make(
                                'warranty_end_date',
                            )
                                ->label('Warranty Until')
                                ->date(),
                            Infolists\Components\IconEntry::make('is_dcp')
                                ->label('DCP')
                                ->boolean(),
                            Infolists\Components\IconEntry::make(
                                'is_functional',
                            )
                                ->label('Functional')
                                ->boolean(),
                            Infolists\Components\TextEntry::make(
                                'condition',
                            )->badge(),
                            Infolists\Components\TextEntry::make(
                                'accountability_status',
                            )
                                ->label('Status')
                                ->badge(),
                            Infolists\Components\TextEntry::make(
                                'equipment_location',
                            )->label('Location'),
                            Infolists\Components\TextEntry::make(
                                'assignments_count',
                            )->getStateUsing(fn ($record) => $record->assignments()->count())
                                ->label('Ownership Transfers')
                                ->badge()
                                ->color('success'),
                            Infolists\Components\TextEntry::make(
                                'maintenance_tickets_count',
                            )->getStateUsing(fn ($record) => $record->maintenanceTickets()->count())
                                ->label('Maintenance Performed')
                                ->badge()
                                ->color('warning'),
                            Infolists\Components\TextEntry::make(
                                'remarks',
                            )->columnSpanFull(),
                        ])
                        ->columns(['default' => 3]),
                    Infolists\Components\Tabs\Tab::make('Acquisition')
                        ->schema([
                            Infolists\Components\TextEntry::make(
                                'mode_of_acquisition',
                            ),
                            Infolists\Components\TextEntry::make(
                                'source_of_acquisition',
                            ),
                            Infolists\Components\TextEntry::make(
                                'source_of_funds',
                            ),
                            Infolists\Components\TextEntry::make('supplier'),
                            Infolists\Components\TextEntry::make('donor'),
                            Infolists\Components\TextEntry::make(
                                'pmp_reference_no',
                            )->label('PMP Ref No.'),
                            Infolists\Components\TextEntry::make(
                                'supporting_doc_type_acquisition',
                            )->label('Doc Type'),
                            Infolists\Components\TextEntry::make(
                                'supporting_doc_no_acquisition',
                            )->label('Doc No.'),
                            Infolists\Components\TextEntry::make(
                                'dcp_package',
                            )->label('DCP Package'),
                            Infolists\Components\TextEntry::make(
                                'dcp_year',
                            )->label('DCP Year'),
                            Infolists\Components\TextEntry::make(
                                'gl_sl_code',
                            )->label('GL-SL Code'),
                            Infolists\Components\TextEntry::make(
                                'uacs_code',
                            )->label('UACS Code'),
                        ])
                        ->columns(['default' => 3]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AssignmentsRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $query = parent::getEloquentQuery();

        $query->when(
            fn () => $user->hasRole('school-admin'),
            fn (Builder $q) => $q->whereIn('school_id', [$user->school_id]),
        );

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipments::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'view' => Pages\ViewEquipment::route('/{record}'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['property_no', 'serial_number', 'brand', 'model'];
    }

    /**
     * Form schema used by both the table-row and page-header "Attach Document" actions.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    protected static function attachDocumentForm(): array
    {
        return [
            Forms\Components\Toggle::make('link_existing')
                ->label('Link to Existing Document')
                ->live(),
            Forms\Components\Select::make('existing_document_id')
                ->label('Select Document to Link')
                ->options(fn () => \App\Models\Document::whereIn('document_type', ['DR', 'PAR', 'ICS'])
                    ->orderBy('title')
                    ->pluck('title', 'id'))
                ->searchable()
                ->preload()
                ->visible(fn (Get $get) => $get('link_existing'))
                ->nullable(),
            Forms\Components\TextInput::make('existing_document_id_alt')
                ->label('Or Enter Document No.')
                ->visible(fn (Get $get) => $get('link_existing'))
                ->nullable(),
            Forms\Components\Select::make('document_type')
                ->options(DocumentType::options())
                ->required()
                ->visible(fn (Get $get) => !$get('link_existing')),
            Forms\Components\TextInput::make('document_no')->label('Document No.')
                ->visible(fn (Get $get) => !$get('link_existing')),
            Forms\Components\DatePicker::make('document_date')->default(now())
                ->visible(fn (Get $get) => !$get('link_existing')),
            Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('description')->columnSpanFull()
                ->visible(fn (Get $get) => !$get('link_existing')),
            Forms\Components\FileUpload::make('file_path')
                ->label('File')
                ->disk('public')
                ->directory(fn (Equipment $record) => "schools/{$record->school_id}/documents")
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(10240)
                ->required()
                ->visible(fn (Get $get) => !$get('link_existing'))
                ->columnSpanFull(),
        ];
    }

    protected static function attachDocumentHandler(Equipment $record, array $data): void
    {
        $linkExisting = $data['link_existing'] ?? false;

        if ($linkExisting && !empty($data['existing_document_id'])) {
            // Link to existing document
            $doc = \App\Models\Document::find($data['existing_document_id']);
            if ($doc) {
                $doc->update(['equipment_id' => $record->id]);
                Notification::make()
                    ->success()
                    ->title('Document linked')
                    ->body("Now linked to {$doc->title}")
                    ->send();
            }
            return;
        }

        // Create new document (current behavior)
        Document::create([
            'school_id' => $record->school_id,
            'equipment_id' => $record->id,
            'document_type' => $data['document_type'],
            'document_no' => $data['document_no'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $data['file_path'],
            'document_date' => $data['document_date'] ?? null,
            'uploaded_by_id' => Auth::id(),
        ]);

        Notification::make()
            ->success()
            ->title('Document attached')
            ->body('The file is now linked to this equipment.')
            ->send();
    }

    /**
     * Table row action — opens the most recent document in a new tab when one exists.
     */
    public static function sharedDocumentViewAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('sharedDocumentView')
            ->label('Document')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->visible(fn (Equipment $record) => $record->hasSharedDocument() && $record->sharedDocument()?->file_path)
            ->url(fn (Equipment $record) => Storage::disk('public')->url($record->sharedDocument()->file_path))
            ->openUrlInNewTab();
    }

    /**
     * Table row action — upload a document when none is attached yet.
     */
    public static function sharedDocumentAttachAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('sharedDocumentAttach')
            ->label('Attach Document')
            ->icon('heroicon-o-document-plus')
            ->color('warning')
            ->visible(fn (Equipment $record) => ! $record->hasSharedDocument())
            ->modalHeading('Attach or Link Document')
            ->modalSubmitActionLabel('Upload')
            ->form(self::attachDocumentForm())
            ->action(fn (Equipment $record, array $data) => self::attachDocumentHandler($record, $data));
    }

    /**
     * Page header variant — same behaviour as the row "view" action, scoped to a single record.
     */
    public static function sharedDocumentViewPageAction(): PageActions\Action
    {
        return PageActions\Action::make('sharedDocumentView')
            ->label('Document')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->visible(fn (Equipment $record) => $record->hasSharedDocument() && $record->sharedDocument()?->file_path)
            ->url(fn (Equipment $record) => Storage::disk('public')->url($record->sharedDocument()->file_path))
            ->openUrlInNewTab();
    }

    /**
     * Page header variant — upload modal scoped to the current record.
     */
    public static function sharedDocumentAttachPageAction(): PageActions\Action
    {
        return PageActions\Action::make('sharedDocumentAttach')
            ->label('Attach Document')
            ->icon('heroicon-o-document-plus')
            ->color('warning')
            ->visible(fn (Equipment $record) => ! $record->hasSharedDocument())
            ->modalHeading('Attach or Link Document')
            ->modalSubmitActionLabel('Upload')
            ->form(self::attachDocumentForm())
            ->action(fn (Equipment $record, array $data) => self::attachDocumentHandler($record, $data));
    }
}
