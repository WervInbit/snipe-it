<?php

namespace Tests\Feature\StatusLabels;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class StatusLabelSemanticMutationTest extends TestCase
{
    public function test_web_update_cannot_retag_an_in_use_label_across_company_scope(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $editorCompany = Company::factory()->create();
        $assetCompany = Company::factory()->create();
        $statuslabel = Statuslabel::factory()->pending()->create([
            'lifecycle_stage' => null,
        ]);
        Asset::factory()->create([
            'company_id' => $assetCompany->id,
            'status_id' => $statuslabel->id,
        ]);
        $editor = $this->statusLabelEditor([
            'company_id' => $editorCompany->id,
        ]);

        $this->actingAs($editor)
            ->put(route('statuslabels.update', $statuslabel), [
                'name' => $statuslabel->name,
                'statuslabel_types' => 'archived',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('lifecycle_stage');

        $statuslabel->refresh();
        $this->assertSame('pending', $statuslabel->getStatuslabelType());
        $this->assertNull($statuslabel->lifecycle_stage);
    }

    public function test_api_update_cannot_retag_an_in_use_label(): void
    {
        $statuslabel = Statuslabel::factory()->pending()->create([
            'lifecycle_stage' => null,
        ]);
        Asset::factory()->create([
            'status_id' => $statuslabel->id,
        ]);

        $this->actingAsForApi($this->statusLabelEditor())
            ->patchJson(route('api.statuslabels.update', $statuslabel), [
                'name' => $statuslabel->name,
                'type' => 'deployable',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            ])
            ->assertStatusMessageIs('error')
            ->assertUnprocessable()
            ->assertJsonPath(
                'messages.lifecycle_stage.0',
                trans('admin/statuslabels/message.semantic_fields_in_use')
            );

        $statuslabel->refresh();
        $this->assertSame('pending', $statuslabel->getStatuslabelType());
        $this->assertNull($statuslabel->lifecycle_stage);
    }

    public function test_in_use_label_can_still_be_renamed_without_changing_semantics(): void
    {
        $statuslabel = Statuslabel::factory()->pending()->create([
            'lifecycle_stage' => null,
        ]);
        Asset::factory()->create([
            'status_id' => $statuslabel->id,
        ]);

        $this->actingAs($this->statusLabelEditor())
            ->put(route('statuslabels.update', $statuslabel), [
                'name' => 'Operator-owned display name',
                'statuslabel_types' => 'pending',
                'lifecycle_stage' => null,
            ])
            ->assertRedirect(route('statuslabels.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Operator-owned display name', $statuslabel->fresh()->name);
    }

    private function statusLabelEditor(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'permissions' => json_encode([
                'statuslabels.edit' => '1',
            ]),
        ], $attributes));
    }
}
