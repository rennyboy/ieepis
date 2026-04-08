<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceLog;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;

class MaintenanceLogSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = Equipment::all();
        $technician = User::first() ?: User::factory()->create();

        if ($equipment->isEmpty()) {
            return;
        }

        foreach ($equipment->random(min(15, $equipment->count())) as $item) {
            MaintenanceLog::create([
                'equipment_id' => $item->id,
                'technician_id' => $technician->id,
                'issue_description' => 'Dummy issue for ' . $item->equipment_type,
                'action_taken' => 'Replaced battery and cleaned internal components.',
                'status' => ['resolved', 'repaired', 'replaced'][rand(0, 2)],
                'date_performed' => Carbon::now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
