<?php

namespace App\Filament\Pages;

use App\Models\School;
use App\Models\Employee;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
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
                    Step::make('School Selection')
                        ->description('Select your school')
                        ->schema([
                            Section::make('Select Your School')
                                ->schema([
                                    Select::make('school_id')
                                        ->label('Select School')
                                        ->options(fn () => \App\Models\School::where('status', 'active')
                                            ->orderBy('name')
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->searchable(),
                                ]),
                        ]),
                    Step::make('Your Profile')
                        ->description('Your personnel information')
                        ->schema([
                            Section::make('Administrator Details')
                                ->schema([
                                    TextInput::make('first_name')
                                        ->label('First Name')
                                        ->required(),
                                    TextInput::make('last_name')
                                        ->label('Last Name')
                                        ->required(),
                                    TextInput::make('position')
                                        ->label('Position')
                                        ->required()
                                        ->default('School Administrator'),
                                    TextInput::make('employee_number')
                                        ->label('Employee Number')
                                        ->required(),
                                ])->columns(['default' => 2]),
                        ]),
                ])
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary fi-active">Complete Setup</button>')),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $schoolId = $data['school_id'];

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $employee = Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'school_id' => $schoolId,
                'email' => $user->email,
                'employee_number' => $data['employee_number'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'position' => $data['position'],
                'employment_type' => 'non-teaching',
                'status' => 'active',
            ],
        );

        if ($employee->school_id !== $schoolId) {
            $employee->update(['school_id' => $schoolId]);
        }

        $user->update(['school_id' => $schoolId]);

        Notification::make()
            ->title('Setup Completed!')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
