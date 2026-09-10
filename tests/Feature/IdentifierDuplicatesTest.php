<?php

namespace Tests\Feature;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\QrLabelService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

use function Livewire\invade;

class IdentifierDuplicatesTest extends TestCase
{
    public function testAssetRequiresIndependentConfirmationForTagAndSerial(): void
    {
        Asset::factory()->create(['asset_tag' => 'SHARED-TAG', 'serial' => 'SHARED-SERIAL']);
        $other = Asset::factory()->make(['asset_tag' => 'SHARED-TAG', 'serial' => 'SHARED-SERIAL']);
        $this->assertFalse($other->save());
        $this->assertTrue($other->getErrors()->has('asset_tag'));
        $this->assertTrue($other->getErrors()->has('serial'));
        $this->assertFalse($other->allowDuplicateTag()->save());
        $this->assertTrue($other->allowDuplicateSerial()->save());
        $this->assertTrue($other->update(['name' => 'Ordinary edit']));
        $this->assertTrue(Actionlog::where('item_type', Asset::class)->where('item_id', $other->id)
            ->where('note', 'like', 'Duplicate identifier explicitly accepted:%')->exists());
    }

    public function testComponentRequiresConfirmationForCrossTypeDuplicates(): void
    {
        Asset::factory()->create(['asset_tag' => 'SHARED-TAG', 'serial' => 'SHARED-SERIAL']);
        $component = ComponentInstance::factory()->make(['component_tag' => 'SHARED-TAG', 'serial' => 'SHARED-SERIAL']);
        try {
            $component->save();
            $this->fail('Unconfirmed component duplicates were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('component_tag', $exception->errors());
            $this->assertArrayHasKey('serial', $exception->errors());
        }
        $this->assertTrue($component->allowDuplicateTag()->confirmDuplicateSerial()->save());
    }

    public function testWebAssetCreationRequiresAndAcceptsExplicitConfirmations(): void
    {
        Asset::factory()->create(['asset_tag' => 'DUPLICATE', 'serial' => 'SERIAL-DUP']);
        $this->actingAs(User::factory()->admin()->create());
        $payload = ['asset_tags' => [1 => 'DUPLICATE'], 'serials' => [1 => 'SERIAL-DUP']];
        $this->post(route('hardware.store'), $payload)->assertRedirect();
        $this->assertSame(1, Asset::where('asset_tag', 'DUPLICATE')->count());
        $this->post(route('hardware.store'), $payload + [
            'allow_duplicate_tags' => [1 => 1], 'allow_duplicate_serials' => [1 => 1],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, Asset::where('asset_tag', 'DUPLICATE')->count());
    }

    public function testComponentApiAcceptsConfirmedDuplicatesAndKeepsUniqueQrUids(): void
    {
        $first = ComponentInstance::factory()->create(['component_tag' => 'DUPLICATE', 'serial' => 'SERIAL-DUP']);
        $this->actingAsForApi(User::factory()->superuser()->create());
        $payload = ['component_tag' => $first->component_tag, 'serial' => $first->serial, 'display_name' => 'Second component'];
        $this->postJson(route('api.components.store'), $payload)->assertStatus(422);
        $this->postJson(route('api.components.store'), $payload + [
            'allow_duplicate_tag' => true, 'allow_duplicate_serial' => true,
        ])->assertOk()->assertJsonPath('status', 'success');
        $second = ComponentInstance::where('id', '<>', $first->id)->sole();
        $this->assertNotSame($first->qr_uid, $second->qr_uid);
        $this->assertSame($first->component_tag, $second->component_tag);
    }

    public function testAssetApiRequiresSeparateDuplicateConfirmations(): void
    {
        Asset::factory()->create(['asset_tag' => 'SHARED', 'serial' => 'SHARED-SERIAL']);
        $this->actingAsForApi(User::factory()->superuser()->create());
        $payload = ['asset_tag' => 'SHARED', 'serial' => 'SHARED-SERIAL'];
        $this->postJson(route('api.assets.store'), $payload)->assertOk()->assertJsonPath('status', 'error');
        $this->postJson(route('api.assets.store'), $payload + ['allow_duplicate_tag' => true])
            ->assertOk()->assertJsonPath('status', 'error');
        $this->assertSame(1, Asset::count());
        $this->postJson(route('api.assets.store'), $payload + ['allow_duplicate_tag' => true, 'allow_duplicate_serial' => true])
            ->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame(2, Asset::count());
    }

    public function testCheckReportsDuplicatesWithoutLeakingOtherCompanyDetails(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $hidden = Asset::factory()->create(['asset_tag' => 'SECRET-TAG', 'company_id' => Company::factory(), 'name' => 'Hidden customer']);
        $this->actingAs(User::factory()->createAssets()->create(['company_id' => Company::factory()]));
        $this->getJson(route('identifiers.check', ['type' => 'asset', 'field' => 'tag', 'value' => 'secret-tag']))
            ->assertOk()->assertJsonPath('duplicate', true)->assertJsonPath('count', 1)
            ->assertJsonPath('matches', [])->assertDontSee('Hidden customer');
        $this->getJson(route('identifiers.check', ['type' => 'asset', 'field' => 'tag', 'value' => 'SECRET-TAG', 'record_id' => $hidden->id]))
            ->assertNotFound();
    }

    public function testDuplicateScanRequiresSelectionAcrossRecordTypes(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'SHARED']);
        $component = ComponentInstance::factory()->make(['component_tag' => 'SHARED']);
        $component->allowDuplicateTag()->save();
        $this->actingAs(User::factory()->superuser()->create());
        $this->get(route('scan.resolve', ['code' => 'SHARED']))
            ->assertRedirect(route('identifiers.choose', ['field' => 'tag', 'value' => 'SHARED']));
        $this->get(route('identifiers.choose', ['field' => 'tag', 'value' => 'SHARED']))
            ->assertOk()->assertSee(trans('identifiers.choose'));
        $this->get(route('identifiers.choose', ['field' => 'tag', 'value' => 'SHARED', 'selected_type' => 'asset', 'selected_id' => $asset->id]))
            ->assertRedirect(route('hardware.show', $asset));
        $this->get(route('scan.resolve', ['code' => 'CMP:' . $component->qr_uid]))
            ->assertRedirect(route('components.show', $component));
    }

