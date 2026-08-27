<?php

namespace Tests\Feature\Assets;

use App\Events\CheckoutableCheckedOut;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CheckoutAcceptance;
use App\Models\CheckoutRequest;
use App\Models\Location;
use App\Models\Statuslabel;
use App\Models\User;
use App\Presenters\PredefinedKitPresenter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegacyAssetFlowContractTest extends TestCase
{
    public function testLegacyAssetWorkflowRoutesAreNotRegistered(): void
    {
        foreach ([
            'api.asset.checkout',
            'api.asset.checkin',
            'api.asset.checkinbytag',
            'api.asset.checkinbytagPath',
            'api.asset.audit',
            'api.asset.audit.legacy',
            'api.assets.list-upcoming',
            'api.assets.requested',
            'api.assets.requests.store',
            'api.assets.requests.destroy',
            'api.assets.requestable',
            'account.requested',
            'requestable-assets',
            'account.request-asset',
            'account.request-asset.cancel',
            'account/request-item',
            'assets.requested',
            'hardware.checkout.create',
            'hardware.checkout.store',
            'hardware.checkin.create',
            'hardware.checkin.store',
            'hardware.bulkcheckout.show',
            'hardware.bulkcheckout.store',
            'asset.audit.create',
            'asset.audit.store',
            'assets.audit.due',
            'assets.bulkaudit',
            'asset.import-history',
            'asset.process-import-history',
            'reports/unaccepted_assets',
            'reports/unaccepted_assets_sent_reminder',
            'reports/unaccepted_assets_delete',
            'reports/export/unaccepted_assets',
            'kits.checkout.show',
            'kits.checkout.store',
        ] as $routeName) {
            $this->assertFalse(Route::has($routeName), "Legacy route [{$routeName}] must remain disabled.");
        }

        $legacyUris = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
            ])
            ->filter(function (array $route): bool {
                $uri = $route['uri'];

                if (preg_match('#^api/v1/hardware/(?:audit|checkinbytag)$#', $uri) === 1) {
                    return true;
                }

                if (preg_match(
                    '#^api/v1/hardware/(?:bytag/\{[^}]+\}/checkin|\{[^}]+\}/(?:checkout|checkin|audit))$#',
                    $uri
                ) === 1) {
                    return true;
                }

                if (preg_match(
                    '#^hardware/(?:history|bulkcheckout|bulkaudit|\{[^}]+\}/(?:checkout|checkin|audit))$#',
                    $uri
                ) === 1) {
                    return true;
                }

                if (preg_match('#^reports/unaccepted_assets(?:/.*)?$#', $uri) === 1) {
                    return true;
                }

                if (preg_match('#^kits/\{[^}]+\}/checkout$#', $uri) === 1) {
                    return true;
                }

                return $uri === 'api/v1/hardware/{action}/{upcoming_status}';
            });

        $this->assertCount(0, $legacyUris, 'No legacy asset-flow URI may be reachable under another name.');
        $this->assertArrayNotHasKey(
            'snipeit:acceptance-reminder',
            Artisan::all(),
            'The Asset acceptance reminder command must remain unregistered.'
        );
    }

    public function testRetiredAssetRequestEndpointsCannotMutateCheckoutRequests(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $checkoutRequest = CheckoutRequest::factory()->forAsset()->create([
            'requestable_id' => $asset->id,
            'requestable_type' => Asset::class,
            'user_id' => $actor->id,
        ]);
        $requestCount = CheckoutRequest::withTrashed()->count();
        $snapshot = CheckoutRequest::withTrashed()
            ->findOrFail($checkoutRequest->id)
            ->getRawOriginal();

        foreach ([
            '/api/v1/account/requests',
            '/api/v1/account/requestable/hardware',
        ] as $endpoint) {
            $response = $this->actingAsForApi($actor)->getJson($endpoint);
            $this->assertContains($response->getStatusCode(), [404, 405], $endpoint);
        }

        foreach ([
            "/api/v1/account/request/{$asset->id}",
            "/api/v1/account/request/{$asset->id}/cancel",
        ] as $endpoint) {
            $response = $this->actingAsForApi($actor)->postJson($endpoint);
            $this->assertContains($response->getStatusCode(), [404, 405], $endpoint);
        }

        foreach ([
            '/account/requested',
            '/account/requestable-assets',
            '/hardware/requested',
            '/test-email',
        ] as $endpoint) {
            $response = $this->actingAs($actor, 'web')->get($endpoint);
            $this->assertContains($response->getStatusCode(), [404, 405], $endpoint);
        }

        foreach ([
            "/account/request-asset/{$asset->id}",
            "/account/request-asset/{$asset->id}/cancel",
            "/account/request/asset/{$asset->id}",
            "/account/request/asset/{$asset->id}/1/{$actor->id}",
        ] as $endpoint) {
            $response = $this->actingAs($actor, 'web')->post($endpoint);
            $this->assertContains($response->getStatusCode(), [404, 405], $endpoint);
        }

        $this->assertSame($requestCount, CheckoutRequest::withTrashed()->count());
        $this->assertEquals(
            $snapshot,
            CheckoutRequest::withTrashed()->findOrFail($checkoutRequest->id)->getRawOriginal(),
        );
    }

    public function testLegacyPermissionsAndDisabledKitCheckoutAreNotExposed(): void
    {
        $configuredPermissions = collect(config('permissions'))
            ->flatMap(fn (array $group) => collect($group)->pluck('permission'))
            ->all();

        foreach ([
            'assets.checkin',
            'assets.checkout',
            'assets.audit',
            'self.checkout_assets',
        ] as $legacyPermission) {
            $this->assertNotContains($legacyPermission, $configuredPermissions);
        }

        $stalePermissionUser = User::factory()->create([
            'permissions' => json_encode([
                'assets.checkin' => '1',
                'assets.checkout' => '1',
                'assets.audit' => '1',
            ]),
        ]);

        $this->assertTrue(Gate::forUser($stalePermissionUser)->denies('checkin', Asset::class));
        $this->assertTrue(Gate::forUser($stalePermissionUser)->denies('checkout', Asset::class));
        $this->assertTrue(Gate::forUser($stalePermissionUser)->denies('audit', Asset::class));

        foreach ([
            User::factory()->admin()->create(),
            User::factory()->superuser()->create(),
        ] as $privilegedUser) {
            $asset = Asset::factory()->create();

            foreach (['checkin', 'checkout', 'audit'] as $ability) {
                $this->assertTrue(
                    Gate::forUser($privilegedUser)->denies($ability, Asset::class),
                    "{$ability} must be denied for privileged users at class scope."
                );
                $this->assertTrue(
                    Gate::forUser($privilegedUser)->denies($ability, $asset),
                    "{$ability} must be denied for privileged users at instance scope."
                );
            }
        }

        $kitColumns = collect(json_decode(
            PredefinedKitPresenter::dataTableLayout(),
            true,
            flags: JSON_THROW_ON_ERROR,
        ))->pluck('field');

        $this->assertNotContains('checkincheckout', $kitColumns);
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Kits/CheckoutKitController.php'));
        $this->assertFileDoesNotExist(app_path('Services/PredefinedKitCheckoutService.php'));
        $this->assertFileDoesNotExist(app_path('Http/Transformers/ComponentsAssetsTransformer.php'));
        $this->assertFileDoesNotExist(resource_path('views/kits/checkout.blade.php'));
        $this->assertStringNotContainsString(
            'hardwareAuditFormatter',
            file_get_contents(resource_path('views/partials/bootstrap-table.blade.php')),
        );
    }

    public function testRetiredAssetLocationSyncCommandCannotMutateAssets(): void
    {
        $assignedUser = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($assignedUser)->create();
        $snapshot = $asset->only([
            'assigned_to',
            'assigned_type',
            'location_id',
            'rtd_location_id',
        ]);

        $this->artisan('snipeit:sync-asset-locations')
            ->expectsOutput(
                'This command is retired because asset checkout/checkin assignments no longer control locations.'
            )
            ->assertExitCode(1);

        $this->assertSame($snapshot, $asset->refresh()->only(array_keys($snapshot)));
    }

    public function testKnownLegacyApiEndpointsCannotMutateAnAsset(): void
    {
        $asset = Asset::factory()->create([
            'last_audit_date' => '2024-01-02 03:04:05',
            'next_audit_date' => '2024-02-03',
            'last_checkin' => '2024-01-01 01:02:03',
            'last_checkout' => '2023-12-01 01:02:03',
        ]);
        $actor = User::factory()->superuser()->create();
        $snapshotFields = [
            'assigned_to',
            'assigned_type',
            'location_id',
            'last_audit_date',
            'next_audit_date',
            'last_checkin',
            'last_checkout',
        ];
        $snapshot = collect($snapshotFields)
            ->mapWithKeys(fn (string $field) => [$field => $asset->getRawOriginal($field)])
            ->all();
        $actionLogCount = $asset->assetlog()->count();

        foreach ([
            "/api/v1/hardware/{$asset->id}/checkout",
            "/api/v1/hardware/{$asset->id}/checkin",
            "/api/v1/hardware/{$asset->id}/audit",
            "/api/v1/hardware/bytag/{$asset->asset_tag}/checkin",
            '/api/v1/hardware/checkinbytag',
            '/api/v1/hardware/audit',
        ] as $endpoint) {
            $response = $this->actingAsForApi($actor)->postJson($endpoint, [
                'assigned_user' => User::factory()->create()->id,
                'update_location' => true,
                'location_id' => Location::factory()->create()->id,
                'note' => 'must not be written',
            ]);

            $this->assertContains($response->getStatusCode(), [404, 405], $endpoint);
        }

        $historyImportResponse = $this->actingAs($actor, 'web')
            ->post('/hardware/history');
        $this->assertContains($historyImportResponse->getStatusCode(), [404, 405]);

        $acceptanceReminderResponse = $this->actingAs($actor, 'web')
            ->post('/reports/unaccepted_assets/sent_reminder');
        $this->assertContains($acceptanceReminderResponse->getStatusCode(), [404, 405]);

        $asset->refresh();

        $current = collect($snapshotFields)
            ->mapWithKeys(fn (string $field) => [$field => $asset->getRawOriginal($field)])
            ->all();

        $this->assertSame($snapshot, $current);
        $this->assertSame($actionLogCount, $asset->assetlog()->count());
    }

    public function testAssetCreationRejectsEveryLegacyAssignmentAliasBeforeMutation(): void
    {
        Event::fake([CheckoutableCheckedOut::class]);

        $model = AssetModel::factory()->create();
        $status = Statuslabel::factory()->readyToDeploy()->create();
        $actor = User::factory()->createAssets()->create();
        $targetUser = User::factory()->create();
        $targetAsset = Asset::factory()->create();
        $targetLocation = Location::factory()->create();

        $payloads = [
            ['assigned_user' => $targetUser->id],
            ['assigned_asset' => $targetAsset->id],
            ['assigned_location' => $targetLocation->id],
            ['assigned_to' => $targetUser->id, 'assigned_type' => User::class],
            ['checkout_to_type' => 'user'],
        ];

        foreach ($payloads as $index => $legacyFields) {
            $assetCount = Asset::count();

            $this->actingAsForApi($actor)
                ->postJson(route('api.assets.store'), array_merge([
                    'asset_tag' => 'NO-ASSIGN-'.$index,
                    'model_id' => $model->id,
                    'status_id' => $status->id,
                ], $legacyFields))
                ->assertUnprocessable()
                ->assertStatusMessageIs('error')
                ->assertJsonPath(
                    'messages',
                    trans('admin/hardware/message.legacy_assignment_disabled')
                );

            $this->assertSame($assetCount, Asset::count());
        }

        Event::assertNotDispatched(CheckoutableCheckedOut::class);
    }

    public function testSyntheticAssetCheckoutEventIsInertAndAssetNotificationSourcesAreAbsent(): void
    {
        Mail::fake();
        Notification::fake();

        $this->settings
            ->enableAdminCC('cc@example.com')
            ->enableAdminCCAlways()
            ->enableSlackWebhook();

        $category = Category::factory()->create([
            'checkin_email' => true,
            'require_acceptance' => true,
            'use_default_eula' => false,
        ]);
        $asset = Asset::factory()
            ->for(AssetModel::factory()->for($category), 'model')
            ->create();
        $target = User::factory()->create();
        $actor = User::factory()->superuser()->create();

        event(new CheckoutableCheckedOut($asset, $target, $actor, 'must remain inert'));

        $this->assertFalse(
            CheckoutAcceptance::query()
                ->where('checkoutable_type', Asset::class)
                ->where('checkoutable_id', $asset->id)
                ->exists()
        );
        $this->assertFalse(
            Actionlog::query()
                ->where('item_type', Asset::class)
                ->where('item_id', $asset->id)
                ->where('action_type', 'checkout')
                ->exists()
        );
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        $this->assertFileDoesNotExist(app_path('Mail/CheckoutAssetMail.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/CheckoutAssetNotification.php'));
        $this->assertFileDoesNotExist(resource_path('views/mail/markdown/checkout-asset.blade.php'));
        $this->assertFalse(view()->exists('mail.markdown.checkout-asset'));
    }

    public function testWebAssetCreationRejectsLegacyAssignmentBeforeMutation(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $target = User::factory()->create();
        $assetCount = Asset::count();

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('hardware.create'))
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => 'NO-WEB-ASSIGN'],
                'assigned_user' => $target->id,
                'model_id' => $model->id,
                'model_number_id' => $modelNumber->id,
                'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
            ])
            ->assertRedirect(route('hardware.create'))
            ->assertSessionHas(
                'error',
                trans('admin/hardware/message.legacy_assignment_disabled')
            );

        $this->assertSame($assetCount, Asset::count());
    }

    public function testApiAndAssetPageDoNotAdvertiseLegacyMutations(): void
    {
        $asset = Asset::factory()->create([
            'last_audit_date' => '2024-01-02 03:04:05',
            'next_audit_date' => '2024-02-03',
            'last_checkin' => '2024-01-01 01:02:03',
            'last_checkout' => '2023-12-01 01:02:03',
            'checkin_counter' => 2,
            'checkout_counter' => 3,
        ]);
        $actor = User::factory()->superuser()->create();

        $apiResponse = $this->actingAsForApi($actor)
            ->getJson(route('api.assets.show', $asset))
            ->assertOk()
            ->assertJsonPath('user_can_checkout', false)
            ->assertJsonPath('available_actions.checkout', false)
            ->assertJsonPath('available_actions.checkin', false)
            ->assertJsonPath('available_actions.audit', false)
            ->assertJsonPath('checkin_counter', 2)
            ->assertJsonPath('checkout_counter', 3);

        $this->assertNotNull($apiResponse->json('last_audit_date'));
        $this->assertNotNull($apiResponse->json('last_checkin'));
        $this->assertNotNull($apiResponse->json('last_checkout'));

        $this->actingAs($actor, 'web')
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertDontSee("/hardware/{$asset->id}/checkout", false)
            ->assertDontSee("/hardware/{$asset->id}/checkin", false)
            ->assertDontSee("/hardware/{$asset->id}/audit", false)
            ->assertDontSee('/hardware/history', false)
            ->assertDontSee('/reports/unaccepted_assets', false);

        $auditReport = Route::getRoutes()->getByName('reports.audit');
        $this->assertNotNull($auditReport);
        $this->assertSame(['GET', 'HEAD'], $auditReport->methods());
    }
}
