<?php

namespace Tests\Feature\Users\Ui;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PrintUserInventoryTest extends TestCase
{
    public function testPermissionRequiredToPrintUserInventory()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('users.print', User::factory()->create()))
            ->assertStatus(403);
    }

    public function testCanPrintUserInventory()
    {
        $actor = User::factory()->viewUsers()->create();

        $this->actingAs($actor)
            ->get(route('users.print', User::factory()->create()))
            ->assertOk()
            ->assertStatus(200);
    }

    public function testCannotPrintUserInventoryFromAnotherCompany()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $actor = User::factory()->for($companyA)->viewUsers()->create();
        $user = User::factory()->for($companyB)->create();

        $this->actingAs($actor)
            ->get(route('users.print', $user))
            ->assertStatus(302);
    }

    public function testSingleUserPrintPayloadIsFilteredPerInventoryPermission(): void
    {
        $subject = User::factory()->create();
        $tokens = $this->seedPrintableInventory($subject);

        foreach ($this->inventoryPermissionCases() as $relation => $factoryState) {
            $actor = User::factory()->viewUsers()->{$factoryState}()->create();
            $response = $this->actingAs($actor)
                ->get(route('users.print', $subject))
                ->assertOk();

            $this->assertPrintResponseShowsOnly($response, $tokens, $relation);
        }
    }

    public function testBulkUserPrintPayloadIsFilteredPerInventoryPermission(): void
    {
        $subject = User::factory()->create();
        $tokens = $this->seedPrintableInventory($subject);

        foreach ($this->inventoryPermissionCases() as $relation => $factoryState) {
            $actor = User::factory()->viewUsers()->{$factoryState}()->create();
            $response = $this->actingAs($actor)
                ->post(route('users/bulkedit'), [
                    'ids' => [$subject->id],
                    'bulk_actions' => 'print',
                ])
                ->assertOk();

            $this->assertPrintResponseShowsOnly($response, $tokens, $relation);
        }
    }

    /**
     * @return array<string, string>
     */
    private function seedPrintableInventory(User $subject): array
    {
        Asset::factory()->assignedToUser($subject)->create([
            'name' => 'PRINT-RESOURCE-ASSET',
            'asset_tag' => 'PRINT-ASSET-TAG',
        ]);

        $license = License::factory()->create(['name' => 'PRINT-RESOURCE-LICENSE']);
        LicenseSeat::factory()->for($license)->assignedToUser($subject)->create();

        $accessory = Accessory::factory()->create(['name' => 'PRINT-RESOURCE-ACCESSORY']);
        $accessory->checkouts()->create([
            'assigned_to' => $subject->id,
            'assigned_type' => User::class,
        ]);

        $consumable = Consumable::factory()->create(['name' => 'PRINT-RESOURCE-CONSUMABLE']);
        $subject->consumables()->attach($consumable->id, ['created_by' => $subject->id]);

        return [
            'assets' => 'PRINT-ASSET-TAG',
            'licenses' => 'PRINT-RESOURCE-LICENSE',
            'accessories' => 'PRINT-RESOURCE-ACCESSORY',
            'consumables' => 'PRINT-RESOURCE-CONSUMABLE',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function inventoryPermissionCases(): array
    {
        return [
            'assets' => 'viewAssets',
            'licenses' => 'viewLicenses',
            'accessories' => 'viewAccessories',
            'consumables' => 'viewConsumables',
        ];
    }

    /**
     * @param array<string, string> $tokens
     */
    private function assertPrintResponseShowsOnly(
        TestResponse $response,
        array $tokens,
        string $visibleRelation
    ): void {
        foreach ($tokens as $relation => $token) {
            if ($relation === $visibleRelation) {
                $response->assertSee($token);
            } else {
                $response->assertDontSee($token);
            }
        }

        $response->assertViewHas('users', function ($users) use ($tokens, $visibleRelation): bool {
            $printUser = collect($users)->first();

            foreach (array_keys($tokens) as $relation) {
                $count = $printUser->{$relation}->count();
                if (($relation === $visibleRelation && $count === 0)
                    || ($relation !== $visibleRelation && $count !== 0)
                ) {
                    return false;
                }
            }

            return true;
        });
    }
}
