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

    public function test_supervisor_can_view_and_select_afgevoerd_when_refurbisher_is_denied(): void
    {
        $afgevoerd = Statuslabel::factory()->create([
            'name' => 'Afgevoerd',
            'archived' => 1,
            'deployable' => 0,
            'pending' => 0,
            'lifecycle_stage' => Statuslabel::LIFECYCLE_DESTROYED,
            'default_label' => 0,
        ]);
        $refurbisherGroup = Group::factory()->create(['name' => 'Refurbisher']);
        $supervisorGroup = Group::factory()->create(['name' => 'Supervisor']);
        $refurbisher = User::factory()->create();
        $supervisor = User::factory()->create();
        $refurbisher->groups()->attach($refurbisherGroup);
        $supervisor->groups()->attach($supervisorGroup);

        foreach ([
            [$refurbisherGroup, StatusLabelAccessRule::DENY],
            [$supervisorGroup, StatusLabelAccessRule::ALLOW],
        ] as [$group, $access]) {
            StatusLabelAccessRule::create([
                'status_label_id' => $afgevoerd->id,
                'subject_type' => StatusLabelAccessRule::SUBJECT_GROUP,
                'subject_id' => $group->id,
                'view_value' => $access,
                'select_value' => $access,
            ]);
        }

        $service = app(StatusLabelAccessService::class);

        $this->assertFalse($service->canView($refurbisher->fresh(), $afgevoerd));
        $this->assertFalse($service->canSelect($refurbisher->fresh(), $afgevoerd));
        $this->assertTrue($service->canView($supervisor->fresh(), $afgevoerd));
        $this->assertTrue($service->canSelect($supervisor->fresh(), $afgevoerd));

        $this->actingAs($refurbisher);
        $this->assertArrayNotHasKey($afgevoerd->id, Helper::statusLabelList());

        $this->actingAs($supervisor);
        $this->assertArrayHasKey($afgevoerd->id, Helper::statusLabelList());
    }

    public function test_status_form_persists_supervisor_afgevoerd_access(): void
    {
        $afgevoerd = Statuslabel::factory()->pending()->create([
            'name' => 'Temporary status',
            'default_label' => 0,
        ]);
        $refurbisherGroup = Group::factory()->create(['name' => 'Refurbisher']);
        $supervisorGroup = Group::factory()->create(['name' => 'Supervisor']);
        $admin = User::factory()->superuser()->create();

        $this->actingAs($admin)
            ->put(route('statuslabels.update', $afgevoerd), [
                'name' => 'Afgevoerd',
                'statuslabel_types' => 'archived',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_DESTROYED,
                'status_access_groups' => [
                    $refurbisherGroup->id => [
                        'view' => StatusLabelAccessRule::DENY,
                        'select' => StatusLabelAccessRule::DENY,
                    ],
                    $supervisorGroup->id => [
                        'view' => StatusLabelAccessRule::ALLOW,
                        'select' => StatusLabelAccessRule::ALLOW,
                    ],
                ],
            ])
            ->assertRedirect(route('statuslabels.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('status_labels', [
            'id' => $afgevoerd->id,
            'name' => 'Afgevoerd',
            'archived' => 1,
            'lifecycle_stage' => Statuslabel::LIFECYCLE_DESTROYED,
        ]);
        $this->assertDatabaseHas('status_label_access_rules', [
            'status_label_id' => $afgevoerd->id,
            'subject_type' => StatusLabelAccessRule::SUBJECT_GROUP,
            'subject_id' => $supervisorGroup->id,
            'view_value' => StatusLabelAccessRule::ALLOW,
            'select_value' => StatusLabelAccessRule::ALLOW,
        ]);
        $this->assertDatabaseHas('status_label_access_rules', [
            'status_label_id' => $afgevoerd->id,
            'subject_type' => StatusLabelAccessRule::SUBJECT_GROUP,
            'subject_id' => $refurbisherGroup->id,
            'view_value' => StatusLabelAccessRule::DENY,
            'select_value' => StatusLabelAccessRule::DENY,
        ]);
    }
}
