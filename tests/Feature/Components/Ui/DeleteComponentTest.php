<?php

namespace Tests\Feature\Components\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Company;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\User;
use Tests\Concerns\TestsFullMultipleCompaniesSupport;
use Tests\Concerns\TestsPermissionsRequirement;
use Tests\TestCase;

class DeleteComponentTest extends TestCase implements TestsFullMultipleCompaniesSupport, TestsPermissionsRequirement
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testRequiresPermission(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('components.destroy', $component->id))
            ->assertForbidden();
    }

    public function testHandlesNonExistentComponent(): void
    {
        $this->actingAs(User::factory()->deleteComponents()->create())
            ->delete(route('components.destroy', 10000))
            ->assertRedirect(route('components.index'));
    }

    public function testCanDeleteComponent(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAs(User::factory()->deleteComponents()->create())
            ->delete(route('components.destroy', $component->id))
            ->assertSessionHas('success')
            ->assertRedirect(route('components.index'));

        $this->assertSoftDeleted($component);
    }

    public function testCannotDeleteComponentIfInstalled(): void
    {
        $component = ComponentInstance::factory()->installed(Asset::factory()->create()->id)->create();

        $this->actingAs(User::factory()->deleteComponents()->create())
            ->delete(route('components.destroy', $component->id))
            ->assertSessionHas('error')
            ->assertRedirect(route('components.show', $component));
    }

    public function testDeletingComponentIsLogged(): void
    {
        $user = User::factory()->deleteComponents()->create();
        $component = ComponentInstance::factory()->create();

        $this->actingAs($user)->delete(route('components.destroy', $component->id));

        $this->assertDatabaseHas('action_logs', [
            'created_by' => $user->id,
            'action_type' => 'delete',
            'item_type' => ComponentInstance::class,
            'item_id' => $component->id,
        ]);
    }

    public function testAdheresToFullMultipleCompaniesSupportScoping(): void
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $userInCompanyA = User::factory()->for($companyA)->deleteComponents()->create();
        $componentForCompanyB = ComponentInstance::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userInCompanyA)
            ->delete(route('components.destroy', $componentForCompanyB->id))
            ->assertRedirect(route('components.index'));

        $this->assertNotSoftDeleted($componentForCompanyB);
    }
}
