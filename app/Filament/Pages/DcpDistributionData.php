<?php

namespace App\Filament\Pages;

use App\Models\District;
use App\Models\Equipment;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class DcpDistributionData
{
    public static function getData(): array
    {
        // Get all active districts with their schools and equipment counts
        return District::with(['schools.equipment'])
            ->get()
            ->map(function ($district) {
                $equipment = $district->schools->flatMap->equipment;
                
                // Filter DCP equipment only
                $dcpEquipment = $equipment->where('is_dcp', true);
                
                // Count by DCP package type if available, otherwise by equipment type
                $l4t = $dcpEquipment->filter(function ($item) {
                    return $item->dcp_package === 'L4T' || 
                           ($item->equipment_type === 'Laptop' && str_contains($item->dcp_package ?? '', 'Teaching'));
                })->count();
                
                $l4nt = $dcpEquipment->filter(function ($item) {
                    return $item->dcp_package === 'L4NT' || 
                           ($item->equipment_type === 'Laptop' && str_contains($item->dcp_package ?? '', 'Non-Teaching'));
                })->count();
                
                $stv = $dcpEquipment->where('equipment_type', 'TV')->count();
                $total = $dcpEquipment->count();
                
                // Population - Count employees in schools of this district
                $psi_pop = $district->schools->flatMap->employees->count();
                
                return [
                    'id' => $district->id,
                    'level' => strtoupper($district->name),
                    'l4nt' => $l4nt,
                    'l4t' => $l4t,
                    'stv' => $stv,
                    'total' => $total,
                    'psi_pop' => $psi_pop ?: 1, // Avoid division by zero
                    'percent_ict' => $psi_pop ? round(($total / $psi_pop) * 100) . '%' : '0%',
                    'percent_l4t' => $psi_pop ? round(($l4t / $psi_pop) * 100) . '%' : '0%',
                    'percent_stv' => $psi_pop ? round(($stv / $psi_pop) * 100) . '%' : '0%',
                ];
            })
            ->toArray();
    }

    public static function getTotals(): array
    {
        $dcpEquipment = Equipment::where('is_dcp', true)->get();
        
        return [
            'l4nt' => $dcpEquipment->filter(function ($item) {
                return $item->dcp_package === 'L4NT' || 
                       ($item->equipment_type === 'Laptop' && str_contains($item->dcp_package ?? '', 'Non-Teaching'));
            })->count(),
            'l4t' => $dcpEquipment->filter(function ($item) {
                return $item->dcp_package === 'L4T' || 
                       ($item->equipment_type === 'Laptop' && str_contains($item->dcp_package ?? '', 'Teaching'));
            })->count(),
            'stv' => $dcpEquipment->where('equipment_type', 'TV')->count(),
            'total' => $dcpEquipment->count(),
            'psi_pop' => Employee::count(),
        ];
    }
}