    public function testLabelCacheDoesNotMixComponentsWithIdenticalTags(): void
    {
        $first = ComponentInstance::factory()->create(['component_tag' => 'SHARED']);
        $second = ComponentInstance::factory()->make(['component_tag' => 'SHARED']);
        $second->allowDuplicateTag()->save();
        $labels = app(QrLabelService::class);
        $this->assertNotSame(invade($labels)->pathFor($first, 'png', 'qr-only'), invade($labels)->pathFor($second, 'png', 'qr-only'));
    }

    public function testExistingAssetPageShowsTagAndSerialDuplicateWarnings(): void
    {
        $first = Asset::factory()->create(['asset_tag' => 'SHARED', 'serial' => 'SERIAL-DUP']);
        $second = Asset::factory()->make(['asset_tag' => 'SHARED', 'serial' => 'SERIAL-DUP']);
        $second->allowDuplicateTag()->allowDuplicateSerial()->save();
        $this->actingAs(User::factory()->superuser()->create())->get(route('hardware.show', $first))
            ->assertOk()->assertSee(trans('identifiers.existing'))
            ->assertSee(trans('identifiers.tag'))->assertSee(trans('identifiers.serial'));
    }

    public function testComponentTagEditRequiresAcceptanceAndPreservesQrIdentity(): void
    {
        Asset::factory()->create(['asset_tag' => 'SHARED']);
        $component = ComponentInstance::factory()->create();
        $originalTag = $component->component_tag;
        $qrUid = $component->qr_uid;
        $this->actingAs(User::factory()->superuser()->create());
        $this->postJson(route('components.tag.update', $component), ['component_tag' => 'SHARED'])
            ->assertUnprocessable()->assertJsonValidationErrors('component_tag');
        $this->assertSame($originalTag, $component->fresh()->component_tag);
        $this->postJson(route('components.tag.update', $component), [
            'component_tag' => 'SHARED', 'allow_duplicate_tag' => true,
        ])->assertOk();
        $this->assertSame('SHARED', $component->fresh()->component_tag);
        $this->assertSame($qrUid, $component->fresh()->qr_uid);
        $this->get(route('components.show', $component))->assertOk()->assertSee(trans('identifiers.existing'));
    }

