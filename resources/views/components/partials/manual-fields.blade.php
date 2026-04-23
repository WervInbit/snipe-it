@php
    $definitionField = $definitionField ?? 'component_definition_id';
    $nameField = $nameField ?? 'display_name';
    $serialField = $serialField ?? 'serial';
    $sourceTypeField = $sourceTypeField ?? 'source_type';
    $conditionField = $conditionField ?? 'condition_code';
    $storageLocationField = $storageLocationField ?? 'storage_location_id';
    $installedAsField = $installedAsField ?? 'installed_as';
    $notesField = $notesField ?? 'notes';
    $showSourceType = $showSourceType ?? true;
    $showCondition = $showCondition ?? true;
    $showStorageLocation = $showStorageLocation ?? false;
    $showInstalledAs = $showInstalledAs ?? false;
    $showNotes = $showNotes ?? true;
    $showCreationModeToggle = $showCreationModeToggle ?? false;
    $creationModeField = $creationModeField ?? 'creation_mode';
    $defaultCreationMode = old(
        $creationModeField,
        (!empty($selectedDisplayName) && empty($selectedDefinitionId)) ? 'custom' : 'definition'
    );
@endphp

<div data-component-mode-scope>
    @if ($showCreationModeToggle)
        <div class="form-group {{ $errors->has($creationModeField) ? 'has-error' : '' }}">
            <label>{{ __('New Component Type') }}</label>
            <div>
                <label class="radio-inline">
                    <input
                        type="radio"
                        name="{{ $creationModeField }}"
                        value="definition"
                        data-component-mode-choice
                        @checked($defaultCreationMode === 'definition')
                    >
                    {{ __('Use Component Definition') }}
                </label>
                <label class="radio-inline">
                    <input
                        type="radio"
                        name="{{ $creationModeField }}"
                        value="custom"
                        data-component-mode-choice
                        @checked($defaultCreationMode === 'custom')
                    >
                    {{ __('Custom Component') }}
                </label>
            </div>
            <p class="help-block">{{ __('Choose a catalog definition or create a custom tracked component without one.') }}</p>
            {!! $errors->first($creationModeField, '<span class="help-block">:message</span>') !!}
        </div>
    @endif

    <div class="row">
        <div
            class="col-md-6 form-group {{ $errors->has($definitionField) ? 'has-error' : '' }}"
            @if($showCreationModeToggle) data-component-mode-panel="definition" @endif
        >
            <label for="{{ $definitionField }}">{{ __('Component Definition') }}</label>
            <select
                class="form-control select2"
                id="{{ $definitionField }}"
                name="{{ $definitionField }}"
                style="width: 100%"
                @if($showCreationModeToggle) data-component-mode-input="definition" @endif
            >
                <option value="">{{ __('Choose a definition') }}</option>
                @foreach ($componentDefinitions as $definition)
                    <option value="{{ $definition->id }}" @selected((string) old($definitionField, $selectedDefinitionId ?? '') === (string) $definition->id)>
                        {{ $definition->name }}
                        @if ($definition->manufacturer)
                            - {{ $definition->manufacturer->name }}
                        @endif
                    </option>
                @endforeach
            </select>
            <p class="help-block">{{ __('Use a definition when the component exists in the catalog.') }}</p>
            {!! $errors->first($definitionField, '<span class="help-block">:message</span>') !!}
        </div>

        <div
            class="col-md-6 form-group {{ $errors->has($nameField) ? 'has-error' : '' }}"
            @if($showCreationModeToggle) data-component-mode-panel="custom" @endif
        >
            <label for="{{ $nameField }}">{{ $showCreationModeToggle ? __('Custom Component Name') : __('Free Name') }}</label>
            <input
                type="text"
                class="form-control"
                id="{{ $nameField }}"
                name="{{ $nameField }}"
                value="{{ old($nameField, $selectedDisplayName ?? '') }}"
                placeholder="{{ $showCreationModeToggle ? __('Required for custom components') : __('Only needed when no definition exists') }}"
                @if($showCreationModeToggle) data-component-mode-input="custom" @endif
            >
            {!! $errors->first($nameField, '<span class="help-block">:message</span>') !!}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 form-group {{ $errors->has($serialField) ? 'has-error' : '' }}">
            <label for="{{ $serialField }}">{{ trans('admin/hardware/form.serial') }}</label>
            <input type="text" class="form-control" id="{{ $serialField }}" name="{{ $serialField }}" value="{{ old($serialField, $selectedSerial ?? '') }}">
            {!! $errors->first($serialField, '<span class="help-block">:message</span>') !!}
        </div>

        @if ($showSourceType)
            <div class="col-md-4 form-group {{ $errors->has($sourceTypeField) ? 'has-error' : '' }}">
                <label for="{{ $sourceTypeField }}">{{ __('Source Type') }}</label>
                <select class="form-control" id="{{ $sourceTypeField }}" name="{{ $sourceTypeField }}">
                    @foreach ($sourceTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old($sourceTypeField, $selectedSourceType ?? \App\Models\ComponentInstance::SOURCE_MANUAL) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first($sourceTypeField, '<span class="help-block">:message</span>') !!}
            </div>
        @endif

        @if ($showCondition)
            <div class="col-md-4 form-group {{ $errors->has($conditionField) ? 'has-error' : '' }}">
                <label for="{{ $conditionField }}">{{ __('Condition') }}</label>
                <select class="form-control" id="{{ $conditionField }}" name="{{ $conditionField }}">
                    @foreach ($conditionOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old($conditionField, $selectedCondition ?? \App\Models\ComponentInstance::CONDITION_UNKNOWN) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first($conditionField, '<span class="help-block">:message</span>') !!}
            </div>
        @endif
    </div>

    @if ($showStorageLocation || $showInstalledAs)
        <div class="row">
            @if ($showStorageLocation)
                <div class="col-md-6 form-group {{ $errors->has($storageLocationField) ? 'has-error' : '' }}">
                    <label for="{{ $storageLocationField }}">{{ __('Stock Location') }}</label>
                    <select class="form-control" id="{{ $storageLocationField }}" name="{{ $storageLocationField }}" required>
                        <option value="">{{ __('Choose a location') }}</option>
                        @foreach ($stockLocations as $location)
                            <option value="{{ $location->id }}" @selected((string) old($storageLocationField, $selectedStorageLocationId ?? '') === (string) $location->id)>
                                {{ $location->name }}
                                @if ($location->siteLocation)
                                    - {{ $location->siteLocation->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    {!! $errors->first($storageLocationField, '<span class="help-block">:message</span>') !!}
                </div>
            @endif

            @if ($showInstalledAs)
                <div class="col-md-6 form-group {{ $errors->has($installedAsField) ? 'has-error' : '' }}">
                    <label for="{{ $installedAsField }}">{{ __('Installed As / Slot') }}</label>
                    <input type="text" class="form-control" id="{{ $installedAsField }}" name="{{ $installedAsField }}" value="{{ old($installedAsField, $selectedInstalledAs ?? '') }}">
                    {!! $errors->first($installedAsField, '<span class="help-block">:message</span>') !!}
                </div>
            @endif
        </div>
    @endif

    @if ($showNotes)
        <div class="form-group {{ $errors->has($notesField) ? 'has-error' : '' }}">
            <label for="{{ $notesField }}">{{ trans('general.notes') }}</label>
            <textarea class="form-control" id="{{ $notesField }}" name="{{ $notesField }}" rows="4">{{ old($notesField, $selectedNotes ?? '') }}</textarea>
            {!! $errors->first($notesField, '<span class="help-block">:message</span>') !!}
        </div>
    @endif
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
                function updateScope(scope) {
                    var selected = scope.querySelector('[data-component-mode-choice]:checked');
                    var mode = selected ? selected.value : 'definition';

                    scope.querySelectorAll('[data-component-mode-panel]').forEach(function (panel) {
                        var active = panel.getAttribute('data-component-mode-panel') === mode;
                        panel.style.display = active ? '' : 'none';

                        panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                            field.disabled = !active;
                        });
                    });
                }

                document.querySelectorAll('[data-component-mode-scope]').forEach(function (scope) {
                    if (!scope.querySelector('[data-component-mode-choice]')) {
                        return;
                    }

                    updateScope(scope);

                    scope.querySelectorAll('[data-component-mode-choice]').forEach(function (choice) {
                        choice.addEventListener('change', function () {
                            updateScope(scope);
                        });
                    });
                });
            })();
        </script>
    @endpush
@endonce
