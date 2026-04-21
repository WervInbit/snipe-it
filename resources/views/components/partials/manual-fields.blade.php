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
    $showStorageLocation = $showStorageLocation ?? false;
    $showInstalledAs = $showInstalledAs ?? false;
@endphp

<div class="row">
    <div class="col-md-6 form-group {{ $errors->has($definitionField) ? 'has-error' : '' }}">
        <label for="{{ $definitionField }}">{{ __('Component Definition') }}</label>
        <select class="form-control select2" id="{{ $definitionField }}" name="{{ $definitionField }}" style="width: 100%">
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
        {!! $errors->first($definitionField, '<span class="help-block">:message</span>') !!}
    </div>

    <div class="col-md-6 form-group {{ $errors->has($nameField) ? 'has-error' : '' }}">
        <label for="{{ $nameField }}">{{ __('Free Name') }}</label>
        <input
            type="text"
            class="form-control"
            id="{{ $nameField }}"
            name="{{ $nameField }}"
            value="{{ old($nameField, $selectedDisplayName ?? '') }}"
            placeholder="{{ __('Only needed when no definition exists') }}"
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

    <div class="col-md-4 form-group {{ $errors->has($conditionField) ? 'has-error' : '' }}">
        <label for="{{ $conditionField }}">{{ __('Condition') }}</label>
        <select class="form-control" id="{{ $conditionField }}" name="{{ $conditionField }}">
            @foreach ($conditionOptions as $value => $label)
                <option value="{{ $value }}" @selected(old($conditionField, $selectedCondition ?? \App\Models\ComponentInstance::CONDITION_UNKNOWN) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        {!! $errors->first($conditionField, '<span class="help-block">:message</span>') !!}
    </div>
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

<div class="form-group {{ $errors->has($notesField) ? 'has-error' : '' }}">
    <label for="{{ $notesField }}">{{ trans('general.notes') }}</label>
    <textarea class="form-control" id="{{ $notesField }}" name="{{ $notesField }}" rows="4">{{ old($notesField, $selectedNotes ?? '') }}</textarea>
    {!! $errors->first($notesField, '<span class="help-block">:message</span>') !!}
</div>
