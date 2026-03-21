<?php

namespace Tests\Feature;

use App\Models\Dependant;
use App\Models\Document;
use App\Models\Member;
use App\Models\NextOfKin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDependantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_have_dependants(): void
    {
        $member = Member::factory()->create();
        $dependant = Dependant::factory()->create(['member_id' => $member->id]);

        $this->assertCount(1, $member->dependants);
        $this->assertEquals($dependant->id, $member->dependants->first()->id);
    }

    public function test_member_can_have_next_of_kin(): void
    {
        $member = Member::factory()->create();
        $nextOfKin = NextOfKin::factory()->create(['member_id' => $member->id]);

        $this->assertCount(1, $member->nextOfKin);
        $this->assertEquals($nextOfKin->id, $member->nextOfKin->first()->id);
    }

    public function test_dependant_does_not_have_member_number(): void
    {
        $dependant = Dependant::factory()->create();

        $this->assertNull($dependant->no);
    }

    public function test_next_of_kin_type_is_set_automatically(): void
    {
        $member = Member::factory()->create();
        $nextOfKin = new NextOfKin([
            'member_id' => $member->id,
            'name' => 'Jane Doe',
            'relationship' => 'Spouse',
        ]);
        $nextOfKin->save();

        $this->assertEquals('next_of_kin', $nextOfKin->type);
    }

    public function test_dependant_type_is_set_automatically(): void
    {
        $member = Member::factory()->create();
        $dependant = new Dependant([
            'member_id' => $member->id,
            'name' => 'Child One',
            'relationship' => 'Child',
        ]);
        $dependant->save();

        $this->assertEquals('dependant', $dependant->type);
    }

    public function test_dependants_do_not_appear_in_member_query(): void
    {
        $member = Member::factory()->create();
        Dependant::factory()->create(['member_id' => $member->id]);

        $memberCount = Member::members()->count();
        $dependantCount = Dependant::count();

        $this->assertEquals(1, $memberCount);
        $this->assertEquals(1, $dependantCount);
        $this->assertNotContains('dependant', Member::members()->pluck('type')->toArray());
    }

    public function test_document_can_be_uploaded_for_member(): void
    {
        $member = Member::factory()->create();
        $document = Document::create([
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
            'document_type' => 'national_id',
            'file_path' => 'documents/national-ids/test.pdf',
            'disk' => 'local',
            'original_name' => 'national_id.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
        ]);

        $this->assertCount(1, $member->documents);
        $this->assertEquals('national_id', $member->documents->first()->document_type);
        $this->assertEquals($member->id, $document->documentable->id);
    }
}
