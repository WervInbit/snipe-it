@extends('layouts/default')

@section('title')
{{ $component->component_tag }} {{ $component->display_name }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.tray') }}" class="btn btn-default">
    {{ __('My Tray') }}
</a>
@can('create', \App\Models\ComponentInstance::class)
<a href="{{ route('components.create') }}" class="btn btn-primary">
    {{ __('Register Component') }}
</a>
@endcan
<a href="{{ route('components.index') }}" class="btn btn-default">
    {{ trans('general.back') }}
</a>
@stop

@inject('qrLabels', 'App\\Services\\QrLabelService')

@section('content')
@php
    $lifecycleStatus = $component->effectiveLifecycleStatus();
    $conditionStatus = $component->effectiveConditionStatus();
    $isInstalled = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_ATTACHED;
    $isInTray = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_IN_TRAY;
    $isInStock = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_IN_STOCK;
    $isNeedsAttention = $conditionStatus === \App\Models\ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION;
    $isDamaged = $conditionStatus === \App\Models\ComponentInstance::CONDITION_STATUS_DAMAGED;
    $isDestructionPending = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING;
    $isDestroyed = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_DESTROYED;
    $isSoldReturned = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_SOLD_RETURNED;
    $isLifecycleManaged = !$isDestroyed && !$isSoldReturned;
    $returnTo = route('components.show', $component);
    $statusTransitions = [];

    if ($isInstalled) {
        $statusTransitions[] = [
            'label' => \App\Models\ComponentInstance::lifecycleStatusLabel(\App\Models\ComponentInstance::LIFECYCLE_IN_TRAY),
            'target' => '#componentToTrayModal',
        ];
    }

    if ($isLifecycleManaged) {
        if (!$isInstalled && !$isInStock && !$isDestructionPending) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::lifecycleStatusLabel(\App\Models\ComponentInstance::LIFECYCLE_IN_STOCK),
                'target' => '#componentToStockModal',
            ];
        }

        if (!$isNeedsAttention) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::conditionStatusLabel(\App\Models\ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION),
                'target' => '#componentNeedsVerificationModal',
            ];
        }

        if (!$isDamaged) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::conditionStatusLabel(\App\Models\ComponentInstance::CONDITION_STATUS_DAMAGED),
                'target' => '#componentDefectiveModal',
            ];
        }

        if (!$isInstalled && !$isDestructionPending) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::lifecycleStatusLabel(\App\Models\ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING),
                'target' => '#componentDestructionPendingModal',
            ];
        } elseif ($isDestructionPending) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::lifecycleStatusLabel(\App\Models\ComponentInstance::LIFECYCLE_DESTROYED),
                'target' => '#componentDestroyedModal',
            ];
        }
    }

    $statusHistory = $component->events->filter(fn ($event) => filled($event->from_status) || filled($event->to_status))->values();
    $attachedChildren = $component->childComponents ?? collect();
    $removedExpectedChildren = $removedExpectedSubcomponents ?? collect();
    $expectedSubcomponents = $component->componentDefinition?->subcomponentTemplates ?? collect();
    $childComponentDefinitions = $childComponentDefinitions ?? collect();
    $canCreateChildComponent = $isInstalled
        && !$component->parent_component_instance_id
        && filled($component->current_asset_id);
    $expectedSubcomponentStates = ($component->expectedSubcomponentStates ?? collect())
        ->keyBy('component_definition_subcomponent_template_id');
    $qrFormats = collect(explode(',', $snipeSettings->qr_formats ?? 'png,pdf,qr'))
        ->map(fn ($format) => strtolower(trim($format)))
        ->filter()
        ->values()
        ->all();
    $selectedTemplate = request('template', $snipeSettings->qr_label_template ?? config('qr_templates.default'));
    $qrTemplates = config('qr_templates.templates');
    $componentQrDownloadUrl = in_array('png', $qrFormats, true)
        ? route('components.qr-label.download', ['component_id' => $component, 'template' => $selectedTemplate])
        : null;
    $componentQrPreview = null;
    if (config('qr_templates.enable_ui', true) && ($componentQrDownloadUrl || in_array('pdf', $qrFormats, true))) {
        $componentQrPreview = $qrLabels->previewDataFor($component, $selectedTemplate);
    }
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.details') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ __('Component tag') }}</dt>
                    <dd>{{ $component->component_tag }}</dd>

                    <dt>{{ trans('general.name') }}</dt>
                    <dd>{{ $component->display_name }}</dd>

                    <dt>{{ trans('general.status') }}</dt>
                    <dd>{{ \App\Models\ComponentInstance::lifecycleStatusLabel($lifecycleStatus) ?? $lifecycleStatus }}</dd>

                    <dt>{{ trans('general.type') }}</dt>
                    <dd>{{ \App\Models\ComponentInstance::sourceTypeLabel($component->source_type) ?? $component->source_type }}</dd>

                    <dt>{{ trans('general.condition') }}</dt>
                    <dd>{{ $component->displayConditionLabel() }}</dd>

                    @if($component->serial)
                    <dt>{{ trans('admin/hardware/form.serial') }}</dt>
                    <dd>{{ $component->serial }}</dd>
                    @endif

                    @if($component->componentDefinition)
                    <dt>{{ trans('general.category') }}</dt>
                    <dd>{{ $component->componentDefinition->category?->name }}</dd>

                    <dt>{{ trans('general.manufacturer') }}</dt>
                    <dd>{{ $component->componentDefinition->manufacturer?->name }}</dd>
                    @endif

                    @if($component->supplier)
                    <dt>{{ trans('general.supplier') }}</dt>
                    <dd>{{ $component->supplier->name }}</dd>
                    @endif

                    @if($component->received_at)
                    <dt>{{ trans('general.purchase_date') }}</dt>
                    <dd>{{ $component->received_at?->format('Y-m-d H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.current') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ trans('general.asset') }}</dt>
                    <dd>
                        @if($component->currentAsset)
                        <a href="{{ route('hardware.show', $component->currentAsset) }}">{{ $component->currentAsset->present()->name() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.location') }}</dt>
                    <dd>
                        @if($component->storageLocation)
                        {{ $component->storageLocation->name }}
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.user') }}</dt>
                    <dd>
                        @if($component->heldBy)
                        <a href="{{ route('users.show', $component->heldBy) }}">{{ $component->heldBy->present()->fullName() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.source') }}</dt>
                    <dd>
                        @if($component->sourceAsset)
                        <a href="{{ route('hardware.show', $component->sourceAsset) }}">{{ $component->sourceAsset->present()->name() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @if (config('qr_templates.enable_ui', true) && ($componentQrPreview || $componentQrDownloadUrl))
            <div data-testid="component-qr-action-panel">
                @include('hardware.partials.qr-label-widget', [
                    'qrPng' => null,
                    'downloadUrl' => $componentQrDownloadUrl,
                    'qrPreview' => $componentQrPreview,
                    'qrTemplates' => $qrTemplates,
                    'selectedTemplate' => $selectedTemplate,
                    'labelTargetName' => $component->display_name ?: ($component->component_tag ?? $component->id),
                    'templatePickerId' => 'component-template-picker',
                    'printerPickerId' => 'component-printer-picker',
                    'printButtonId' => 'component-qr-server-print-button',
                    'printUrl' => Route::has('components.print-label') ? route('components.print-label', $component) : null,
                    'printButtonLabel' => __('Print QR label'),
                ])
            </div>
        @endif

        @if(in_array($lifecycleStatus, [
            \App\Models\ComponentInstance::LIFECYCLE_IN_STOCK,
            \App\Models\ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
        ], true))
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Storage Location') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('Components can be moved into stock first and assigned to a specific storage location later here.') }}</p>
                    @can('update', $component)
                        <form method="POST" action="{{ route('components.update', $component) }}">
                            @csrf
                            @method('PUT')
                            <div class="form-group {{ $errors->has('storage_location_id') ? 'has-error' : '' }}">
                                <label for="component_storage_location_id">{{ __('Storage Location') }}</label>
                                <select class="form-control" id="component_storage_location_id" name="storage_location_id">
                                    <option value="">{{ __('No specific storage location yet') }}</option>
                                    @foreach($editableStorageLocations as $location)
                                        <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                            {{ $location->name }} ({{ \Illuminate\Support\Str::headline($location->type) }})
                                            @if($location->siteLocation)
                                                - {{ $location->siteLocation->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                {!! $errors->first('storage_location_id', '<span class="help-block">:message</span>') !!}
                            </div>
                            <div class="form-group {{ $errors->has('storage_location_note') ? 'has-error' : '' }}">
                                <label for="component_storage_location_note">{{ trans('general.notes') }}</label>
                                <textarea class="form-control" id="component_storage_location_note" name="storage_location_note" rows="3" placeholder="{{ __('Optional note for the storage-location change') }}">{{ old('storage_location_note') }}</textarea>
                                {!! $errors->first('storage_location_note', '<span class="help-block">:message</span>') !!}
                            </div>
                            <button type="submit" class="btn btn-default">{{ __('Save Storage Location') }}</button>
                        </form>
                    @else
                        <p class="text-muted">{{ __('You do not have permission to change the storage location for this component.') }}</p>
                    @endcan
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.actions') }}</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">{{ __('Launch the next workflow for this component from the buttons below.') }}</p>

                <div class="btn-toolbar" role="toolbar">
                    @if(!empty($statusTransitions))
                        <div class="form-group" style="display:inline-block; min-width:260px; margin-right:10px; margin-bottom:0; vertical-align:top;">
                            <label class="sr-only" for="component_status_transition">{{ __('Status') }}</label>
                            <select class="form-control" id="component_status_transition">
                                <option value="">{{ __('Status') }}: {{ \App\Models\ComponentInstance::lifecycleStatusLabel($lifecycleStatus) ?? $lifecycleStatus }}</option>
                                @foreach($statusTransitions as $transition)
                                    <option value="{{ $transition['target'] }}">{{ $transition['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($isInstalled)
                        @can('install', $component)
                            @if($component->currentAsset)
                                <a href="{{ route('hardware.components.transfer.create', [$component->currentAsset, $component]) }}" class="btn btn-primary">{{ __('Move To Other Device') }}</a>
                            @endif
                        @endcan
                    @elseif (!$isDestroyed)
                        @can('install', $component)
                            @if(!$isDestructionPending)
                                <a href="{{ route('components.install.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-primary">{{ __('Install') }}</a>
                            @endif
                        @endcan
                    @endif

                    @if($component->currentAsset)
                        <a href="{{ route('hardware.show', $component->currentAsset) }}" class="btn btn-default">{{ __('Open Asset') }}</a>
                    @endif
                    <a href="{{ route('components.tray') }}" class="btn btn-default">{{ __('My Tray') }}</a>
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Child Structure') }}</h3>
            </div>
            <div class="box-body">
                <h4 style="margin-top:0;">{{ __('Attached Child Components') }}</h4>
                @if($attachedChildren->isEmpty())
                    <p class="text-muted">{{ __('No child components attached.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.tag') }}</th>
                                <th>{{ trans('general.status') }}</th>
                                <th>{{ trans('general.condition') }}</th>
                                <th>{{ trans('general.asset') }}</th>
                                <th>{{ trans('general.action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($attachedChildren as $child)
                                <tr>
                                    <td>
                                        <a href="{{ route('components.show', $child) }}">{{ $child->display_name }}</a>
                                        @if($child->componentDefinition)
                                            <div class="text-muted small">{{ $child->componentDefinition->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('components.show', $child) }}">{{ $child->component_tag }}</a>
                                    </td>
                                    <td>{{ \App\Models\ComponentInstance::lifecycleStatusLabel($child->effectiveLifecycleStatus()) ?? $child->effectiveLifecycleStatus() }}</td>
                                    <td>{{ $child->displayConditionLabel() }}</td>
                                    <td>
                                        @if($child->currentAsset)
                                            <a href="{{ route('hardware.show', $child->currentAsset) }}">{{ $child->currentAsset->present()->name() }}</a>
                                        @else
                                            <span class="text-muted">{{ trans('general.na') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @can('move', $child)
                                            <a href="{{ route('components.remove_to_tray.create', [$child, 'return_to' => route('components.show', $component)]) }}" class="btn btn-xs btn-warning">{{ __('To Tray') }}</a>
                                            <form method="POST" action="{{ route('components.move_to_stock', $child) }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="return_to" value="{{ route('components.show', $component) }}">
                                                <button type="submit" class="btn btn-xs btn-default">{{ __('To Stock') }}</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('create', \App\Models\ComponentInstance::class)
                    @can('install', new \App\Models\ComponentInstance())
                        @if($canCreateChildComponent)
                            <h4>{{ __('Add Child Component') }}</h4>
                            <form method="POST" action="{{ route('components.children.store', $component) }}">
                                @csrf
                                @include('components.partials.manual-fields', [
                                    'componentDefinitions' => $childComponentDefinitions,
                                    'conditionOptions' => $conditionOptions,
                                    'notesField' => 'note',
                                    'showSourceType' => false,
                                    'showCondition' => true,
                                    'showStorageLocation' => false,
                                    'showInstalledAs' => false,
                                    'showCreationModeToggle' => true,
                                    'creationModeField' => 'creation_mode',
                                ])
                                @include('components.partials.condition-warning-confirmation', [
                                    'conditionStatus' => \App\Models\ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
                                    'message' => __('If the child component condition is Unknown, Poor, or Broken, confirm the warning before attaching it.'),
                                    'checkboxLabel' => __('I understand the selected child component condition and want to attach it.'),
                                ])
                                <button type="submit" class="btn btn-success">{{ __('Create Child Component') }}</button>
                            </form>
                        @endif
                    @endcan
                @endcan

                <h4>{{ __('Removed Expected Child Components') }}</h4>
                @if($removedExpectedChildren->isEmpty())
                    <p class="text-muted">{{ __('No expected child components removed.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.tag') }}</th>
                                <th>{{ trans('general.status') }}</th>
                                <th>{{ trans('general.location') }}</th>
                                <th>{{ trans('general.action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($removedExpectedChildren as $child)
                                <tr>
                                    <td>
                                        <a href="{{ route('components.show', $child) }}">{{ $child->display_name }}</a>
                                        @if($child->componentDefinition)
                                            <div class="text-muted small">{{ $child->componentDefinition->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('components.show', $child) }}">{{ $child->component_tag }}</a>
                                    </td>
                                    <td>{{ \App\Models\ComponentInstance::lifecycleStatusLabel($child->effectiveLifecycleStatus()) ?? $child->effectiveLifecycleStatus() }}</td>
                                    <td>
                                        @if($child->storageLocation)
                                            {{ $child->storageLocation->name }}
                                        @elseif($child->heldBy)
                                            {{ __('Tray') }}: {{ $child->heldBy->present()->fullName() }}
                                        @elseif($child->currentAsset)
                                            <a href="{{ route('hardware.show', $child->currentAsset) }}">{{ $child->currentAsset->present()->name() }}</a>
                                        @else
                                            <span class="text-muted">{{ trans('general.na') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('components.show', $child) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h4>{{ __('Expected Subcomponents') }}</h4>
                @if($expectedSubcomponents->isEmpty())
                    <p class="text-muted">{{ __('No expected subcomponents defined.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ __('Definition') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Required') }}</th>
                                <th>{{ trans('general.notes') }}</th>
                                <th>{{ trans('general.action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($expectedSubcomponents as $template)
                                @php
                                    $state = $expectedSubcomponentStates->get($template->id);
                                    $expectedQty = max(1, (int) $template->expected_qty);
                                    $materializedQty = min($expectedQty, max(0, (int) ($state?->materialized_qty ?? 0)));
                                    $removedQty = min($expectedQty - $materializedQty, max(0, (int) ($state?->removed_qty ?? 0)));
                                    $remainingQty = max(0, $expectedQty - $materializedQty - $removedQty);
                                    $canMaterializeExpectedChild = $lifecycleStatus === \App\Models\ComponentInstance::LIFECYCLE_ATTACHED
                                        && filled($component->current_asset_id)
                                        && $remainingQty > 0;
                                @endphp
                                <tr>
                                    <td>{{ $template->expected_name }}</td>
                                    <td>
                                        @if($template->childComponentDefinition)
                                            {{ $template->childComponentDefinition->name }}
                                            @if($template->childComponentDefinition->part_code)
                                                <div class="text-muted small">{{ $template->childComponentDefinition->part_code }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('Freeform') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ __('Expected') }}: {{ $expectedQty }}
                                        <div class="text-muted small">{{ __('Tracked') }}: {{ $materializedQty }} | {{ __('Removed') }}: {{ $removedQty }} | {{ __('Remaining') }}: {{ $remainingQty }}</div>
                                    </td>
                                    <td>{{ $template->is_required ? __('Yes') : __('No') }}</td>
                                    <td>{{ $template->notes ?: trans('general.none') }}</td>
                                    <td class="text-nowrap">
                                        @can('install', new \App\Models\ComponentInstance())
                                            @if($canMaterializeExpectedChild)
                                                <form method="POST" action="{{ route('components.expected_subcomponents.materialize', [$component, $template]) }}" class="form-inline">
                                                    @csrf
                                                    @include('components.partials.condition-warning-confirmation', [
                                                        'component' => null,
                                                        'compact' => true,
                                                        'conditionStatus' => \App\Models\ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
                                                        'message' => __('Tracking creates a Needs Attention child component until it is verified.'),
                                                        'checkboxLabel' => __('Confirm warning'),
                                                    ])
                                                    <div class="input-group input-group-sm" style="max-width:280px;">
                                                        <input type="text" name="note" class="form-control" placeholder="{{ __('Reason') }}">
                                                        <span class="input-group-btn">
                                                            <button type="submit" class="btn btn-primary">{{ __('Track') }}</button>
                                                        </span>
                                                    </div>
                                                </form>
                                            @else
                                                <span class="text-muted">{{ $remainingQty === 0 ? __('Complete') : __('Unavailable') }}</span>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if ($isInstalled)
            @can('move', $component)
                <div class="modal fade" id="componentToTrayModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentToTrayModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('components.remove_to_tray', $component) }}">
                                @csrf
                                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="componentToTrayModalLabel">{{ __('Move To Tray') }}</h4>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted">{{ __('Remove this component from its current asset and place it in your tray.') }}</p>
                                    <div class="alert alert-info">
                                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                                        @if($component->currentAsset)
                                            <br><strong>{{ __('Current Asset') }}:</strong> {{ $component->currentAsset->present()->name() }}
                                        @endif
                                    </div>
                                    @include('components.partials.serial-change-control', [
                                        'component' => $component,
                                        'serialId' => 'component_remove_serial_modal',
                                    ])
                                    <div class="form-group">
                                        <label for="component_remove_note_modal">{{ trans('general.notes') }}</label>
                                        <textarea class="form-control" id="component_remove_note_modal" name="note" rows="4"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                    <button type="submit" class="btn btn-warning">{{ __('Confirm To Tray') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        @if ($isInTray && !$isDestroyed)
            <div class="modal fade" id="componentToStockModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentToStockModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.move_to_stock', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentToStockModalLabel">{{ __('Move To Stock') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as in stock. You can assign a specific storage location later on this page.') }}</p>
                                <div class="form-group">
                                    <label for="component_move_to_stock_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_move_to_stock_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-default">{{ __('Confirm In Stock') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (!$isDestroyed && !$isNeedsAttention)
            <div class="modal fade" id="componentNeedsVerificationModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentNeedsVerificationModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.flag_needs_verification', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentNeedsVerificationModalLabel">{{ __('Mark Needs Attention') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as needing attention without changing where it is attached or stored.') }}</p>
                                <div class="form-group">
                                    <label for="component_needs_verification_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_needs_verification_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-warning">{{ __('Confirm Needs Attention') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (!$isDestroyed && !$isDamaged)
            <div class="modal fade" id="componentDefectiveModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentDefectiveModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.mark_defective', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentDefectiveModalLabel">{{ __('Mark Damaged') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as damaged without changing where it is attached or stored.') }}</p>
                                <div class="form-group">
                                    <label for="component_defective_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_defective_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Damaged') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (!$isDestroyed && !$isDestructionPending)
            <div class="modal fade" id="componentDestructionPendingModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentDestructionPendingModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.mark_destruction_pending', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentDestructionPendingModalLabel">{{ __('Mark Destruction Pending') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as pending destruction.') }}</p>
                                <div class="form-group">
                                    <label for="component_destruction_pending_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_destruction_pending_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Destruction Pending') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($isDestructionPending)
            <div class="modal fade" id="componentDestroyedModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentDestroyedModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.mark_destroyed', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentDestroyedModalLabel">{{ __('Mark Destroyed') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Confirm that this component has been destroyed or recycled.') }}</p>
                                <div class="form-group">
                                    <label for="component_destroyed_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_destroyed_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Destroyed') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (!$isInstalled)
            @can('delete', $component)
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Delete Component') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('components.destroy', $component) }}">
                        <div class="box-body">
                            @csrf
                            @method('DELETE')
                            <p class="text-muted">{{ __('Only loose or inactive components can be deleted. Installed components must be removed first.') }}</p>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                        </div>
                    </form>
                </div>
            @endcan
        @endif

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.notes') }}</h3>
            </div>
            <div class="box-body">
                @can('update', $component)
                    <form method="POST" action="{{ route('components.update', $component) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <textarea class="form-control" name="notes" id="component_notes" rows="4" placeholder="{{ __('Add a note for this component') }}">{{ old('notes', $component->notes ?? '') }}</textarea>
                            {!! $errors->first('notes', '<span class="help-block">:message</span>') !!}
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save Note') }}</button>
                    </form>
                @else
                    @if($component->notes)
                        <div>{!! nl2br(\App\Helpers\Helper::parseEscapedMarkedownInline($component->notes)) !!}</div>
                    @else
                        <p class="text-muted">{{ trans('general.none') }}</p>
                    @endif
                @endcan
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.file_uploads') }}</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">{{ __('Upload photos or files for this component here.') }}</p>
                <x-filestable object_type="component-instances" :object="$component" />
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Status History') }}</h3>
            </div>
            <div class="box-body table-responsive">
                @if($statusHistory->isEmpty())
                    <p class="text-muted">{{ trans('general.none') }}</p>
                @else
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ trans('general.date') }}</th>
                            <th>{{ trans('general.from') }}</th>
                            <th>{{ trans('general.to') }}</th>
                            <th>{{ trans('general.user') }}</th>
                            <th>{{ trans('general.notes') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($statusHistory as $event)
                            <tr>
                                <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ \App\Models\ComponentInstance::statusLabel($event->from_status) ?? trans('general.none') }}</td>
                                <td>{{ \App\Models\ComponentInstance::statusLabel($event->to_status) ?? trans('general.none') }}</td>
                                <td>{{ $event->performedBy?->present()->fullName() ?? trans('general.system') }}</td>
                                <td>{{ $event->note ?: trans('general.none') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.history') }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ trans('general.date') }}</th>
                        <th>{{ trans('general.action') }}</th>
                        <th>{{ trans('general.user') }}</th>
                        <th>{{ trans('general.details') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($component->events as $event)
                        <tr>
                            <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->actionLabel() }}</td>
                            <td>{{ $event->performedBy?->present()->fullName() ?? trans('general.system') }}</td>
                            <td>
                                @php
                                    $details = collect([
                                        $event->fromAsset ? 'From asset: '.$event->fromAsset->present()->name() : null,
                                        $event->toAsset ? 'To asset: '.$event->toAsset->present()->name() : null,
                                        $event->fromStorageLocation ? 'From location: '.$event->fromStorageLocation->name : null,
                                        $event->toStorageLocation ? 'To location: '.$event->toStorageLocation->name : null,
                                        $event->heldBy ? 'Held by: '.$event->heldBy->present()->fullName() : null,
                                        $event->relatedWorkOrder ? 'Work order: '.$event->relatedWorkOrder->work_order_number : null,
                                        $event->relatedWorkOrderTask ? 'Task: '.$event->relatedWorkOrderTask->title : null,
                                        $event->isAutoAgedVerificationEscalation() ? 'Triggered automatically by tray aging.' : null,
                                        $event->note,
                                    ])->filter()->values();
                                @endphp
                                @if($details->isEmpty())
                                <span class="text-muted">{{ trans('general.na') }}</span>
                                @else
                                @php
                                    $taskWorkOrder = $event->relatedWorkOrderTask?->workOrder;
                                @endphp
                                @if($event->relatedWorkOrder || $event->relatedWorkOrderTask)
                                    @foreach($details as $detail)
                                        @if(\Illuminate\Support\Str::startsWith($detail, 'From asset:') && $event->fromAsset && auth()->user()?->can('view', $event->fromAsset))
                                        <a href="{{ route('hardware.show', $event->fromAsset) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'To asset:') && $event->toAsset && auth()->user()?->can('view', $event->toAsset))
                                        <a href="{{ route('hardware.show', $event->toAsset) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'Work order:') && $event->relatedWorkOrder)
                                        <a href="{{ route('work-orders.show', $event->relatedWorkOrder) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'Task:') && $taskWorkOrder && $event->relatedWorkOrderTask)
                                        <a href="{{ route('work-orders.show', $taskWorkOrder) }}#task-{{ $event->relatedWorkOrderTask->id }}">{{ $detail }}</a>
                                        @else
                                        {{ $detail }}
                                        @endif
                                        @if(!$loop->last)
                                        <span> | </span>
                                        @endif
                                    @endforeach
                                @else
                                    @foreach($details as $detail)
                                        @if(\Illuminate\Support\Str::startsWith($detail, 'From asset:') && $event->fromAsset && auth()->user()?->can('view', $event->fromAsset))
                                        <a href="{{ route('hardware.show', $event->fromAsset) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'To asset:') && $event->toAsset && auth()->user()?->can('view', $event->toAsset))
                                        <a href="{{ route('hardware.show', $event->toAsset) }}">{{ $detail }}</a>
                                        @else
                                        {{ $detail }}
                                        @endif
                                        @if(!$loop->last)
                                        <span> | </span>
                                        @endif
                                    @endforeach
                                @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
    @parent
    <script>
        (function () {
            var select = document.getElementById('component_status_transition');
            if (!select) {
                return;
            }

            function resetSelect() {
                select.value = '';
            }

            select.addEventListener('change', function () {
                var target = select.value;
                if (!target) {
                    return;
                }

                var modalEl = document.querySelector(target);
                if (!modalEl) {
                    resetSelect();
                    return;
                }

                if (window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    return;
                }

                var $ = window.jQuery || window.$;
                if ($ && $.fn && $.fn.modal) {
                    $(modalEl).modal('show');
                    return;
                }

                resetSelect();
            });

            document.querySelectorAll('[data-component-status-modal]').forEach(function (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', resetSelect);
            });
        })();
    </script>
@stop
