# IEEPIS Technical Guide

## Introduction

The IEEPIS (ICT Equipment and Employee Profile Information System) project is built using the Laravel 11 framework and the Filament admin panel. This guide will dive into the technical details and implementation of the core concepts that power the system.

## Models and Eloquent

At the heart of the IEEPIS application are the Eloquent models, which represent the database tables and provide an intuitive way to interact with the data.

### School Model
The `School` model (`app/Models/School.php`) is responsible for managing school-related information, such as the school name, code, district, and geographic location. It has the following key properties and relationships:

```php
class School extends Model
{
    protected $fillable = [
        'name', 'code', 'district', 'latitude', 'longitude',
        // other school-specific fields
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    // Other relationships, such as documents, tickets, and internet connections
}
```

The `employees()` and `equipment()` methods define the one-to-many relationships between a school and its employees and equipment, respectively.

### Employee Model
The `Employee` model (`app/Models/Employee.php`) represents the personnel directory, storing details such as employee ID, name, position, department, and employment type. It also tracks OIC (Officer-in-Charge) designations and separation records.

```php
class Employee extends Model
{
    protected $fillable = [
        'employee_id', 'first_name', 'last_name', 'position', 'department',
        'employment_type', 'oic_designation', 'separation_date', 'separation_reason',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'equipment_assignments');
    }
}
```

The `school()` relationship links an employee to their associated school, while the `equipment()` relationship, through the `equipment_assignments` pivot table, tracks the equipment assigned to each employee.

### Equipment Model
The `Equipment` model (`app/Models/Equipment.php`) is responsible for managing the ICT equipment inventory, including properties such as the property number, serial number, item type, acquisition details, warranty information, and condition.

```php
class Equipment extends Model
{
    protected $fillable = [
        'property_no', 'old_property_no', 'serial_no', 'item_type', 'dcp_package',
        'gl_sl_code', 'uacs_code', 'category', 'acquisition_cost', 'acquisition_date',
        'acquisition_mode', 'warranty_expiry', 'condition', 'functional_status',
        // other equipment-specific fields
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'equipment_assignments');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
```

The `school()` relationship links the equipment to the owning school, while the `employees()` relationship, through the `equipment_assignments` pivot table, tracks the employees who have been assigned the equipment. The `documents()` and `tickets()` relationships connect the equipment to any associated documents and support tickets.

### Ticket Model
The `Ticket` model (`app/Models/Ticket.php`) represents the support ticket system, storing details such as the auto-generated ticket number, priority, status, and links to the school, equipment, reporter, and assigned technician.

```php
class Ticket extends Model
{
    protected $fillable = [
        'number', 'priority', 'status', 'description', 'school_id',
        'equipment_id', 'reported_by', 'assigned_to',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
```

The relationships in the `Ticket` model connect the ticket to the school, equipment, reporter, and assigned technician.

## Routing and Controllers

The routing and controller logic in the IEEPIS project are defined in the standard Laravel way, with the main web routes located in the `routes/web.php` file.

### Web Routes
The web routes define the URL endpoints and map them to the appropriate controller actions. For example, the routes for the public-facing blog feature:

```php
Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [PostController::class, 'show'])->name('blog.show');
```

These routes map the `/blog` and `/blog/{post}` URLs to the `index()` and `show()` actions in the `PostController`, respectively.

### Controllers
The controllers handle the incoming requests, interact with the models, and return the appropriate response. Here's an example of the `PostController`:

```php
class PostController extends Controller
{
    public function index()
    {
        $posts = Post::whereNotNull('published_at')
                     ->orderByDesc('published_at')
                     ->paginate(10);

        return view('blog.index', compact('posts'));
    }

    public function show(Post $post)
    {
        return view('blog.show', compact('post'));
    }
}
```

The `index()` action fetches the published blog posts, paginates them, and passes them to the `blog.index` Blade view. The `show()` action uses route model binding to retrieve the `Post` model based on the `{post}` parameter in the URL and passes it to the `blog.show` view.

## Filament Admin Panel

The IEEPIS project utilizes the Filament admin panel to provide a user-friendly interface for managing the application's data and functionality. Filament is built on top of the TALL (Tailwind, Alpine.js, Laravel, Livewire) stack, providing a powerful and flexible way to create custom admin panels.

