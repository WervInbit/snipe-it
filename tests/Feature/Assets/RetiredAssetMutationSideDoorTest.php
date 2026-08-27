<?php

namespace Tests\Feature\Assets;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use LogicException;
use Tests\TestCase;

class RetiredAssetMutationSideDoorTest extends TestCase
{
    public function test_browser_update_rejects_legacy_assignment_aliases_without_partial_location_change(): void
    {
        $originalLocation = Location::factory()->create();
        $requestedLocation = Location::factory()->create();
        $assignee = User::factory()->create();
        $asset = Asset::factory()->create([
            'rtd_location_id' => $originalLocation->id,
            'location_id' => $originalLocation->id,
        ]);

        $this->actingAs(User::factory()->editAssets()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'assigned_user' => $assignee->id,
                'rtd_location_id' => $requestedLocation->id,
            ])
            ->assertRedirect(route('hardware.edit', $asset))
            ->assertSessionHas(
                'error',
                trans('admin/hardware/message.legacy_assignment_disabled')
            );

        $asset->refresh();

        $this->assertSame($originalLocation->id, $asset->rtd_location_id);
        $this->assertSame($originalLocation->id, $asset->location_id);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
    }

    public function test_asset_loggable_checkout_checkin_and_audit_entry_points_fail_closed(): void
    {
        $asset = Asset::factory()->create([
            'checkin_counter' => 0,
            'checkout_counter' => 0,
        ]);
        $target = User::factory()->create();
        $before = Actionlog::query()
            ->where('item_type', Asset::class)
            ->where('item_id', $asset->id)
            ->count();

        foreach ([
            fn () => $asset->logCheckout('retired', $target),
            fn () => $asset->logCheckin($target, 'retired'),
            fn () => $asset->logAudit('retired', $asset->location_id),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Retired asset history mutation must fail closed.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('read-only', $exception->getMessage());
            }
        }

        $asset->refresh();

        $this->assertSame(0, (int) $asset->checkin_counter);
        $this->assertSame(0, (int) $asset->checkout_counter);
        $this->assertSame(
            $before,
            Actionlog::query()
                ->where('item_type', Asset::class)
                ->where('item_id', $asset->id)
                ->count()
        );
    }
}
