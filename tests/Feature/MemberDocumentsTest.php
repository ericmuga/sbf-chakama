<?php

namespace Tests\Feature;

use App\Models\Dependant;
use App\Models\Document;
use App\Models\Member;
use App\Models\NextOfKin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_created_with_all_nullable_fields(): void
    {
        $member = Member::factory()->create();

        $document = Document::create([
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
        ]);

        $this->assertDatabaseHas('bus_documents', [
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
            'document_type' => null,
            'document_no' => null,
            'file_path' => null,
        ]);
        $this->assertNotNull($document->id);
    }

    public function test_document_derives_original_name_from_file_path(): void
    {
        $member = Member::factory()->create();

        $document = Document::create([
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
            'file_path' => 'member-documents/national_id_scan.pdf',
        ]);

        $this->assertEquals('national_id_scan.pdf', $document->original_name);
    }

    public function test_document_sets_default_disk(): void
    {
        $member = Member::factory()->create();

        $document = Document::create([
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
        ]);

        $this->assertEquals('local', $document->disk);
    }

    public function test_document_stores_document_no(): void
    {
        $member = Member::factory()->create();

        Document::create([
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
            'document_type' => 'national_id',
            'document_no' => 'ID-12345678',
        ]);

        $this->assertDatabaseHas('bus_documents', [
            'documentable_id' => $member->id,
            'document_no' => 'ID-12345678',
        ]);
    }

    public function test_dependant_can_have_documents(): void
    {
        $member = Member::factory()->create();
        $dependant = Dependant::factory()->create(['member_id' => $member->id]);

        $document = Document::create([
            'documentable_type' => Dependant::class,
            'documentable_id' => $dependant->id,
            'document_type' => 'birth_cert',
            'document_no' => 'BC-2010-001',
        ]);

        $this->assertCount(1, $dependant->documents);
        $this->assertEquals('birth_cert', $dependant->documents->first()->document_type);
        $this->assertEquals($dependant->id, $document->documentable->id);
    }

    public function test_next_of_kin_can_have_documents(): void
    {
        $member = Member::factory()->create();
        $nextOfKin = NextOfKin::factory()->create(['member_id' => $member->id]);

        $document = Document::create([
            'documentable_type' => NextOfKin::class,
            'documentable_id' => $nextOfKin->id,
            'document_type' => 'passport',
            'document_no' => 'A12345678',
        ]);

        $this->assertCount(1, $nextOfKin->documents);
        $this->assertEquals('passport', $nextOfKin->documents->first()->document_type);
        $this->assertEquals($nextOfKin->id, $document->documentable->id);
    }

    public function test_document_types_are_supported(): void
    {
        $member = Member::factory()->create();

        foreach (['national_id', 'pin', 'passport', 'birth_cert'] as $type) {
            Document::create([
                'documentable_type' => Member::class,
                'documentable_id' => $member->id,
                'document_type' => $type,
            ]);
        }

        $this->assertCount(4, $member->documents);
    }
}