### Filament Resources
Filament resources represent the different data models that can be managed within the admin panel. Each resource has its own set of pages, such as the list, create, edit, and delete pages.

Here's an example of the `EquipmentResource`:

```php
class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-desktop-computer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form fields for equipment
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Table columns for equipment
            ])
            ->filters([
                // Filters for the equipment table
            ])
            ->actions([
                // Actions for the equipment table
            ]);
    }
}
```

The `EquipmentResource` class defines the form fields, table columns, filters, and actions for the equipment management functionality within the Filament admin panel.

### Filament Pages
In addition to the resource-based pages, Filament also supports custom pages, which can be used to create more complex or specialized functionality.

For example, the `DcpDashboard` page (`app/Filament/Pages/DcpDashboard.php`) provides the dashboard functionality for the IEEPIS project, displaying real-time statistics, charts, and other widgets.

```php
class DcpDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            EquipmentBySchoolWidget::class,
            EquipmentConditionWidget::class,
            OpenTicketsWidget::class,
        ];
    }
}
```

The `getWidgets()` method returns an array of widget classes that are displayed on the dashboard page.

## Observers and Events

The IEEPIS project utilizes Laravel's observer pattern to handle various events and perform related actions. One example is the `TicketObserver`, which listens for ticket creation and update events and sends notifications to the appropriate support staff.

```php
class TicketObserver
{
    public function created(Ticket $ticket)
    {
        $this->sendNotification($ticket, 'created');
    }

    public function updated(Ticket $ticket)
    {
        if ($ticket->isDirty(['priority', 'status'])) {
            $this->sendNotification($ticket, 'updated');
        }
    }

    protected function sendNotification(Ticket $ticket, string $event)
    {
        // Determine the notification recipients based on user roles
        $recipients = $this->getNotificationRecipients($ticket);

        // Format the notification message and send it to the recipients
        Notification::send($recipients, new TicketNotification($ticket, $event));
    }

    protected function getNotificationRecipients(Ticket $ticket): Collection
    {
        // Retrieve the appropriate users based on their roles
        return User::role(['super-admin', 'sdo-admin', 'technician'])
                   ->get();
    }
}
```

The `created()` and `updated()` methods in the `TicketObserver` class listen for the corresponding events and trigger the `sendNotification()` method, which determines the appropriate recipients and sends the notification.

## Authorization and Permissions

The IEEPIS project utilizes the Spatie Laravel Permission package to handle user roles and permissions. This allows for fine-grained control over what actions each user can perform within the application.

### Role-Based Access Control (RBAC)
The project defines several user roles, such as Super Admin, SDO Admin, School Admin, Technician, and Viewer. Each role is assigned a set of permissions that determine what the user can do.

The roles and their associated permissions are defined in the `database/seeders/RoleSeeder.php` file:

```php
class RoleSeeder extends Seeder
{
    public function run()
    {
        // Define the roles and their permissions
        $roles = [
            'super-admin' => ['*'],
            'sdo-admin' => ['equipment.view', 'equipment.create', 'equipment.update', 'equipment.delete'],
            'school-admin' => ['equipment.view', 'equipment.create', 'equipment.update'],
            'technician' => ['equipment.view', 'equipment.update'],
            'viewer' => ['equipment.view'],
        ];

        // Create the roles and assign the permissions
        foreach ($roles as $role => $permissions) {
            $role = Role::create(['name' => $role]);
            $role->givePermissionTo($permissions);
        }
    }
}
```

### Permissions in the Filament Resources
The Filament resources leverage the Spatie permissions to restrict access to certain actions based on the user's role. For example, in the `EquipmentResource`, the `getEloquentQuery()` method is used to scope the equipment data based on the user's role:

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->when(
        auth()->user()->hasRole('school-admin'),
        fn ($query) => $query->where('school_id', auth()->user()->school_id)
    );
}
```

This ensures that a School Admin can only see the equipment belonging to their own school, while a Super Admin or SDO Admin can see equipment from all schools.

## Conclusion

This technical guide has provided an overview of the core concepts and implementation details of the IEEPIS project, including the use of Eloquent models, routing and controllers, the Filament admin panel, observers and events, and authorization and permissions.

By understanding these fundamental aspects of the project, you'll be better equipped to modify and extend the system's functionality, whether it's adding new features, updating existing ones, or troubleshooting any issues that may arise.