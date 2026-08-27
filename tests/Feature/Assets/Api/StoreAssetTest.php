<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\Location;
use App\Models\Statuslabel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class StoreAssetTest extends TestCase
{
    public function testRequiresPermissionToCreateAsset()
    {
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.assets.store'))
            ->assertForbidden();
    }

    public function testAllAssetAttributesAreStored()
    {
        $company = Company::factory()->create();
        $model = AssetModel::factory()->create();
        $rtdLocation = Location::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $supplier = Supplier::factory()->create();
        $user = User::factory()->createAssets()->create();

        $response = $this->actingAsForApi($user)
            ->postJson(route('api.assets.store'), [
                'asset_eol_date' => '2024-06-02',
                'asset_tag' => 'random_string',
                'company_id' => $company->id,
                'model_id' => $model->id,
                'name' => 'A New Asset',
                'notes' => 'Some notes',
                'order_number' => '5678',
                'purchase_cost' => '123.45',
                'purchase_date' => '2023-09-02',
                'is_sellable' => false,
                'rtd_location_id' => $rtdLocation->id,
                'serial' => '1234567890',
                'status_id' => $status->id,
                'supplier_id' => $supplier->id,
                'warranty_months' => 10,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);

        $this->assertTrue($asset->adminuser->is($user));

        $this->assertEquals('2024-06-02', $asset->asset_eol_date);
        $this->assertEquals('RANDOM_STRING', $asset->asset_tag);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertTrue($asset->company->is($company));
        $this->assertNull($asset->last_audit_date);
        $this->assertTrue($asset->location->is($rtdLocation));
        $this->assertTrue($asset->model->is($model));
        $this->assertEquals('A New Asset', $asset->name);
        $this->assertEquals('Some notes', $asset->notes);
        $this->assertEquals('5678', $asset->order_number);
        $this->assertEquals('123.45', $asset->purchase_cost);
        $this->assertTrue($asset->purchase_date->is('2023-09-02'));
        $this->assertSame(0, (int) $asset->requestable);
        $this->assertTrue($asset->defaultLoc->is($rtdLocation));
        $this->assertEquals('1234567890', $asset->serial);
        $this->assertTrue($asset->assetstatus->is($status));
        $this->assertTrue($asset->supplier->is($supplier));
        $this->assertEquals(10, $asset->warranty_months);
        $this->assertFalse($asset->is_sellable);

        $this->assertHasTheseActionLogs($asset, ['create']);
    }

    public function testLegacyReadOnlyFieldsAreRejectedBeforeAssetCreation(): void
    {
        $assetCount = Asset::count();
        $legacyValues = [
            'requestable' => 1,
            'last_checkin' => '2024-01-02 03:04:05',
            'last_checkout' => '2024-01-03 04:05:06',
            'expected_checkin' => '2024-02-01',
            'last_audit_date' => '2024-01-04 05:06:07',
            'next_audit_date' => '2024-03-01',
        ];

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                ...$legacyValues,
                'asset_tag' => 'LEGACY-METADATA-MUST-NOT-STORE',
                'model_id' => AssetModel::factory()->create()->id,
                'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');

        foreach (Asset::LEGACY_READ_ONLY_FIELDS as $field) {
            $this->assertNotNull($response->json("messages.{$field}"));
        }

        $this->assertSame($assetCount, Asset::count());
    }

    public function testCannotCreateAssetWithDeprecatedModelNumber(): void
    {
        $model = AssetModel::factory()->create();
        $model->modelNumbers()->create([
            'code' => 'ACTIVE',
            'label' => 'Active Preset',
        ]);
        $deprecated = $model->modelNumbers()->create([
            'code' => 'OLD',
            'label' => 'Deprecated Preset',
        ]);
        $deprecated->deprecate();

        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->actingAsForApi(User::factory()->createAssets()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => 'TAG-1001',
                'model_id' => $model->id,
                'model_number_id' => $deprecated->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('model_number_id');
    }

    public function testLastAuditDateCanBeNull()
    {
        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                // 'last_audit_date' => '2023-09-03 12:23:45',
                'asset_tag' => '1234',
                'model_id' => AssetModel::factory()->create()->id,
                'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $asset = Asset::find($response['payload']['id']);
        $this->assertNull($asset->last_audit_date);
    }

    public function testSaveWithPendingStatusWithoutUserIsSuccessful()
    {
        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => '1234',
                'model_id' => AssetModel::factory()->create()->id,
                'status_id' => Statuslabel::factory()->pending()->create()->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');
    }

    public function testModelNumberIsRequiredWhenModelHasAnActivePreset()
    {
        $model = AssetModel::factory()->create();
        $model->ensurePrimaryModelNumber();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $response = $this->actingAsForApi(User::factory()->createAssets()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => '12345',
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->assertNotNull($response->json('messages.model_number_id'));
    }


    public function testArchivedDepreciateAndPhysicalCanBeNull()
    {
        $model = AssetModel::factory()->ipadModel()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->enableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'archive' => null,
                'depreciate' => null,
                'physical' => null
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);
        $this->assertEquals(0, $asset->archived);
        $this->assertEquals(1, $asset->physical);
        $this->assertEquals(0, $asset->depreciate);
    }

    public function testArchivedDepreciateAndPhysicalCanBeEmpty()
    {
        $model = AssetModel::factory()->ipadModel()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->enableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'archive' => '',
                'depreciate' => '',
                'physical' => ''
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);
        $this->assertEquals(0, $asset->archived);
        $this->assertEquals(1, $asset->physical);
        $this->assertEquals(0, $asset->depreciate);
    }

    public function testAssetEolDateIsCalculatedIfPurchaseDateSet()
    {
        $model = AssetModel::factory()->mbp13Model()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->enableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'purchase_date' => '2021-01-01',
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);
        $this->assertEquals('2024-01-01', $asset->asset_eol_date);
    }

    public function testAssetEolDateIsNotCalculatedIfPurchaseDateNotSet()
    {
        $model = AssetModel::factory()->mbp13Model()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->enableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);
        $this->assertNull($asset->asset_eol_date);
    }

    public function testAssetEolExplicitIsSetIfAssetEolDateIsExplicitlySet()
    {
        $model = AssetModel::factory()->mbp13Model()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->enableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'asset_eol_date' => '2025-01-01',
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);
        $this->assertEquals('2025-01-01', $asset->asset_eol_date);
        $this->assertTrue($asset->eol_explicit);
    }

    public function testAssetGetsAssetTagWithAutoIncrement()
    {
        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->enableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::find($response['payload']['id']);
        $this->assertNotNull($asset->asset_tag);
    }

    public function testAssetGetsGeneratedTagWhenAutoIncrementIsDisabled()
    {
        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();

        $this->settings->disableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        $asset = Asset::findOrFail($response['payload']['id']);
        $this->assertMatchesRegularExpression('/^INBIT-[A-Z]{2}\d{4}$/', $asset->asset_tag);
    }

    public function testStoresPeriodAsDecimalSeparatorForPurchaseCost()
    {
        $this->settings->set([
            'default_currency' => 'USD',
            'digit_separator' => '1,234.56',
        ]);

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => 'random-string',
                'model_id' => AssetModel::factory()->create()->id,
                'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
                // API accepts float
                'purchase_cost' => 12.34,
            ])
            ->assertStatusMessageIs('success');

        $asset = Asset::find($response['payload']['id']);

        $this->assertEquals(12.34, $asset->purchase_cost);
    }

    public function testStoresPeriodAsCommaSeparatorForPurchaseCost()
    {
        $this->settings->set([
            'default_currency' => 'EUR',
            'digit_separator' => '1.234,56',
        ]);

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => 'random-string',
                'model_id' => AssetModel::factory()->create()->id,
                'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
                // API also accepts string for comma separated values
                'purchase_cost' => '12,34',
            ])
            ->assertStatusMessageIs('success');

        $asset = Asset::find($response['payload']['id']);

        $this->assertEquals(12.34, $asset->purchase_cost);
    }

    public function testUniqueSerialNumbersIsEnforcedWhenEnabled()
    {
        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $serial = '1234567890';

        $this->settings->enableAutoIncrement();
        $this->settings->enableUniqueSerialNumbers();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'serial' => $serial,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'serial' => $serial,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');
    }

    public function testUniqueSerialNumbersIsNotEnforcedWhenDisabled()
    {
        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $serial = '1234567890';

        $this->settings->enableAutoIncrement();
        $this->settings->disableUniqueSerialNumbers();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'serial' => $serial,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'serial' => $serial,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');
    }

    public function testAssetTagsMustBeUniqueWhenUndeleted()
    {
        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $asset_tag = '1234567890';

        $this->settings->disableAutoIncrement();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => $asset_tag,
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => $asset_tag,
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');
    }

    public function testAssetTagsCanBeDuplicatedIfDeleted()
    {
        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $asset_tag = '1234567890';

        $this->settings->disableAutoIncrement();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => $asset_tag,
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->json();

        Asset::find($response['payload']['id'])->delete();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => $asset_tag,
                'model_id' => $model->id,
                'status_id' => $status->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');
    }

    /**
     * @link https://app.shortcut.com/grokability/story/24475
     */
    public function testCompanyIdNeedsToBeInteger()
    {
        $this->actingAsForApi(User::factory()->createAssets()->create())
            ->postJson(route('api.assets.store'), [
                'company_id' => [1],
            ])
            ->assertStatusMessageIs('error')
            ->assertJson(function (AssertableJson $json) {
                $json->has('messages.company_id')->etc();
            });
    }

    public function testSerialValidation()
    {
        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.store'), [
                'asset_tag' => '1234',
                'model_id' => AssetModel::factory()->create()->id,
                'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
                'serial' => [
                    // this should not be an array
                ],
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('serial');
    }

    public function testEncryptedCustomFieldCanBeStored()
    {
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $field = CustomField::factory()->testEncrypted()->create();
        $superuser = User::factory()->superuser()->create();
        $assetData = Asset::factory()->hasEncryptedCustomField($field)->make();

        $response = $this->actingAsForApi($superuser)
            ->postJson(route('api.assets.store'), [
                $field->db_column_name() => 'This is encrypted field',
                'model_id' => $assetData->model->id,
                'model_number_id' => $assetData->model_number_id,
                'status_id' => $status->id,
                'asset_tag' => '1234',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk()
            ->json();

        $asset = Asset::findOrFail($response['payload']['id']);
        $this->assertEquals('This is encrypted field', Crypt::decrypt($asset->{$field->db_column_name()}));
    }

    public function testEncryptedCustomFieldValidationPasses()
    {
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $alphaField = CustomField::factory()->encrypt()->alpha()->create();
        $numericField = CustomField::factory()->encrypt()->numeric()->create();
        $emailField = CustomField::factory()->encrypt()->email()->create();
        $fields = [$alphaField, $numericField, $emailField];
        $superuser = User::factory()->superuser()->create();
        $assetData = Asset::factory()->hasMultipleCustomFields($fields)->make();

        $response = $this->actingAsForApi($superuser)
            ->postJson(route('api.assets.store'), [
                $alphaField->db_column_name()   => 'Thisisencryptedfield',
                $numericField->db_column_name() => '1234567890',
                $emailField->db_column_name()   => 'poop@poop.com',
                'model_id'                      => $assetData->model->id,
                'model_number_id'               => $assetData->model_number_id,
                'status_id'                     => $status->id,
                'asset_tag'                     => '1234',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk()
            ->json();

        $asset = Asset::findOrFail($response['payload']['id']);
        $this->assertEquals('Thisisencryptedfield', Crypt::decrypt($asset->{$alphaField->db_column_name()}));
        $this->assertEquals('1234567890', Crypt::decrypt($asset->{$numericField->db_column_name()}));
        $this->assertEquals('poop@poop.com', Crypt::decrypt($asset->{$emailField->db_column_name()}));
    }

    public function testEncryptedCustomFieldValidationFails()
    {
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $alphaField = CustomField::factory()->encrypt()->alpha()->create();
        $numericField = CustomField::factory()->encrypt()->numeric()->create();
        $emailField = CustomField::factory()->encrypt()->email()->create();
        $fields = [$alphaField, $numericField, $emailField];
        $superuser = User::factory()->superuser()->create();
        $assetData = Asset::factory()->hasMultipleCustomFields($fields)->make();
        $cleaned_name = trim(preg_replace('/_+|snipeit|\d+/', ' ', $alphaField->db_column_name()));

        $response = $this->actingAsForApi($superuser)
            ->postJson(route('api.assets.store'), [
                $alphaField->db_column_name() => 'Thisisencryptedfield123',
                'model_id'                    => $assetData->model->id,
                'model_number_id'             => $assetData->model_number_id,
                'status_id'                   => $status->id,
                'asset_tag'                   => '1234',
            ])
            ->assertStatusMessageIs('error')
            ->assertJsonPath(
                'messages.' . $alphaField->db_column_name(),
                [trans('validation.alpha', ['attribute' => $cleaned_name])]
            )
            ->assertOk()
            ->json();
    }


    public function testPermissionNeededToStoreEncryptedField()
    {
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $field = CustomField::factory()->testEncrypted()->create();
        $normal_user = User::factory()->createAssets()->create();
        $assetData = Asset::factory()->hasEncryptedCustomField($field)->make();

        $response = $this->actingAsForApi($normal_user)
            ->postJson(route('api.assets.store'), [
                $field->db_column_name() => 'Some Other Value Entirely!',
                'model_id' => $assetData->model->id,
                'model_number_id' => $assetData->model_number_id,
                'status_id' => $status->id,
                'asset_tag' => '1234',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk()
            ->assertMessagesAre(trans('admin/hardware/message.create.encrypted_warning'))
            ->json();

        $asset = Asset::findOrFail($response['payload']['id']);
        $this->assertNull($asset->{$field->db_column_name()});
    }

    public function testEncryptedDefaultIsStoredEncryptedWhenCreatorCannotViewEncryptedFields()
    {
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $field = CustomField::factory()->testEncrypted()->create();
        $model = AssetModel::factory()->hasEncryptedCustomField($field)->create();
        $model->defaultValues()->attach($field, ['default_value' => 'Protected default']);

        $response = $this->actingAsForApi(User::factory()->createAssets()->create())
            ->postJson(route('api.assets.store'), [
                'model_id' => $model->id,
                'status_id' => $status->id,
                'asset_tag' => 'encrypted-default',
            ])
            ->assertStatusMessageIs('success')
            ->assertMessagesAre(trans('admin/hardware/message.create.success'))
            ->assertOk()
            ->json();

        $asset = Asset::findOrFail($response['payload']['id']);
        $this->assertSame(
            'Protected default',
            Crypt::decrypt($asset->{$field->db_column_name()})
        );
    }
}
