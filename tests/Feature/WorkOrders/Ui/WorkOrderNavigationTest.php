<?php

namespace Tests\Feature\WorkOrders\Ui;

use App\Models\User;
use Tests\TestCase;

class WorkOrderNavigationTest extends TestCase
{
    public function testInternalSidebarEntryAppearsOnlyForUsersWhoCanViewWorkOrders(): void
    {
        $authorized = User::factory()->viewWorkOrders()->create();
        $unauthorized = User::factory()->create();

        $this->actingAs($authorized)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('workorders-sidenav-option', false)
            ->assertSee(route('work-orders.index'), false);

        $this->actingAs($unauthorized)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('workorders-sidenav-option', false)
            ->assertDontSee(route('work-orders.index'), false);
    }

    public function testAccountEntriesAppearOnlyForPortalUsers(): void
    {
        $portalUser = User::factory()->viewPortal()->create();
        $nonPortalUser = User::factory()->create();

        $this->actingAs($portalUser)
            ->get(route('view-assets'))
            ->assertOk()
            ->assertSee('My Work Orders')
            ->assertSee(route('account.work-orders.index'), false);

        $this->actingAs($nonPortalUser)
            ->get(route('view-assets'))
            ->assertOk()
            ->assertDontSee('My Work Orders')
            ->assertDontSee(route('account.work-orders.index'), false);
    }

    public function testStartShortcutTemplatesPointManageButtonToWorkOrders(): void
    {
        foreach (['admin', 'superuser', 'supervisor'] as $template) {
            $contents = file_get_contents(resource_path("views/start/{$template}.blade.php"));

            $this->assertIsString($contents);
            $this->assertStringContainsString("route('work-orders.index')", $contents);
            $this->assertStringContainsString("'testid' => 'start-manage'", $contents);
        }
    }
}