    public function testWebComponentIntakeAcceptsCustomDuplicateIdentifiersOnlyAfterConfirmation(): void
    {
        Asset::factory()->create(['asset_tag' => 'SHARED', 'serial' => 'SHARED-SERIAL']);
        $this->actingAs(User::factory()->superuser()->create());
        $payload = ['component_tag' => 'SHARED', 'serial' => 'SHARED-SERIAL', 'display_name' => 'Intake component',
            'source_type' => ComponentInstance::SOURCE_MANUAL, 'condition_code' => ComponentInstance::CONDITION_GOOD,
            'storage_location_id' => ComponentStorageLocation::factory()->stock()->create()->id];
        $this->post(route('components.store'), $payload)->assertRedirect()->assertSessionHasErrors(['component_tag', 'serial']);
        $this->assertSame(0, ComponentInstance::count());
        $this->post(route('components.store'), $payload + ['allow_duplicate_tag' => 1, 'allow_duplicate_serial' => 1])
            ->assertRedirect();
        $this->assertSame('SHARED', ComponentInstance::sole()->component_tag);
    }

    public function testLiveCheckUsesStoredIdentifierAndAcceptanceDoesNotCarryToAnotherEdit(): void
    {
        $first = Asset::factory()->create(['asset_tag' => 'SHARED']);
        $other = Asset::factory()->make(['asset_tag' => 'SHARED']);
        $other->allowDuplicateTag()->save();
        $third = Asset::factory()->create(['asset_tag' => 'ANOTHER']);
        $this->actingAs(User::factory()->superuser()->create());
        $query = ['type' => 'asset', 'field' => 'tag', 'record_id' => $other->id];
        $this->getJson(route('identifiers.check', $query + ['value' => 'SHARED']))
            ->assertOk()->assertJsonPath('unchanged', true)->assertJsonPath('count', 1);
        $this->getJson(route('identifiers.check', $query + ['value' => 'ANOTHER']))
            ->assertOk()->assertJsonPath('unchanged', false)->assertJsonPath('count', 1);
        $other->preserveAssetTagCase()->asset_tag = 'shared';
        $this->assertTrue($other->save());
        $other->asset_tag = $third->asset_tag;
        $this->assertFalse($other->save());
    }

    public function testRollbackRefusesToDiscardAcceptedComponentDuplicates(): void
    {
        ComponentInstance::factory()->create(['component_tag' => 'SHARED']);
        ComponentInstance::factory()->make(['component_tag' => 'SHARED'])->allowDuplicateTag()->save();
        $migration = require database_path('migrations/2026_09_08_130000_allow_confirmed_duplicate_identifiers.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot restore unique component tags');
        $migration->down();
    }

    public function testCaseAndWhitespaceVariantsOfDeletedIdentifiersRequireAcceptance(): void
    {
        $first = ComponentInstance::factory()->create(['component_tag' => 'Shared', 'serial' => 'Serial']);
        $first->delete();
        $asset = Asset::factory()->make(['asset_tag' => ' SHARED ', 'serial' => ' serial ']);
        $this->assertFalse($asset->save());
        $this->assertTrue($asset->getErrors()->has('asset_tag'));
        $this->assertTrue($asset->getErrors()->has('serial'));
        $this->assertTrue($asset->allowDuplicateTag()->allowDuplicateSerial()->save());
    }

    public function testScanSelectionReplacesExistingDestinationAndRejectsInvalidSelection(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'SHARED']);
        $other = Asset::factory()->make(['asset_tag' => 'SHARED']);
        $other->allowDuplicateTag()->save();
        $this->actingAs(User::factory()->superuser()->create());
        $query = ['field' => 'tag', 'value' => 'SHARED', 'selected_type' => 'asset', 'selected_id' => $asset->id];
        $this->get(route('identifiers.choose', $query + ['return_to' => '/components?destination_asset_id=999&keep=1#install']))
            ->assertRedirect('/components?destination_asset_id=' . $asset->id . '&keep=1#install');
        $this->get(route('identifiers.choose', $query + ['return_to' => 'https://example.org/']))
            ->assertRedirect(route('hardware.show', $asset));
        $query['selected_id'] = 999999;
        $this->get(route('identifiers.choose', $query))->assertNotFound();
    }
}
