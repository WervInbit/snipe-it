@extends('layouts/default')

@section('title')
    {{ __('Add / Install Component') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('hardware.show', $asset) }}#components" class="btn btn-default">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    @php
        $showNewComponent = $errors->has('creation_mode')
            || $errors->has('component_definition_id')
            || $errors->has('display_name')
            || $errors->has('serial')
            || $errors->has('condition_code')
            || $errors->has('note')
            || old('creation_mode');
        $hasConditionWarningInstallOptions = $trayComponents
            ->concat($stockComponents)
            ->contains(fn ($component) => $component->requiresConditionWarningForAttachment());
        $hasLifecycleWarningInstallOptions = $trayComponents
            ->concat($stockComponents)
            ->contains(fn ($component) => $component->requiresLifecycleWarningForAttachment());
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Add / Install Component') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        {{ __('Install an existing tray or storage component into :asset. Tray components are listed first in the picker.', ['asset' => $asset->present()->name()]) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Install') }}</h3>
                </div>
                <div class="box-body">
                    @if($trayComponents->isEmpty() && $stockComponents->isEmpty())
                        <p class="text-muted">{{ __('No tray or storage components are currently available to install.') }}</p>
                    @else
                        <form method="POST" action="{{ route('hardware.components.install', $asset) }}">
                            @csrf
                            <div class="form-group {{ $errors->has('component_id') ? 'has-error' : '' }}">
                                <label for="asset_add_component_id">{{ __('Component') }}</label>
                                <select class="form-control select2" id="asset_add_component_id" name="component_id" style="width: 100%" required>
                                    <option value="">{{ __('Search tray or storage components') }}</option>
                                    @if($trayComponents->isNotEmpty())
                                        <optgroup label="{{ __('My Tray') }}">
                                            @foreach($trayComponents as $trayComponent)
                                                <option value="{{ $trayComponent->id }}" @selected((string) old('component_id') === (string) $trayComponent->id)>
                                                    [{{ __('Tray') }}] {{ $trayComponent->component_tag }} - {{ $trayComponent->display_name }}@if($trayComponent->requiresConditionWarningForAttachment()) ({{ $trayComponent->displayConditionLabel() }})@endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($stockComponents->isNotEmpty())
                                        <optgroup label="{{ __('Storage') }}">
                                            @foreach($stockComponents as $stockComponent)
                                                @php($sourceLabel = $stockComponent->effectiveLifecycleStatus() === \App\Models\ComponentInstance::LIFECYCLE_SOLD_RETURNED ? __('Sold / Returned') : __('Storage'))
                                                <option value="{{ $stockComponent->id }}" @selected((string) old('component_id') === (string) $stockComponent->id)>
                                                    [{{ $sourceLabel }}] {{ $stockComponent->component_tag }} - {{ $stockComponent->display_name }}@if($stockComponent->effectiveConditionStatus() !== \App\Models\ComponentInstance::CONDITION_STATUS_GOOD) ({{ $stockComponent->displayConditionLabel() }})@endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <p class="help-block">{{ __('Search once and install directly. Tray components appear before storage components.') }}</p>
                                {!! $errors->first('component_id', '<span class="help-block">:message</span>') !!}
                            </div>
                            @include('components.partials.condition-warning-confirmation', [
                                'show' => $hasConditionWarningInstallOptions,
                                'message' => __('Some available components have Unknown, Poor, or Broken condition. If you select one of those parts, confirm the warning before installing it.'),
                                'checkboxLabel' => __('I understand the selected component condition and want to install it.'),
                            ])
                            @include('components.partials.lifecycle-warning-confirmation', [
                                'show' => $hasLifecycleWarningInstallOptions,
                                'message' => __('Some available components are marked Sold / Returned. If you select one of those parts, confirm the warning before installing it.'),
                                'checkboxLabel' => __('I understand the selected component lifecycle and want to install it.'),
                            ])
                            <button type="submit" class="btn btn-primary">{{ __('Install') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('New Component') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('Only use this when the component is not already in tray or storage.') }}</p>
                    <button
                        type="button"
                        class="btn btn-default"
                        data-toggle-new-component
                        aria-expanded="{{ $showNewComponent ? 'true' : 'false' }}"
                    >
                        {{ $showNewComponent ? __('Hide New Component Form') : __('Show New Component Form') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row{{ $showNewComponent ? '' : ' hidden' }}" data-new-component-panel>
        <div class="col-md-8">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('New Component') }}</h3>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hardware.components.register', $asset) }}">
                        @csrf
                        <p class="text-muted">
                            {{ __('Create a new tracked component and install it immediately. Source type is manual by default.') }}
                        </p>
                        @include('components.partials.manual-fields', [
                            'componentDefinitions' => $componentDefinitions,
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
                            'message' => __('If the new component condition is Unknown, Poor, or Broken, confirm the warning before installing it.'),
                            'checkboxLabel' => __('I understand the selected component condition and want to install it.'),
                        ])
                        <button type="submit" class="btn btn-success">{{ __('Create And Install') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('moar_scripts')
    @parent
    <script nonce="{{ csrf_token() }}">
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('[data-toggle-new-component]');
            var panel = document.querySelector('[data-new-component-panel]');

            if (!toggle || !panel) {
                return;
            }

            toggle.addEventListener('click', function () {
                panel.classList.toggle('hidden');
                var open = !panel.classList.contains('hidden');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.textContent = open ? '{{ __('Hide New Component Form') }}' : '{{ __('Show New Component Form') }}';
            });
        });
    </script>
@stop
