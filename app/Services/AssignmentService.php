<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sole owner of the equipment-accountability lifecycle.
 *
 * Filament resources MUST route create/transfer/return operations through this
 * service. The "one active assignment per equipment" invariant and the
 * `equipment.accountability_status` denormalization are only correct when this
 * is the single write path.
 */
class AssignmentService
{
    public function issue(array $data, User $actor): EquipmentAssignment
    {
        return DB::transaction(function () use ($data, $actor): EquipmentAssignment {
            $equipment = Equipment::query()
                ->whereKey($data['equipment_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNoActiveAssignment($equipment);
            $this->assertSameSchool($equipment, $data);

            $assignment = EquipmentAssignment::query()->create([
                'school_id' => $equipment->school_id,
                'equipment_id' => $equipment->id,
                'employee_id' => $data['employee_id'],
                'custodian_id' => $data['custodian_id'] ?? null,
                'assigned_by_id' => $actor->id,
                'assigned_at' => $data['assigned_at'] ?? now()->toDateString(),
                'custodian_received_at' => $data['custodian_received_at'] ?? null,
                'transaction_type' => $data['transaction_type'] ?? 'Issuance',
                'supporting_doc_type' => $data['supporting_doc_type'] ?? null,
                'supporting_doc_no' => $data['supporting_doc_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $equipment->forceFill(['accountability_status' => 'assigned'])->save();

            return $assignment;
        });
    }

    public function transfer(EquipmentAssignment $current, array $newData, User $actor): EquipmentAssignment
    {
        return DB::transaction(function () use ($current, $newData, $actor): EquipmentAssignment {
            $equipment = Equipment::query()
                ->whereKey($current->equipment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->returned_at !== null) {
                throw new RuntimeException('Cannot transfer a closed assignment.');
            }

            $current->forceFill([
                'returned_at' => $newData['assigned_at'] ?? now()->toDateString(),
                'transaction_type' => 'Transfer',
            ])->save();

            return $this->issue(
                array_merge($newData, [
                    'equipment_id' => $equipment->id,
                    'transaction_type' => 'Transfer',
                ]),
                $actor,
            );
        });
    }

    public function return(EquipmentAssignment $current, array $data, User $actor): void
    {
        DB::transaction(function () use ($current, $data, $actor): void {
            $equipment = Equipment::query()
                ->whereKey($current->equipment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->returned_at !== null) {
                throw new RuntimeException('Assignment is already closed.');
            }

            $current->forceFill([
                'returned_at' => $data['returned_at'] ?? now()->toDateString(),
                'transaction_type' => 'Return',
                'notes' => trim(($current->notes ?? '') . "\nReturned by user #{$actor->id}: " . ($data['notes'] ?? '')),
            ])->save();

            $equipment->forceFill(['accountability_status' => 'unassigned'])->save();
        });
    }

    private function assertNoActiveAssignment(Equipment $equipment): void
    {
        $exists = EquipmentAssignment::query()
            ->where('equipment_id', $equipment->id)
            ->whereNull('returned_at')
            ->exists();

        if ($exists) {
            throw new RuntimeException(
                "Equipment #{$equipment->id} already has an active assignment. Close it before issuing a new one.",
            );
        }
    }

    private function assertSameSchool(Equipment $equipment, array $data): void
    {
        if (! isset($data['employee_id'])) {
            return;
        }

        $employee = \App\Models\Employee::query()->whereKey($data['employee_id'])->first();

        if ($employee && $employee->school_id !== $equipment->school_id) {
            throw new RuntimeException(
                "Employee and equipment belong to different schools (employee #{$employee->id}, equipment #{$equipment->id}).",
            );
        }
    }
}
