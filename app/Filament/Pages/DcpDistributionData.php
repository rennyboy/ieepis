<?php

namespace App\Filament\Pages;

use App\Models\District;
use App\Models\Equipment;
use App\Models\Employee;
use App\Scopes\SchoolScope;
use Illuminate\Support\Facades\DB;

class DcpDistributionData
{
    public static function getData(): array
    {
        return DB::table('districts as d')
            ->select([
                'd.id',
                'd.name',
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN equipment e ON e.school_id = s.id
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND e.is_dcp = true AND e.deleted_at IS NULL
                    AND (e.dcp_package = ? OR (e.equipment_type = ? AND LOWER(e.dcp_package) LIKE ?))
                ) as l4t'),
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN equipment e ON e.school_id = s.id
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND e.is_dcp = true AND e.deleted_at IS NULL
                    AND (e.dcp_package = ? OR (e.equipment_type = ? AND LOWER(e.dcp_package) LIKE ?))
                ) as l4nt'),
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN equipment e ON e.school_id = s.id
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND e.is_dcp = true AND e.deleted_at IS NULL
                    AND (e.dcp_package = ? OR LOWER(e.equipment_type) LIKE ?)
                ) as stv'),
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN equipment e ON e.school_id = s.id
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND e.is_dcp = true AND e.deleted_at IS NULL
                ) as total'),
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN employees emp ON emp.school_id = s.id
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND emp.deleted_at IS NULL
                ) as psi_pop'),
            ])
            ->addBinding(['L4T', 'Laptop', '%teaching%', 'L4NT', 'Laptop', '%non-teaching%', 'STV', '%tv%'], 'select')
            ->orderBy('d.name')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'level' => strtoupper($row->name),
                'l4nt' => (int) $row->l4nt,
                'l4t' => (int) $row->l4t,
                'stv' => (int) $row->stv,
                'total' => (int) $row->total,
                'psi_pop' => (int) $row->psi_pop,
                'percent_ict' => $row->psi_pop ? round(($row->total / $row->psi_pop) * 100) . '%' : '0%',
                'percent_l4t' => $row->psi_pop ? round(($row->l4t / $row->psi_pop) * 100) . '%' : '0%',
                'percent_stv' => $row->psi_pop ? round(($row->stv / $row->psi_pop) * 100) . '%' : '0%',
            ])
            ->toArray();
    }

    public static function getTotals(): array
    {
        $totals = DB::table('equipment')
            ->where('is_dcp', true)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(CASE WHEN dcp_package = ? OR (equipment_type = ? AND LOWER(dcp_package) LIKE ?) THEN 1 END) as l4t,
                COUNT(CASE WHEN dcp_package = ? OR (equipment_type = ? AND LOWER(dcp_package) LIKE ?) THEN 1 END) as l4nt,
                COUNT(CASE WHEN dcp_package = ? OR LOWER(equipment_type) LIKE ? THEN 1 END) as stv,
                COUNT(*) as total
            ', ['L4T', 'Laptop', '%teaching%', 'L4NT', 'Laptop', '%non-teaching%', 'STV', '%tv%'])
            ->first();

        return [
            'l4t' => (int) $totals->l4t,
            'l4nt' => (int) $totals->l4nt,
            'stv' => (int) $totals->stv,
            'total' => (int) $totals->total,
            'psi_pop' => Employee::withoutGlobalScope(SchoolScope::class)->count(),
        ];
    }
}
