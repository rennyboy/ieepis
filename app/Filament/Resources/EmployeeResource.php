<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
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

    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Information')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Select::make('school_id')
                        ->label('School / Office')
                        ->relationship('school', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('employee_number')
                        ->label('Employee ID')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('first_name')->required(),
                    Forms\Components\TextInput::make('last_name')->required(),
                    Forms\Components\TextInput::make('middle_name'),
                    Forms\Components\TextInput::make('suffix')->label(
                        'Suffix (Jr., Sr., II, etc.)',
                    ),
                    Forms\Components\FileUpload::make('photo')
                        ->image()
                        ->directory('employees/photos')
                        ->imagePreviewHeight('80')
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 3]),

            Forms\Components\Section::make('Employment Details')
                ->icon('heroicon-o-briefcase')
                ->schema([
                    Forms\Components\TextInput::make('position')
                        ->label('Position / Designation')
                        ->required(),
                    Forms\Components\TextInput::make('department')->label(
                        'Department / Division',
                    ),
                    Forms\Components\TextInput::make('ro_office')->label(
                        'RO Office',
                    ),
                    Forms\Components\TextInput::make('sdo_office')->label(
                        'SDO Office',
                    ),
                    Forms\Components\Select::make('employment_type')
                        ->options([
                            'teaching' => 'Teaching',
                            'non-teaching' => 'Non-Teaching',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'retired' => 'Retired',
                        ])
                        ->required()
                        ->default('active'),
                    Forms\Components\DatePicker::make('date_hired')->label(
                        'Date Hired',
                    ),
                    Forms\Components\Toggle::make('is_oic')
                        ->label('Officer-In-Charge (OIC)')
                        ->live(),
                    Forms\Components\TextInput::make('oic_office')
                        ->label('OIC Office / Division')
                        ->visible(fn ($get) => $get('is_oic')),
                    Forms\Components\Toggle::make('is_non_deped_funded')->label(
                        'Non-DepEd Funded',
                    ),
                ])
                ->columns(['default' => 3]),

            Forms\Components\Section::make('Contact Information')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('DepEd Email')
                        ->email(),
                    Forms\Components\TextInput::make('personal_email')
                        ->label('Personal Email')
                        ->email(),
                    Forms\Components\TextInput::make('mobile_1')->label(
                        'Mobile No. 1',
                    ),
                    Forms\Components\TextInput::make('mobile_2')->label(
                        'Mobile No. 2',
                    ),
                ])
                ->columns(['default' => 2]),

            Forms\Components\Section::make('Separation Details')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->schema([
                    Forms\Components\Toggle::make('is_inactive')->label(
                        'Mark as Inactive / Separated',
                    ),
                    Forms\Components\DatePicker::make(
                        'date_of_separation',
                    )->label('Date of Separation'),
                    Forms\Components\Select::make(
                        'cause_of_separation',
                    )->options([
                        'Resigned' => 'Resigned',
                        'Retired' => 'Retired',
                        'AWOL' => 'AWOL',
                        'Transferred' => 'Transferred',
                        'Deceased' => 'Deceased',
                        'Dismissed' => 'Dismissed',
                    ]),
                    Forms\Components\TextInput::make('detailed_from')->label(
                        'Detailed/Transferred From',
                    ),
                    Forms\Components\TextInput::make('detailed_to')->label(
                        'Detailed/Transferred To',
                    ),
                ])
                ->columns(['default' => 2])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->circular()
                    ->defaultImageUrl(
                        fn (
                            $record,
                        ) => "https://ui-avatars.com/api/?name={$record->first_name}+{$record->last_name}&background=1a56db&color=fff",
                    ),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(query: function ($query, string $search) {
                        return $query
                            ->whereRaw('first_name like ?', ["%{$search}%"])
                            ->orWhereRaw('last_name like ?', ["%{$search}%"])
                            ->orWhereRaw('middle_name like ?', ["%{$search}%"]);
                    })
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('employee_number')
                    ->label('Employee No.')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('position')->searchable(),
                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->school?->name),
                Tables\Columns\TextColumn::make('employment_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'info' => 'teaching',
                        'warning' => 'non-teaching',
                    ]),
                Tables\Columns\TextColumn::make('current_equipment_count')
                    ->label('Equipment')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(
                        fn (Employee $r) => $r->activeAssignments()->count(),
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'gray' => 'retired',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('employment_type')->options([
                    'teaching' => 'Teaching',
                    'non-teaching' => 'Non-Teaching',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'retired' => 'Retired',
                ]),
            ])
            ->heading(new \Illuminate\Support\HtmlString(view('filament.components.export-button', [
                'route' => 'employees.pdf.bulk',
                'label' => 'Export Personnel (PDF)',
            ])->render()))
            ->headerActions([
                Tables\Actions\Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(route('employees.excel.export')),
                Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('employees.excel.template')),
                Tables\Actions\Action::make('importEmployees')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Import Employees from Excel')
                    ->modalDescription('Upload an Excel file (.xlsx, .xls, or .csv) to import employees.')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Select File')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                '.csv',
                            ])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new \App\Imports\EmployeeImport();
                            \Maatwebsite\Excel\Facades\Excel::import($import, $data['file'], 'public');

                            if ($import->getRowCount() === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Import Skipped')
                                    ->body('The uploaded file was empty or contained no valid employee rows.')
                                    ->send();
                                return;
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Import Successful')
                                ->body($import->getRowCount() . ' employee records have been imported or updated.')
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Employee Profile')
                ->schema([
                    Infolists\Components\ImageEntry::make('photo')
                        ->circular()
                        ->height(80),
                    Infolists\Components\TextEntry::make('full_name')
                        ->label('Full Name')
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('employee_number')
                        ->label('Employee No.')
                        ->badge(),
                    Infolists\Components\TextEntry::make('position'),
                    Infolists\Components\TextEntry::make('school.name')->label(
                        'School',
                    ),
                    Infolists\Components\TextEntry::make(
                        'employment_type',
                    )->badge(),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('email'),
                    Infolists\Components\TextEntry::make('mobile_1'),
                    Infolists\Components\TextEntry::make('date_hired')->date(),
                ])
                ->columns(['default' => 3]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'employee_number', 'position'];
    }
}
