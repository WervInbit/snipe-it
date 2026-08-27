<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CheckoutConcurrencyGuardTest extends TestCase
{
    public function test_supported_quantity_checkout_surfaces_recheck_inventory_under_a_row_lock(): void
    {
        $basePath = realpath(__DIR__.'/../..');

        foreach ([
            'app/Http/Controllers/Accessories/AccessoryCheckoutController.php',
            'app/Http/Controllers/Api/AccessoriesController.php',
            'app/Http/Controllers/Consumables/ConsumableCheckoutController.php',
            'app/Http/Controllers/Api/ConsumablesController.php',
        ] as $relativePath) {
            $source = file_get_contents($basePath.'/'.$relativePath);

            $this->assertStringContainsString('DB::transaction(', $source, $relativePath);
            $this->assertStringContainsString('->lockForUpdate()', $source, $relativePath);
            $this->assertStringContainsString('->numRemaining()', $source, $relativePath);
        }
    }

    public function test_license_checkout_locks_the_selected_seat(): void
    {
        $basePath = realpath(__DIR__.'/../..');
        $controller = file_get_contents($basePath.'/app/Http/Controllers/Licenses/LicenseCheckoutController.php');
        $apiController = file_get_contents($basePath.'/app/Http/Controllers/Api/LicenseSeatsController.php');
        $model = file_get_contents($basePath.'/app/Models/License.php');

        $this->assertStringContainsString('findLicenseSeatToCheckout($license, $seatId, lock: true)', $controller);
        $this->assertStringContainsString('->lockForUpdate()', $controller);
        $this->assertStringContainsString('->lockForUpdate()', $apiController);
        $this->assertStringContainsString('public function freeSeat(bool $lock = false)', $model);
    }

    public function test_retired_legacy_component_checkout_is_not_reintroduced(): void
    {
        $basePath = realpath(__DIR__.'/../..');

        $this->assertFileDoesNotExist(
            $basePath.'/app/Http/Controllers/Components/ComponentCheckoutController.php'
        );
        $this->assertStringNotContainsString(
            'components.checkout',
            file_get_contents($basePath.'/routes/web/components.php')
        );
    }
}
