<?php

namespace App\Filament\Pages;

use App\Enums\EmployeeStatus;
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
                // L4T / L4NT are DCP laptops classified by the employment_type of
                // the person actually holding them: the custodian/end-user when
                // set, else the accountable officer (COALESCE below). The teaching
                // split lives on the employee, not the equipment row, so it can
                // only come through the active assignment.
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN equipment e ON e.school_id = s.id AND e.is_dcp = true AND e.deleted_at IS NULL
                    JOIN equipment_assignments ea ON ea.equipment_id = e.id AND ea.returned_at IS NULL AND ea.deleted_at IS NULL
                    JOIN employees emp ON emp.id = COALESCE(ea.custodian_id, ea.employee_id) AND emp.deleted_at IS NULL
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND LOWER(e.equipment_type) = ? AND emp.employment_type = ?
                ) as l4t'),
                DB::raw('(SELECT COUNT(*) FROM schools s
                    JOIN equipment e ON e.school_id = s.id AND e.is_dcp = true AND e.deleted_at IS NULL
                    JOIN equipment_assignments ea ON ea.equipment_id = e.id AND ea.returned_at IS NULL AND ea.deleted_at IS NULL
                    JOIN employees emp ON emp.id = COALESCE(ea.custodian_id, ea.employee_id) AND emp.deleted_at IS NULL
                    WHERE s.district_id = d.id AND s.deleted_at IS NULL
                    AND LOWER(e.equipment_type) = ? AND emp.employment_type = ?
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
                    AND emp.deleted_at IS NULL AND emp.status = ?
                ) as psi_pop'),
            ])
            ->addBinding([
                'laptop', 'teaching',
                'laptop', 'non-teaching',
                'STV', '%tv%',
                EmployeeStatus::Active->value,
            ], 'select')
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
        // One active assignment per equipment is enforced on import, so the
        // left joins do not multiply rows; equipment without an assignment
        // still counts toward `total` (and simply isn't an L4T/L4NT).
        $totals = DB::table('equipment as e')
            ->whereNull('e.deleted_at')
            ->where('e.is_dcp', true)
            ->leftJoin('equipment_assignments as ea', function ($join) {
                $join->on('ea.equipment_id', '=', 'e.id')
                    ->whereNull('ea.returned_at')
                    ->whereNull('ea.deleted_at');
            })
            ->leftJoin('employees as emp', function ($join) {
                $join->on('emp.id', '=', DB::raw('COALESCE(ea.custodian_id, ea.employee_id)'))
                    ->whereNull('emp.deleted_at');
            })
            ->selectRaw('
                COUNT(CASE WHEN LOWER(e.equipment_type) = ? AND emp.employment_type = ? THEN 1 END) as l4t,
                COUNT(CASE WHEN LOWER(e.equipment_type) = ? AND emp.employment_type = ? THEN 1 END) as l4nt,
                COUNT(CASE WHEN e.dcp_package = ? OR LOWER(e.equipment_type) LIKE ? THEN 1 END) as stv,
                COUNT(DISTINCT e.id) as total
            ', ['laptop', 'teaching', 'laptop', 'non-teaching', 'STV', '%tv%'])
            ->first();

        return [
            'l4t' => (int) $totals->l4t,
            'l4nt' => (int) $totals->l4nt,
            'stv' => (int) $totals->stv,
            'total' => (int) $totals->total,
            'psi_pop' => Employee::withoutGlobalScope(SchoolScope::class)->active()->count(),
        ];
    }
}
