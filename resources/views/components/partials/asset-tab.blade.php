@php
    $installedComponents = $asset->trackedComponents->sortByDesc('updated_at')->values();
    $expectedComponents = $asset->modelNumber?->componentTemplates ?? collect();
    $selectedDefinitionId = request('component_definition_id');
    $selectedInstalledAs = request('component_slot');
    $selectedDisplayName = request('component_name');
@endphp

<div class="tab-pane fade" id="components">
    <div class="row{{ ($installedComponents->isNotEmpty() || $expectedComponents->isNotEmpty() || $componentHistory->isNotEmpty()) ? '' : ' hidden-print' }}">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    @can('install', new \App\Models\ComponentInstance())
                        <div class="panel panel-default" id="components-install-from-tray">
                            <div class="panel-heading">{{ __('Install From Tray') }}</div>
                            <div class="panel-body">
                                <form method="POST" action="{{ route('hardware.components.install-tray', $asset) }}">
                                    @csrf
                                    <div class="form-group {{ $errors->has('component_id') ? 'has-error' : '' }}">
                                        <label for="tray_component_id_{{ $asset->id }}">{{ __('Tray Component') }}</label>
                                        <select class="form-control" id="tray_component_id_{{ $asset->id }}" name="component_id" required>
                                            <option value="">{{ __('Choose a tray component') }}</option>
                                            @foreach ($currentUserTrayComponents as $trayComponent)
                                                <option value="{{ $trayComponent->id }}" @selected((string) old('component_id') === (string) $trayComponent->id)>
                                                    {{ $trayComponent->component_tag }} - {{ $trayComponent->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        {!! $errors->first('component_id', '<span class="help-block">:message</span>') !!}
                                    </div>
                                    <div class="form-group">
                                        <label for="tray_installed_as_{{ $asset->id }}">{{ __('Installed As / Slot') }}</label>
                                        <input type="text" class="form-control" id="tray_installed_as_{{ $asset->id }}" name="installed_as" value="{{ old('installed_as', $selectedInstalledAs) }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="tray_install_note_{{ $asset->id }}">{{ trans('general.notes') }}</label>
                                        <textarea class="form-control" id="tray_install_note_{{ $asset->id }}" name="note" rows="2">{{ old('note') }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{ __('Install From Tray') }}</button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>
                <div class="col-md-6">
                    @can('install', new \App\Models\ComponentInstance())
                        <div class="panel panel-default" id="components-install-existing">
                            <div class="panel-heading">{{ __('Install Existing Component') }}</div>
                            <div class="panel-body">
                                <form method="POST" action="{{ route('hardware.components.install-existing', $asset) }}">
                                    @csrf
                                    <div class="form-group {{ $errors->has('component_id') ? 'has-error' : '' }}">
                                        <label for="existing_component_id_{{ $asset->id }}">{{ __('Existing Component') }}</label>
                                        <select
                                            class="js-data-ajax select2"
                                            data-endpoint="components"
                                            data-placeholder="{{ __('Search components') }}"
                                            aria-label="component_id"
                                            id="existing_component_id_{{ $asset->id }}"
                                            name="component_id"
                                            style="width: 100%"
                                            required
                                        >
                                            <option value="">{{ __('Search components') }}</option>
                                            @if (old('component_id'))
                                                @php($selectedComponent = \App\Models\ComponentInstance::find(old('component_id')))
                                                @if ($selectedComponent)
                                                    <option value="{{ $selectedComponent->id }}" selected="selected">{{ $selectedComponent->component_tag }} {{ $selectedComponent->display_name }}</option>
                                                @endif
                                            @endif
                                        </select>
                                        {!! $errors->first('component_id', '<span class="help-block">:message</span>') !!}
                                    </div>
                                    <div class="form-group">
                                        <label for="existing_installed_as_{{ $asset->id }}">{{ __('Installed As / Slot') }}</label>
                                        <input type="text" class="form-control" id="existing_installed_as_{{ $asset->id }}" name="installed_as" value="{{ old('installed_as', $selectedInstalledAs) }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="existing_install_note_{{ $asset->id }}">{{ trans('general.notes') }}</label>
                                        <textarea class="form-control" id="existing_install_note_{{ $asset->id }}" name="note" rows="2">{{ old('note') }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-default">{{ __('Install Existing') }}</button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    @can('create', \App\Models\ComponentInstance::class)
                        <div class="panel panel-default" id="components-register">
                            <div class="panel-heading">{{ __('Register Component') }}</div>
                            <div class="panel-body">
                                <form method="POST" action="{{ route('hardware.components.register', $asset) }}">
                                    @csrf
                                    @include('components.partials.manual-fields', [
                                        'componentDefinitions' => $componentDefinitions,
                                        'stockLocations' => $stockComponentLocations,
                                        'sourceTypeOptions' => $componentSourceTypeOptions,
                                        'conditionOptions' => $componentConditionOptions,
                                        'notesField' => 'note',
                                        'showSourceType' => true,
                                        'showStorageLocation' => false,
                                        'showInstalledAs' => true,
                                        'selectedDefinitionId' => old('component_definition_id', $selectedDefinitionId),
                                        'selectedInstalledAs' => old('installed_as', $selectedInstalledAs),
                                        'selectedDisplayName' => old('display_name', $selectedDisplayName),
                                    ])
                                    <button type="submit" class="btn btn-primary">{{ __('Register Component') }}</button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>
                <div class="col-md-6">
                    @can('extract', new \App\Models\ComponentInstance())
                        <div class="panel panel-default" id="components-extract">
                            <div class="panel-heading">{{ __('Extract Component') }}</div>
                            <div class="panel-body">
                                <p class="text-muted">{{ __('Use this when a part is removed from the device but is not already tracked as a component instance.') }}</p>
                                <form method="POST" action="{{ route('hardware.components.extract', $asset) }}">
                                    @csrf
                                    @include('components.partials.manual-fields', [
                                        'componentDefinitions' => $componentDefinitions,
                                        'stockLocations' => $stockComponentLocations,
                                        'sourceTypeOptions' => $componentSourceTypeOptions,
                                        'conditionOptions' => $componentConditionOptions,
                                        'notesField' => 'note',
                                        'showSourceType' => false,
                                        'showStorageLocation' => false,
                                        'showInstalledAs' => false,
                                    ])
                                    <button type="submit" class="btn btn-warning">{{ __('Extract To Tray') }}</button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">{{ trans('general.current') }}</div>
                <div class="panel-body">
                    @if ($installedComponents->isEmpty())
                        <p class="text-muted">{{ trans('general.none') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ trans('general.asset_tag') }}</th>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('admin/hardware/form.serial') }}</th>
                                    <th>{{ trans('general.status') }}</th>
                                    <th>{{ trans('general.location') }}</th>
                                    <th>{{ trans('general.updated_at') }}</th>
                                    <th>{{ trans('general.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($installedComponents as $component)
                                    <tr>
                                        <td><a href="{{ route('components.show', $component) }}">{{ $component->component_tag }}</a></td>
                                        <td>{{ $component->display_name }}</td>
                                        <td>{{ $component->serial ?: trans('general.none') }}</td>
                                        <td>{{ str_replace('_', ' ', $component->status) }}</td>
                                        <td>{{ $component->installed_as ?: trans('general.none') }}</td>
                                        <td>{{ Helper::getFormattedDateObject($component->updated_at, 'datetime', false) }}</td>
                                        <td>
                                            <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
                                            @can('move', $component)
                                                <form method="POST" action="{{ route('components.remove_to_tray', $component) }}" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-warning">{{ __('Remove To Tray') }}</button>
                                                </form>
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

            <div class="panel panel-default">
                <div class="panel-heading">{{ __('Expected Components') }}</div>
                <div class="panel-body">
                    @if ($expectedComponents->isEmpty())
                        <p class="text-muted">{{ trans('general.none') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('general.category') }}</th>
                                    <th>{{ trans('general.manufacturer') }}</th>
                                    <th>{{ trans('general.qty') }}</th>
                                    <th>{{ __('Requirement') }}</th>
                                    <th>{{ trans('general.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($expectedComponents as $template)
                                    <tr>
                                        <td>
                                            {{ $template->expected_name }}
                                            @if ($template->slot_name)
                                                <div class="text-muted small">{{ $template->slot_name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $template->componentDefinition?->category?->name ?: trans('general.none') }}</td>
                                        <td>{{ $template->componentDefinition?->manufacturer?->name ?: trans('general.none') }}</td>
                                        <td>{{ $template->expected_qty }}</td>
                                        <td>{{ $template->is_required ? __('Required') : __('Optional') }}</td>
                                        <td>
                                            <a href="{{ route('hardware.show', ['asset' => $asset, 'component_definition_id' => $template->component_definition_id, 'component_slot' => $template->slot_name, 'component_name' => $template->expected_name]) }}#components-install-from-tray" class="btn btn-xs btn-primary">{{ __('Install From Tray') }}</a>
                                            <a href="{{ route('hardware.show', ['asset' => $asset, 'component_definition_id' => $template->component_definition_id, 'component_slot' => $template->slot_name, 'component_name' => $template->expected_name]) }}#components-install-existing" class="btn btn-xs btn-default">{{ __('Install Existing') }}</a>
                                            <a href="{{ route('hardware.show', ['asset' => $asset, 'component_definition_id' => $template->component_definition_id, 'component_slot' => $template->slot_name, 'component_name' => $template->expected_name]) }}#components-register" class="btn btn-xs btn-success">{{ __('Register Component') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">{{ trans('general.history') }}</div>
                <div class="panel-body">
                    @if ($componentHistory->isEmpty())
                        <p class="text-muted">{{ trans('general.none') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ trans('general.date') }}</th>
                                    <th>{{ trans('general.asset_tag') }}</th>
                                    <th>{{ trans('general.action') }}</th>
                                    <th>{{ trans('general.location') }}</th>
                                    <th>{{ trans('general.performed_by') }}</th>
                                    <th>{{ trans('general.notes') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($componentHistory as $event)
                                    @php($component = $event->componentInstance)
                                    <tr>
                                        <td>{{ Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}</td>
                                        <td>
                                            @if ($component && !$component->trashed())
                                                <a href="{{ route('components.show', $component) }}">{{ $component->component_tag }}</a>
                                            @elseif ($component)
                                                {{ $component->component_tag }}
                                                <div class="text-muted small">{{ __('Deleted') }}</div>
                                            @else
                                                {{ trans('general.none') }}
                                            @endif
                                        </td>
                                        <td>{{ str_replace('_', ' ', $event->event_type) }}</td>
                                        <td>{{ $event->toStorageLocation?->name ?: $event->fromStorageLocation?->name ?: trans('general.none') }}</td>
                                        <td>{{ $event->performedBy ? $event->performedBy->present()->fullName() : trans('general.system') }}</td>
                                        <td>{{ $event->note ?: trans('general.none') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
