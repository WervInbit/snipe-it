<?php

namespace Tests\Feature\StatusLabels;

use App\Helpers\Helper;
use App\Models\Asset;
use App\Models\Group;
use App\Models\Statuslabel;
use App\Models\StatusLabelAccessRule;
use App\Models\User;
use App\Services\Assets\StatusLabelAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusLabelOperationalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_access_remains_open_until_rules_are_configured(): void
    {
        $status = Statuslabel::factory()->pending()->create(['default_label' => 0]);
        $user = User::factory()->create();
        $service = app(StatusLabelAccessService::class);

        $this->assertTrue($service->canView($user, $status));
        $this->assertTrue($service->canSelect($user, $status));
    }

    public function test_group_allow_is_additive_and_direct_user_deny_wins(): void
    {
        $status = Statuslabel::factory()->pending()->create(['default_label' => 0]);
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $user->groups()->attach($group);

        StatusLabelAccessRule::create([
            'status_label_id' => $status->id,
            'subject_type' => StatusLabelAccessRule::SUBJECT_GROUP,
            'subject_id' => $group->id,
            'view_value' => StatusLabelAccessRule::ALLOW,
            'select_value' => StatusLabelAccessRule::ALLOW,
        ]);

        $service = app(StatusLabelAccessService::class);
        $this->assertTrue($service->canView($user->fresh(), $status));
        $this->assertTrue($service->canSelect($user->fresh(), $status));

        StatusLabelAccessRule::create([
            'status_label_id' => $status->id,
            'subject_type' => StatusLabelAccessRule::SUBJECT_USER,
            'subject_id' => $user->id,
            'view_value' => StatusLabelAccessRule::DENY,
            'select_value' => StatusLabelAccessRule::DENY,
        ]);

        $this->assertFalse($service->canView($user->fresh(), $status));
        $this->assertFalse($service->canSelect($user->fresh(), $status));
    }

    public function test_operational_status_dropdown_filters_disallowed_choices(): void
    {
        $allowed = Statuslabel::factory()->pending()->create(['name' => 'Operator choice', 'default_label' => 0]);
        $blocked = Statuslabel::factory()->pending()->create(['name' => 'Admin choice', 'default_label' => 0]);
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $user->groups()->attach($group);

        foreach ([$allowed, $blocked] as $status) {
            StatusLabelAccessRule::create([
                'status_label_id' => $status->id,
                'subject_type' => StatusLabelAccessRule::SUBJECT_GROUP,
                'subject_id' => $group->id,
                'view_value' => $status->is($allowed) ? StatusLabelAccessRule::ALLOW : StatusLabelAccessRule::DENY,
                'select_value' => $status->is($allowed) ? StatusLabelAccessRule::ALLOW : StatusLabelAccessRule::DENY,
            ]);
        }

        $this->actingAs($user);
        $choices = Helper::statusLabelList();

        $this->assertArrayHasKey($allowed->id, $choices);
        $this->assertArrayNotHasKey($blocked->id, $choices);
    }

    public function test_status_configured_to_require_note_rejects_empty_transition_note(): void
    {
        $current = Statuslabel::factory()->pending()->create(['default_label' => 0]);
        $target = Statuslabel::factory()->pending()->create([
            'default_label' => 0,
            'requires_note' => true,
        ]);
        $asset = Asset::factory()->create(['status_id' => $current->id]);
        $user = User::factory()->superuser()->create();

        $this->assertTrue((bool) $target->fresh()->requires_note);
        $this->assertNotSame($target->id, $asset->fresh()->status_id);

        $response = $this->actingAs($user)
            ->patchJson(route('hardware.status.update', $asset), ['status_id' => $target->id]);
        $response->assertOk()
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['messages' => ['status_change_note']]);

        $this->patchJson(route('hardware.status.update', $asset), [
            'status_id' => $target->id,
            'status_change_note' => 'QA exception recorded.',
        ])->assertOk();

        $this->assertSame($target->id, $asset->fresh()->status_id);
    }
}
