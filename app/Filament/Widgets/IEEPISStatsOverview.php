<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\Employee;
use App\Models\School;
use App\Models\Ticket;
use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IEEPISStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalEquipment   = Equipment::count();
        $assignedEquipment= Equipment::where('accountability_status', 'assigned')->orWhere('accountability_status', 'Normal')->count();
        $unassigned       = Equipment::where('accountability_status', 'unassigned')->count();
        $nonFunctional    = Equipment::where('is_functional', false)->count();
        $forDisposal      = Equipment::where('accountability_status', 'For Disposal')->count();
        $dcpEquipment     = Equipment::where('is_dcp', true)->count();
        $openTickets      = Ticket::whereIn('status', ['open', 'in-progress'])->count();
        $totalEmployees   = Employee::where('status', 'active')->count();
        $totalSchools     = School::where('status', 'active')->count();
        $totalDocuments   = Document::count();
        $warrantyExpiring = Equipment::where('under_warranty', true)
            ->whereNotNull('warranty_end_date')
            ->whereBetween('warranty_end_date', [now(), now()->addDays(90)])
            ->count();

        return [
            Stat::make('Total ICT Equipment', $totalEquipment)
                ->description("{$dcpEquipment} DCP items · {$totalSchools} schools")
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('primary')
                ->chart([4, 8, 6, 10, 12, 15, $totalEquipment]),

            Stat::make('Assigned Equipment', $assignedEquipment)
                ->description(
                    $totalEquipment > 0
                        ? round(($assignedEquipment / $totalEquipment) * 100) . '% utilization rate'
                        : 'No equipment'
                )
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([2, 5, 8, 10, $assignedEquipment]),

            Stat::make('Unassigned Equipment', $unassigned)
                ->description("Available for assignment")
                ->descriptionIcon('heroicon-m-inbox')
                ->color('warning'),

            Stat::make('Non-Functional / For Disposal', $nonFunctional + $forDisposal)
                ->description("{$nonFunctional} non-functional · {$forDisposal} for disposal")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Active Personnel', $totalEmployees)
                ->description("Accountable officers & staff")
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Open Tickets', $openTickets)
                ->description($openTickets > 0 ? 'Requires attention' : 'All clear!')
                ->descriptionIcon($openTickets > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle')
                ->color($openTickets > 0 ? 'danger' : 'success'),

            Stat::make('Documents Filed', $totalDocuments)
                ->description("PAR, ICS, IAR and other documents")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Warranties Expiring', $warrantyExpiring)
                ->description("Within the next 90 days")
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($warrantyExpiring > 0 ? 'warning' : 'success'),
        ];
    }
}
