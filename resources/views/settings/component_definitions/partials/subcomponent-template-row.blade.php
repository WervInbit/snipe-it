@php
    $showErrors = $showErrors ?? true;
    $selectedChildDefinitionId = (string) ($row['child_component_definition_id'] ?? '');
    $isRequired = filter_var($row['is_required'] ?? true, FILTER_VALIDATE_BOOL);
    $idErrorKey = 'expected_subcomponents.' . $index . '.id';
    $childErrorKey = 'expected_subcomponents.' . $index . '.child_component_definition_id';
    $nameErrorKey = 'expected_subcomponents.' . $index . '.expected_name';
    $qtyErrorKey = 'expected_subcomponents.' . $index . '.expected_qty';
    $notesErrorKey = 'expected_subcomponents.' . $index . '.notes';
    $selectedChildDefinition = $componentDefinitionOptions
        ->first(fn ($definition) => (string) ($definition->id ?? '') === $selectedChildDefinitionId);
    $pickerValue = trim((string) ($row['child_component_definition_search'] ?? ''));

    if ($pickerValue === '' && $selectedChildDefinition) {
        $pickerValue = $selectedChildDefinition->name;

        if ($selectedChildDefinition->part_code) {
            $pickerValue .= ' (' . $selectedChildDefinition->part_code . ')';
        }
    }

    $notesValue = (string) ($row['notes'] ?? '');
    $hasNotes = trim($notesValue) !== '';
    $notesHasError = $showErrors && $errors->has($notesErrorKey);
    $notesExpanded = $hasNotes || $notesHasError;
    $notesCollapseId = 'expected-subcomponent-notes-' . $index;
    $canRemoveSubcomponent = ($canManageDefinitionLifecycle ?? false) || empty($row['id']);
@endphp

<div class="panel panel-default" data-subcomponent-template-row>
    <div class="panel-body">
        <input type="hidden" name="expected_subcomponents[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 form-group {{ $showErrors && ($errors->has($idErrorKey) || $errors->has($childErrorKey)) ? 'has-error' : '' }}">
                <label>{{ __('Component Definition') }}</label>
                <input type="hidden"
                       name="expected_subcomponents[{{ $index }}][child_component_definition_id]"
                       value="{{ $selectedChildDefinitionId }}"
                       data-subcomponent-definition-id>
                <input type="search"
                       class="form-control"
                       name="expected_subcomponents[{{ $index }}][child_component_definition_search]"
                       value="{{ $pickerValue }}"
                       placeholder="{{ __('Search component definitions...') }}"
                       autocomplete="off"
                       data-subcomponent-definition-search>
                <div class="list-group component-definition-picker-results" data-subcomponent-search-results hidden></div>
                <p class="help-block text-muted">
                    {{ __('Start typing a component definition name, part code, category, or manufacturer, then pick a match.') }}
                </p>
                @if($showErrors)
                    {!! $errors->first($idErrorKey, '<span class="help-block">:message</span>') !!}
                    {!! $errors->first($childErrorKey, '<span class="help-block">:message</span>') !!}
                @endif
            </div>

            <div class="col-md-3 form-group {{ $showErrors && $errors->has($nameErrorKey) ? 'has-error' : '' }}">
                <label>{{ __('Expected Name') }}</label>
                <input type="text"
                       class="form-control"
                       name="expected_subcomponents[{{ $index }}][expected_name]"
                       value="{{ $row['expected_name'] ?? '' }}"
                       placeholder="{{ __('Uses selected definition name if blank') }}">
                @if($showErrors)
                    {!! $errors->first($nameErrorKey, '<span class="help-block">:message</span>') !!}
                @endif
            </div>

            <div class="col-md-2 form-group {{ $showErrors && $errors->has($qtyErrorKey) ? 'has-error' : '' }}">
                <label>{{ __('Quantity') }}</label>
                <input type="number"
                       class="form-control"
                       name="expected_subcomponents[{{ $index }}][expected_qty]"
                       value="{{ $row['expected_qty'] ?? '' }}"
                       min="1"
                       step="1">
                @if($showErrors)
                    {!! $errors->first($qtyErrorKey, '<span class="help-block">:message</span>') !!}
                @endif
            </div>

            <div class="col-md-1 form-group">
                <label>{{ __('Required') }}</label>
                <div class="checkbox" style="margin-top:8px;">
                    <label>
                        <input type="hidden" name="expected_subcomponents[{{ $index }}][is_required]" value="0">
                        <input type="checkbox" name="expected_subcomponents[{{ $index }}][is_required]" value="1" @checked($isRequired)>
                    </label>
                </div>
            </div>

            <div class="col-md-2 form-group">
                <label>&nbsp;</label>
                <div class="btn-group btn-group-sm btn-group-justified" role="group">
                    <a href="#" class="btn btn-default" data-move-subcomponent-template="up">{{ __('Up') }}</a>
                    <a href="#" class="btn btn-default" data-move-subcomponent-template="down">{{ __('Down') }}</a>
                </div>
                @if($canRemoveSubcomponent)
                    <button type="button" class="btn btn-default btn-block btn-sm" style="margin-top:6px;" data-remove-subcomponent-template>{{ __('Remove') }}</button>
                @else
                    <span class="text-muted small">{{ __('Admin only') }}</span>
                @endif
                <button type="button"
                        class="btn btn-default btn-block btn-sm"
                        style="margin-top:6px;"
                        data-toggle="collapse"
                        data-target="#{{ $notesCollapseId }}"
                        aria-expanded="{{ $notesExpanded ? 'true' : 'false' }}"
                        aria-controls="{{ $notesCollapseId }}"
                        data-subcomponent-notes-toggle>
                    {{ $hasNotes ? __('Notes added') : __('Notes') }}
                    <i class="fas fa-chevron-down pull-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div id="{{ $notesCollapseId }}"
             class="collapse component-definition-subcomponent-notes{{ $notesExpanded ? ' in' : '' }}"
             data-subcomponent-notes-panel>
            <div class="form-group {{ $notesHasError ? 'has-error' : '' }}">
                <label>{{ __('Notes') }}</label>
                <textarea class="form-control" name="expected_subcomponents[{{ $index }}][notes]" rows="2">{{ $notesValue }}</textarea>
                @if($showErrors)
                    {!! $errors->first($notesErrorKey, '<span class="help-block">:message</span>') !!}
                @endif
            </div>
        </div>
    </div>
</div>
