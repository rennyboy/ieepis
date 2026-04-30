<?php

namespace App\Filament\Pages;

use App\Models\School;
use App\Models\District;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Division;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SetupSchoolProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static string $view = 'filament.pages.setup-school-profile';
    protected static ?string $title = 'Initial School Setup';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        // If user already has a school, redirect them
        if (Auth::user()->school_id) {
            $this->redirect('/admin');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('School Profile')
                        ->description('Basic information about your school')
                        ->schema([
                            Section::make('Location Details')
                                ->schema([
                                    Select::make('division')
                                        ->label('Division')
                                        ->options(Division::pluck('name', 'name'))
                                        ->required()
                                        ->live(),
                                    Select::make('district_id')
                                        ->label('District')
                                        ->options(fn (Get $get) => District::whereHas('division', fn($q) => $q->where('name', $get('division')))->pluck('name', 'id'))
                                        ->required()
                                        ->searchable(),
                                    TextInput::make('name')
                                        ->label('School Name')
                                        ->required(),
                                    TextInput::make('school_code')
                                        ->label('School ID / BEIS Code')
                                        ->required()
                                        ->unique('schools', 'school_code'),
                                ])->columns(['default' => 2]),
                        ]),
                    Step::make('Initial Employees')
                        ->description('Add key personnel (Admin, ICT Coordinator, etc.)')
                        ->schema([
                            Repeater::make('employees')
                                ->schema([
                                    TextInput::make('name')->required(),
                                    TextInput::make('position')->required(),
                                    TextInput::make('email')->email()->required(),
                                    TextInput::make('contact_number'),
                                ])
                                ->columns(['default' => 2]),
                        ]),
                    Step::make('Primary Equipment')
                        ->description('Record your initial ICT equipment')
                        ->schema([
                            Repeater::make('equipment')
                                ->schema([
                                    TextInput::make('property_number')->label('Property No.'),
                                    Select::make('type')
                                        ->options([
                                            'Laptop' => 'Laptop',
                                            'Desktop' => 'Desktop',
                                            'Tablet' => 'Tablet',
                                            'Server' => 'Server',
                                        ])
                                        ->required(),
                                    TextInput::make('serial_number')->label('Serial No.'),
                                    Select::make('condition')
                                        ->options([
                                            'New' => 'New',
                                            'Good' => 'Good',
                                            'Fair' => 'Fair',
                                            'Defective' => 'Defective',
                                        ])
                                        ->required(),
                                ])
                                ->columns(['default' => 2]),
                        ]),
                ])
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary fi-active">Complete Setup</button>')),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        // 1. Create School
        $school = School::create([
            'name' => $data['name'],
            'school_code' => $data['school_code'],
            'division' => $data['division'],
            'district_id' => $data['district_id'],
            'is_active' => true,
        ]);

        // 2. Link the current user to this school via their Employee record.
        // After identity unification, school_id lives on Employee, not User.
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'school_id' => $school->id,
                'email' => $user->email,
                'employee_number' => 'EMP-'.strtoupper(\Illuminate\Support\Str::random(8)),
                'first_name' => 'School',
                'last_name' => 'Administrator',
                'employment_type' => 'non-teaching',
                'status' => 'active',
            ],
        );
        if ($employee->school_id !== $school->id) {
            $employee->update(['school_id' => $school->id]);
        }

        // Update user school_id for direct access
        $user->update(['school_id' => $school->id]);

        // 3. Create Employees
        if (!empty($data['employees'])) {
            foreach ($data['employees'] as $empData) {
                Employee::create(array_merge($empData, ['school_id' => $school->id]));
            }
        }

        // 4. Create Equipment
        if (!empty($data['equipment'])) {
            foreach ($data['equipment'] as $equData) {
                Equipment::create(array_merge($equData, ['school_id' => $school->id]));
            }
        }

        Notification::make()
            ->title('Setup Completed!')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
