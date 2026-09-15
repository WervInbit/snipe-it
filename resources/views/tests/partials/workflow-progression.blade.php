@php
    use App\Services\WorkflowProgressionService;

    $workflowContext = $workflowContext ?? 'workflow';
    $workflowRows = $workflowProgression ?? collect();
    $manualItems = $manualWorkflowItems ?? collect();
    $normalFormId = $workflowContext . '-workflow-start-form';
    $overrideModalId = $workflowContext . '-workflow-override-modal';
    $overrideFormId = $workflowContext . '-workflow-override-form';
    $normalFormTestId = match ($workflowContext) {
        'hardware-tests' => 'hardware-tests-start-form',
        'tests-index' => 'tests-index-start-run-form',
        'tests-empty' => 'tests-empty-start-run-form',
        default => 'workflow-start-form',
    };
@endphp

@once
    @push('css')
        <style>
            .workflow-progression {
                margin-bottom: 1.5rem;
            }
            .workflow-progression__list {
                display: flex;
                flex-direction: column;
                gap: 0.6rem;
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .workflow-progression__item {
                display: grid;
                grid-template-columns: 2.4rem minmax(0, 1fr) auto;
                gap: 0.8rem;
                align-items: center;
                padding: 0.85rem 1rem;
                border: 1px solid #d7dee8;
                border-radius: 6px;
                background: #fff;
            }
            .workflow-progression__item--locked {
                color: #6b7280;
                background: #f1f3f5;
                border-color: #d8dde3;
            }
            .workflow-progression__number {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.1rem;
                height: 2.1rem;
                border-radius: 50%;
                background: #e8edf4;
                color: #344054;
                font-weight: 700;
            }
            .workflow-progression__item--locked .workflow-progression__number {
                background: #d9dde2;
                color: #747b84;
            }
            .workflow-progression__name {
                display: block;
                color: inherit;
                font-weight: 600;
            }
            .workflow-progression__meta {
                margin-top: 0.2rem;
                font-size: 0.9em;
            }
            .workflow-progression__actions {
                min-width: 13rem;
                text-align: right;
            }
            .workflow-progression__actions .btn + .btn {
                margin-left: 0.35rem;
            }
            .workflow-progression__extras {
                margin-bottom: 1rem;
            }
            .workflow-progression--compact .workflow-progression__list {
                gap: 0.35rem;
            }
            .workflow-progression--compact .workflow-progression__item {
                padding: 0.55rem 0.8rem;
            }
            .workflow-progression--compact .workflow-progression__number {
                width: 1.8rem;
                height: 1.8rem;
            }
            @media (max-width: 640px) {
                .workflow-progression__item {
                    grid-template-columns: 2.4rem minmax(0, 1fr);
                }
                .workflow-progression__actions {
                    grid-column: 2;
                    min-width: 0;
                    text-align: left;
                }
            }
        </style>
    @endpush
@endonce

<section class="workflow-progression{{ $workflowRows->count() > 6 ? ' workflow-progression--compact' : '' }}" data-testid="workflow-progression">
    @if($workflowRows->isEmpty())
        <div class="alert alert-warning mb-0" data-testid="no-workflow-profiles">
            {{ trans('tests.no_workflow_profiles_available') }}
        </div>
    @else
        @if($canStartRun ?? false)
            <form method="POST"
                  action="{{ route('test-runs.store', $asset->id) }}"
                  id="{{ $normalFormId }}"
                  class="mb-3"
                  data-testid="{{ $normalFormTestId }}">
                @csrf
                @if($manualItems->isNotEmpty())
                    <div class="form-group workflow-progression__extras">
                        <label for="{{ $workflowContext }}-extra-workflow-item-ids">{{ __('Extra workflow items') }}</label>
                        <select id="{{ $workflowContext }}-extra-workflow-item-ids"
                                name="extra_workflow_item_ids[]"
                                class="form-control"
                                multiple>
                            @foreach($manualItems as $item)
                                <option value="{{ $item->id }}" @selected(in_array($item->id, (array) old('extra_workflow_item_ids', [])))>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">{{ __('Optional one-off checks are added after the selected workflow steps.') }}</span>
                    </div>
                @endif
            </form>
        @endif

        <ol class="workflow-progression__list">
            @foreach($workflowRows as $row)
                @php
                    $profile = $row['profile'];
                    $canExecuteProfile = auth()->check() && $profile->canBeExecutedBy(auth()->user());
                    $canEditExisting = $row['run'] && Gate::allows('update', $row['run']);
                    $isGuarded = $row['dependency_override_required'] || $row['repeat_override_required'];
                    $canOverrideThisGuard = $canExecuteProfile
                        && (!$row['dependency_override_required'] || ($canOverrideWorkflowGuards ?? false))
                        && (!$row['repeat_override_required'] || ($canStartNewRun ?? false));
                    $isLocked = $row['configuration_blocked']
                        || $isGuarded
                        || $row['repeat_forbidden']
                        || !$canExecuteProfile;
                    $stateLabel = match ($row['state']) {
                        WorkflowProgressionService::STATE_IN_PROGRESS => __('In progress'),
                        WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES => __('Completed with issues'),
                        WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY => __('Completed successfully'),
                        WorkflowProgressionService::STATE_STALE => __('Changed - new run needed'),
                        default => __('Not started'),
                    };
                    if ($row['configuration_blocked']) {
                        $stateLabel = __('Needs configuration');
                    } elseif (!$row['can_continue'] && $row['blockers']->isNotEmpty()) {
                        $stateLabel = $row['state'] === WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY
                            ? __('Completed; prerequisites pending')
                            : __('Waiting for prerequisites');
                    }
                    $stateClass = $row['configuration_blocked'] || $isGuarded
                        ? 'label-default'
                        : ($row['state'] === WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY
                            ? 'label-success'
                            : ($row['state'] === WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES
                                ? 'label-warning'
                                : 'label-default'));
                    $guardMessages = collect();
                    if ($row['configuration_blocked']) {
                        $guardMessages->push(__('This workflow has no applicable items for this asset. Configure its items before starting it.'));
                    }
                    if ($row['can_continue'] && $row['blockers']->isNotEmpty()) {
                        $guardMessages->push(__('Dependencies changed after this run started; the existing run can still be continued.'));
                    } else {
                        foreach ($row['blockers'] as $blocker) {
                            $guardMessages->push(match (true) {
                                $blocker['actual_state'] === 'empty' => __('Configure at least one applicable item in :name.', [
                                    'name' => $blocker['profile_name'],
                                ]),
                                $blocker['actual_state'] === 'invalid_dependency_graph' => __('The dependency graph for :name is invalid.', [
                                    'name' => $blocker['profile_name'],
                                ]),
                                $blocker['actual_state'] === WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY
                                    && !$blocker['prerequisites_satisfied'] => __('Complete the unmet prerequisites for :name.', [
                                        'name' => $blocker['profile_name'],
                                    ]),
                                default => __('All required items in :name must pass or be done.', [
                                        'name' => $blocker['profile_name'],
                                    ]),
                            });
                        }
                    }
                    if ($row['repeat_override_required']) {
                        $guardMessages->push($row['state'] === WorkflowProgressionService::STATE_STALE
                            ? __('The workflow definition changed after the current run started.')
                            : __('This workflow already has a completed run.'));
                    }
                    if ($row['repeat_forbidden']) {
                        $guardMessages->push(__('This workflow cannot be repeated.'));
                    }
                    if (!$canExecuteProfile) {
                        $guardMessages->push(__('Restricted to :level users.', [
                            'level' => ucfirst($profile->execution_level),
                        ]));
                    }
                    $guardWarning = $guardMessages->implode(' ');
                @endphp
                <li class="workflow-progression__item{{ $isLocked ? ' workflow-progression__item--locked' : '' }}"
                    data-testid="workflow-step"
                    data-workflow-position="{{ $row['position'] }}"
                    data-workflow-profile-id="{{ $profile->id }}">
                    <span class="workflow-progression__number" aria-label="{{ __('Step :number', ['number' => $row['position']]) }}">
                        {{ $row['position'] }}
                    </span>
                    <div>
                        <span class="workflow-progression__name">{{ $profile->name }}</span>
                        <div class="workflow-progression__meta">
                            <span class="label {{ $stateClass }}">
                                {{ $stateLabel }}
                            </span>
                            @if($profile->execution_level !== \App\Models\WorkflowProfile::EXECUTION_OPERATOR)
                                <span class="label label-default">{{ ucfirst($profile->execution_level) }}</span>
                            @endif
                            @if($guardWarning !== '')
                                <span>{{ $guardWarning }}</span>
                            @elseif($profile->description)
                                <span>{{ \Illuminate\Support\Str::limit($profile->description, 120) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="workflow-progression__actions">
                        @if($row['run'] && $canEditExisting)
                            <a class="btn btn-primary btn-sm"
                               href="{{ route('test-results.active', ['asset' => $asset->id, 'run' => $row['run']->id]) }}">
                                {{ $row['can_continue'] ? __('Continue') : __('Edit') }}
                            </a>
                        @endif

                        @if(!($canStartRun ?? false) || !$canExecuteProfile)
                            <button type="button" class="btn btn-default btn-sm" disabled>{{ __('Unavailable') }}</button>
                        @elseif($row['can_continue'])
                            @if(!$canEditExisting)
                                <button type="button" class="btn btn-default btn-sm" disabled>{{ __('In progress') }}</button>
                            @endif
                        @elseif($row['configuration_blocked'])
                            <button type="button" class="btn btn-default btn-sm" disabled>{{ __('Needs configuration') }}</button>
                        @elseif($row['can_start'])
                            <button type="submit"
                                    form="{{ $normalFormId }}"
                                    name="workflow_profile_id"
                                    value="{{ $profile->id }}"
                                    class="btn btn-primary btn-sm">
                                {{ __('Start') }}
                            </button>
                        @elseif($isGuarded && $canOverrideThisGuard)
                            <button type="button"
                                    class="btn btn-warning btn-sm"
                                    data-toggle="modal"
                                    data-target="#{{ $overrideModalId }}"
                                    data-profile-id="{{ $profile->id }}"
                                    data-profile-name="{{ $profile->name }}"
                                    data-starts-new="{{ $row['run'] ? '1' : '0' }}"
                                    data-guard-warning="{{ $guardWarning }}">
                                {{ $row['run'] ? __('Start new...') : __('Override and start...') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-default btn-sm" disabled>
                                {{ $row['repeat_forbidden'] ? __('Repeat blocked') : __('Blocked') }}
                            </button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>

@if(($canStartRun ?? false) && (($canOverrideWorkflowGuards ?? false) || ($canStartNewRun ?? false)) && $workflowRows->isNotEmpty())
    <div class="modal fade"
         id="{{ $overrideModalId }}"
         tabindex="-1"
         role="dialog"
         aria-hidden="true"
         aria-labelledby="{{ $overrideModalId }}-label">
        <div class="modal-dialog" role="document">
            <form method="POST"
                  action="{{ route('test-runs.store', $asset->id) }}"
                  id="{{ $overrideFormId }}">
                @csrf
                <input type="hidden" name="workflow_profile_id" value="">
                <div data-override-extra-items></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="{{ $overrideModalId }}-label">{{ __('Start a new workflow run') }}</h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong data-override-profile-name></strong>
                            <div data-override-warning></div>
                            <div>{{ __('The existing run remains editable. Starting another run can make the history incomplete or out of sequence, so this decision will be recorded.') }}</div>
                        </div>
                        <div class="form-group">
                            <label for="{{ $workflowContext }}-workflow-override-reason">{{ __('Reason') }}</label>
                            <textarea id="{{ $workflowContext }}-workflow-override-reason"
                                      name="workflow_override_reason"
                                      class="form-control"
                                      rows="3"
                                      maxlength="2000"
                                      required></textarea>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="confirm_workflow_override" value="1" required>
                                {{ __('I understand the warning and want to start this workflow anyway.') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                        <button type="submit" class="btn btn-warning">{{ __('Confirm override and start') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
                var modal = $('#{{ $overrideModalId }}');
                var overrideForm = document.getElementById('{{ $overrideFormId }}');
                var normalForm = document.getElementById('{{ $normalFormId }}');

                // Bootstrap renders the backdrop under <body>. Hoist this
                // dialog out of tab panes so it always stays above that
                // backdrop and its inputs remain clickable.
                modal.appendTo(document.body);

                modal.on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var modalElement = $(this);
                    modalElement.find('.modal-title').text(
                        button.data('starts-new') === 1
                            ? '{{ __('Start a new workflow run') }}'
                            : '{{ __('Override dependency and start workflow') }}'
                    );
                    modalElement.find('input[name="workflow_profile_id"]').val(button.data('profile-id'));
                    modalElement.find('[data-override-profile-name]').text(button.data('profile-name'));
                    modalElement.find('[data-override-warning]').text(button.data('guard-warning'));
                });

                overrideForm.addEventListener('submit', function () {
                    var target = overrideForm.querySelector('[data-override-extra-items]');
                    target.innerHTML = '';

                    if (!normalForm) {
                        return;
                    }

                    normalForm.querySelectorAll('select[name="extra_workflow_item_ids[]"] option:checked').forEach(function (option) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'extra_workflow_item_ids[]';
                        input.value = option.value;
                        target.appendChild(input);
                    });
                });
            })();
        </script>
    @endpush
@endif
