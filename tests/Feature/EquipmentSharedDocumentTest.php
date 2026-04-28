<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentSharedDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(): School
    {
        return School::create([
            'name' => 'Test Elementary School',
            'school_code' => 'TES-001',
        ]);
    }

    private function makeEquipment(School $school, string $propertyNo = 'EQ-0001'): Equipment
    {
        return Equipment::create([
            'school_id' => $school->id,
            'property_no' => $propertyNo,
        ]);
    }

    private function attachDocument(
        School $school,
        Equipment $equipment,
        string $title,
        ?string $documentDate = null,
        DocumentType $type = DocumentType::PAR,
    ): Document {
        return Document::create([
            'school_id' => $school->id,
            'equipment_id' => $equipment->id,
            'document_type' => $type->value,
            'title' => $title,
            'file_path' => 'documents/' . md5($title) . '.pdf',
            'document_date' => $documentDate,
        ]);
    }

    public function test_has_shared_document_is_false_when_no_documents_attached(): void
    {
        $school = $this->makeSchool();
        $equipment = $this->makeEquipment($school);

        $this->assertFalse($equipment->hasSharedDocument());
        $this->assertNull($equipment->sharedDocument());
    }

    public function test_shared_document_returns_the_only_attachment(): void
    {
        $school = $this->makeSchool();
        $equipment = $this->makeEquipment($school);
        $doc = $this->attachDocument($school, $equipment, 'Solo PAR', '2026-01-15');

        $this->assertTrue($equipment->fresh()->hasSharedDocument());
        $this->assertSame($doc->id, $equipment->fresh()->sharedDocument()->id);
    }

    public function test_shared_document_resolves_latest_by_document_date(): void
    {
        $school = $this->makeSchool();
        $equipment = $this->makeEquipment($school);

        $this->attachDocument($school, $equipment, 'Old PAR', '2025-01-01');
        $newer = $this->attachDocument($school, $equipment, 'New ICS', '2026-04-01', DocumentType::ICS);
        $this->attachDocument($school, $equipment, 'Older Still', '2024-06-15', DocumentType::IAR);

        $this->assertSame($newer->id, $equipment->fresh()->sharedDocument()->id);
    }

    public function test_shared_document_falls_back_to_created_at_when_dates_are_null(): void
    {
        $school = $this->makeSchool();
        $equipment = $this->makeEquipment($school);

        $this->attachDocument($school, $equipment, 'First', null);
        // Force a later created_at without altering wall-clock by sleeping briefly.
        usleep(1_100_000);
        $second = $this->attachDocument($school, $equipment, 'Second', null);

        $this->assertSame($second->id, $equipment->fresh()->sharedDocument()->id);
    }

    public function test_document_type_cast_round_trips_string_to_enum(): void
    {
        $school = $this->makeSchool();
        $equipment = $this->makeEquipment($school);
        $doc = $this->attachDocument($school, $equipment, 'Round-trip', '2026-04-28', DocumentType::RRPE);

        $reloaded = Document::find($doc->id);

        $this->assertInstanceOf(DocumentType::class, $reloaded->document_type);
        $this->assertSame(DocumentType::RRPE, $reloaded->document_type);
        $this->assertSame('RRPE', $reloaded->document_type->value);
        $this->assertSame('RRPE – Report on Physical Count of Property & Equipment', $reloaded->document_type->label());
    }
}
