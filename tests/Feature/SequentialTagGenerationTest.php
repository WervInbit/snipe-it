<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\Company;
use App\Models\User;
use App\Services\ComponentTagGenerator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SequentialTagGenerationTest extends TestCase
{
    public function testReservationsAdvanceWithoutSavingAndUseIndependentSequences(): void
    {
        $this->assertSame('INBIT-AA0001', Asset::generateTag());
        $this->assertSame('INBIT-AA0002', Asset::generateTag());
        $this->assertSame('INBIT-C-AA0001', app(ComponentTagGenerator::class)->generate());
        $this->assertSame('INBIT-C-AA0002', app(ComponentTagGenerator::class)->generate());
        $this->assertSame('INBIT-AA0003', Asset::generateTag());
    }

    public function testGenerationSkipsExistingAndDeletedTagsInBothTables(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'INBIT-AA0001']);
        Asset::factory()->create(['asset_tag' => 'INBIT-C-AA0001', 'deleted_at' => now()]);
        ComponentInstance::factory()->create(['component_tag' => 'INBIT-AA0002', 'deleted_at' => now()]);
        ComponentInstance::factory()->create(['component_tag' => 'INBIT-C-AA0002']);

        $this->assertSame('INBIT-AA0003', Asset::generateTag());
        $this->assertSame('INBIT-C-AA0003', app(ComponentTagGenerator::class)->generate());
        $this->assertSame('INBIT-AA0001', $asset->fresh()->asset_tag);
    }

    public function testCollisionsAreCheckedOutsideTheCurrentUsersCompany(): void
    {
        $this->settings->set(['full_multiple_companies_support' => 1]);
        Asset::factory()->create(['asset_tag' => 'INBIT-AA0001', 'company_id' => Company::factory()]);
        ComponentInstance::factory()->create(['component_tag' => 'INBIT-C-AA0001', 'company_id' => Company::factory()]);
        $this->actingAs(User::factory()->create(['company_id' => Company::factory()]));

        $this->assertSame('INBIT-AA0002', Asset::generateTag());
        $this->assertSame('INBIT-C-AA0002', app(ComponentTagGenerator::class)->generate());
    }

    public function testCreateFormsReserveDistinctTagsAndReuseValidationInput(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $this->get(route('hardware.create'))->assertOk()->assertSee('value="INBIT-AA0001"', false);
        $this->get(route('hardware.create'))->assertOk()->assertSee('value="INBIT-AA0002"', false);
        $this->withSession(['_old_input' => ['asset_tags' => [1 => 'INBIT-AA0001']]])
            ->get(route('hardware.create'))->assertOk()->assertSee('value="INBIT-AA0001"', false);

        $this->assertSame('INBIT-AA0003', Asset::generateTag());
    }

    public function testNumbersRollOverBeforeLettersAndCarryBetweenLetterPositions(): void
    {
        DB::table('identifier_sequences')->where('prefix', 'INBIT-')->update(['next_value' => 9999]);
        $this->assertSame('INBIT-AA9999', Asset::generateTag());
        $this->assertSame('INBIT-AB0001', Asset::generateTag());

        DB::table('identifier_sequences')->where('prefix', 'INBIT-C-')->update(['next_value' => 26 * 9999]);
        $this->assertSame('INBIT-C-AZ9999', app(ComponentTagGenerator::class)->generate());
        $this->assertSame('INBIT-C-BA0001', app(ComponentTagGenerator::class)->generate());
    }

    public function testExhaustionFailsInsteadOfReusingIdentifiers(): void
    {
        DB::table('identifier_sequences')->where('prefix', 'INBIT-')->update(['next_value' => 26 * 26 * 9999]);
        $this->assertSame('INBIT-ZZ9999', Asset::generateTag());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tag sequence is missing or exhausted: INBIT-');
        Asset::generateTag();
    }

    public function testAssetAndComponentCreationUseTheSequenceWhenTagsAreBlank(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => '']);
        $component = ComponentInstance::factory()->create(['component_tag' => '']);

        $this->assertSame('INBIT-AA0001', $asset->asset_tag);
        $this->assertSame('INBIT-C-AA0001', $component->component_tag);
        $this->assertNotEmpty($component->qr_uid);
    }

    public function testCustomAssetTagsAreAcceptedWithoutConsumingTheSequence(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => 'Workshop-custom-42'],
                'asset_tag_case_override' => [1 => '1'],
            ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Workshop-custom-42', Asset::sole()->asset_tag);
        $this->assertSame('INBIT-AA0001', Asset::generateTag());
    }

    public function testBatchCreationCombinesReservedGeneratedAndCustomTags(): void
    {
        $reserved = Asset::generateTag();
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => $reserved, 2 => '', 3 => 'CUSTOM-BATCH-42', 4 => ''],
            ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([
            'INBIT-AA0001', 'INBIT-AA0002', 'CUSTOM-BATCH-42', 'INBIT-AA0003',
        ], Asset::orderBy('id')->pluck('asset_tag')->all());
    }

    public function testEditingExistingRecordsPreservesCustomTagsAndQrIdentity(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'LEGACY-DEVICE-42']);
        $component = ComponentInstance::factory()->create(['component_tag' => 'CUSTOM-PART-17']);
        $qrUid = $component->qr_uid;

        $asset->update(['name' => 'Updated device name']);
        $component->update(['display_name' => 'Updated component name']);

        $this->assertSame('LEGACY-DEVICE-42', $asset->fresh()->asset_tag);
        $this->assertSame('CUSTOM-PART-17', $component->fresh()->component_tag);
        $this->assertSame($qrUid, $component->fresh()->qr_uid);
        $this->assertSame('INBIT-AA0001', Asset::generateTag());
        $this->assertSame('INBIT-C-AA0001', app(ComponentTagGenerator::class)->generate());
    }

    public function testSparseBatchRowsKeepTheirTagsSerialsAndConfirmationTogether(): void
    {
        Asset::factory()->create(['asset_tag' => 'SHARED', 'serial' => 'SHARED-SERIAL']);
        $this->actingAs(User::factory()->admin()->create())->post(route('hardware.store'), [
            'asset_tags' => [1 => 'CUSTOM-FIRST', 3 => 'SHARED', 4 => ''],
            'serials' => [1 => 'FIRST-SERIAL', 3 => 'SHARED-SERIAL', 4 => 'LAST-SERIAL'],
            'allow_duplicate_tags' => [3 => 1], 'allow_duplicate_serials' => [3 => 1],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(4, Asset::count());
        $this->assertSame('CUSTOM-FIRST', Asset::where('serial', 'FIRST-SERIAL')->sole()->asset_tag);
        $this->assertSame(2, Asset::where('asset_tag', 'SHARED')->where('serial', 'SHARED-SERIAL')->count());
        $this->assertSame('INBIT-AA0001', Asset::where('serial', 'LAST-SERIAL')->sole()->asset_tag);
    }
}
