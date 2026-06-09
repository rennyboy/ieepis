<?php

namespace Tests\Feature;

use App\Enums\PhysicalCountStatus;
use App\Models\PpePhysicalCount;
use App\Models\PpePhysicalCountItem;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpePhysicalCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_ppe_physical_count_item_variance_calculations(): void
    {
        $school = School::create(['name' => 'Test School', 'school_code' => 'SCH-' . rand(10000, 99999)]);
        $count = PpePhysicalCount::create([
            'inventory_date' => now(),
            'school_id' => $school->id,
            'status' => PhysicalCountStatus::Draft,
        ]);

        // Scenario 1: Shortage (Count < Card)
        $item1 = PpePhysicalCountItem::create([
            'physical_count_id' => $count->id,
            'article' => 'Laptop',
            'property_number' => 'PROP-123',
            'unit_of_measure' => 'unit',
            'unit_value' => 1000.00,
            'quantity_property_card' => 5,
            'quantity_physical_count' => 3,
        ]);

        $this->assertSame(2, $item1->shortage_quantity);
        $this->assertEquals(2000.00, $item1->shortage_value);
        $this->assertSame(0, $item1->overage_quantity);
        $this->assertEquals(0.00, $item1->overage_value);

        // Scenario 2: Overage (Count > Card)
        $item2 = PpePhysicalCountItem::create([
            'physical_count_id' => $count->id,
            'article' => 'Desktop',
            'property_number' => 'PROP-456',
            'unit_of_measure' => 'unit',
            'unit_value' => 1500.00,
            'quantity_property_card' => 2,
            'quantity_physical_count' => 5,
        ]);

        $this->assertSame(0, $item2->shortage_quantity);
        $this->assertEquals(0.00, $item2->shortage_value);
        $this->assertSame(3, $item2->overage_quantity);
        $this->assertEquals(4500.00, $item2->overage_value);

        // Scenario 3: Balanced (Count == Card)
        $item3 = PpePhysicalCountItem::create([
            'physical_count_id' => $count->id,
            'article' => 'Router',
            'property_number' => 'PROP-789',
            'unit_of_measure' => 'pcs',
            'unit_value' => 500.00,
            'quantity_property_card' => 4,
            'quantity_physical_count' => 4,
        ]);

        $this->assertSame(0, $item3->shortage_quantity);
        $this->assertEquals(0.00, $item3->shortage_value);
        $this->assertSame(0, $item3->overage_quantity);
        $this->assertEquals(0.00, $item3->overage_value);
    }

    public function test_total_sums_on_physical_count_header(): void
    {
        $school = School::create(['name' => 'Test School', 'school_code' => 'SCH-' . rand(10000, 99999)]);
        $count = PpePhysicalCount::create([
            'inventory_date' => now(),
            'school_id' => $school->id,
            'status' => PhysicalCountStatus::Draft,
        ]);

        PpePhysicalCountItem::create([
            'physical_count_id' => $count->id,
            'article' => 'Laptop',
            'property_number' => 'PROP-1',
            'unit_of_measure' => 'unit',
            'unit_value' => 1000.00,
            'quantity_property_card' => 5,
            'quantity_physical_count' => 3,
        ]);

        PpePhysicalCountItem::create([
            'physical_count_id' => $count->id,
            'article' => 'Desktop',
            'property_number' => 'PROP-2',
            'unit_of_measure' => 'unit',
            'unit_value' => 2000.00,
            'quantity_property_card' => 2,
            'quantity_physical_count' => 4,
        ]);

        $this->assertEquals(2000.00, $count->total_shortage_value);
        $this->assertEquals(4000.00, $count->total_overage_value);
        $this->assertSame(2, $count->total_items_count);
    }

    public function test_count_number_generation(): void
    {
        $school = School::create(['name' => 'Test School', 'school_code' => 'SCH-' . rand(10000, 99999)]);
        $count1 = PpePhysicalCount::create([
            'inventory_date' => now(),
            'school_id' => $school->id,
            'status' => PhysicalCountStatus::Draft,
        ]);

        $count2 = PpePhysicalCount::create([
            'inventory_date' => now(),
            'school_id' => $school->id,
            'status' => PhysicalCountStatus::Draft,
        ]);

        $year = now()->year;
        $this->assertSame("PC-{$year}-0001", $count1->count_number);
        $this->assertSame("PC-{$year}-0002", $count2->count_number);
    }
}
