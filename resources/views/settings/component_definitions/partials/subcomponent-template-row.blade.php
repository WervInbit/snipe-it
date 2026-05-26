@php
    $showErrors = $showErrors ?? true;
    $selectedChildDefinitionId = (string) ($row['child_component_definition_id'] ?? '');
    $isRequired = filter_var($row['is_required'] ?? true, FILTER_VALIDATE_BOOL);
    $idErrorKey = 'expected_subcomponents.' . $index . '.id';
    $childErrorKey = 'expected_subcomponents.' . $index . '.child_component_definition_id';
    $nameErrorKey = 'expected_subcomponents.' . $index . '.expected_name';
    $qtyErrorKey = 'expected_subcomponents.' . $index . '.expected_qty';
    $notesErrorKey = 'expected_subcomponents.' . $index . '.notes';
@endphp

<div class="panel panel-default" data-subcomponent-template-row>
    <div class="panel-body">
        <input type="hidden" name="expected_subcomponents[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 form-group {{ $showErrors && ($errors->has($idErrorKey) || $errors->has($childErrorKey)) ? 'has-error' : '' }}">
                <label>{{ __('Component Definition') }}</label>
                <select class="form-control" name="expected_subcomponents[{{ $index }}][child_component_definition_id]">
                    <option value="">{{ __('Freeform expected subcomponent') }}</option>
                    @foreach ($componentDefinitionOptions as $definition)
                        @continue((int) ($definition->id ?? 0) === (int) ($currentDefinitionId ?? 0))
                        <option value="{{ $definition->id }}" @selected($selectedChildDefinitionId === (string) $definition->id)>
                            {{ $definition->name }}
                            @if ($definition->part_code)
                                ({{ $definition->part_code }})
                            @endif
                        </option>
                    @endforeach
                </select>
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
                <button type="button" class="btn btn-default btn-block btn-sm" style="margin-top:6px;" data-remove-subcomponent-template>{{ __('Remove') }}</button>
            </div>
        </div>

        <div class="form-group {{ $showErrors && $errors->has($notesErrorKey) ? 'has-error' : '' }}">
            <label>{{ __('Notes') }}</label>
            <textarea class="form-control" name="expected_subcomponents[{{ $index }}][notes]" rows="2">{{ $row['notes'] ?? '' }}</textarea>
            @if($showErrors)
                {!! $errors->first($notesErrorKey, '<span class="help-block">:message</span>') !!}
            @endif
        </div>
    </div>
</div>
