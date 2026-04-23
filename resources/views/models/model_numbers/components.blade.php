@extends('layouts/default')

@section('title')
    {{ __('Expected Components') }}: {{ $modelNumber->code }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('models.numbers.edit', [$model, $modelNumber]) }}" class="btn btn-default">
        {{ __('Edit Model Number') }}
    </a>
    <a href="{{ route('models.show', $model) }}" class="btn btn-default">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Add Expected Component') }}</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">
                    {{ __('Define the default tracked parts refurbishers should expect for this model-number preset.') }}
                </p>

                <form method="POST" action="{{ route('models.numbers.components.store', [$model, $modelNumber]) }}">
                    @csrf

                    <div class="form-group{{ $errors->has('component_definition_id') ? ' has-error' : '' }}">
                        <label for="component_definition_id">{{ __('Catalog Definition') }}</label>
                        <select name="component_definition_id" id="component_definition_id" class="form-control">
                            <option value="">{{ __('Freeform expected component') }}</option>
                            @foreach($componentDefinitions as $definition)
                                <option value="{{ $definition->id }}" @selected((string) old('component_definition_id') === (string) $definition->id)>
                                    {{ $definition->name }}
                                    @if($definition->manufacturer)
                                        - {{ $definition->manufacturer->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        {!! $errors->first('component_definition_id', '<span class="help-block">:message</span>') !!}
                    </div>

                    <div class="form-group{{ $errors->has('expected_name') ? ' has-error' : '' }}">
                        <label for="expected_name">{{ __('Expected Name') }}</label>
                        <input type="text" class="form-control" id="expected_name" name="expected_name" value="{{ old('expected_name') }}" required>
                        {!! $errors->first('expected_name', '<span class="help-block">:message</span>') !!}
                    </div>

                    <div class="form-group{{ $errors->has('slot_name') ? ' has-error' : '' }}">
                        <label for="slot_name">{{ __('Slot Name') }}</label>
                        <input type="text" class="form-control" id="slot_name" name="slot_name" value="{{ old('slot_name') }}">
                        {!! $errors->first('slot_name', '<span class="help-block">:message</span>') !!}
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group{{ $errors->has('expected_qty') ? ' has-error' : '' }}">
                                <label for="expected_qty">{{ trans('general.qty') }}</label>
                                <input type="number" min="1" class="form-control" id="expected_qty" name="expected_qty" value="{{ old('expected_qty', 1) }}" required>
                                {!! $errors->first('expected_qty', '<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group{{ $errors->has('sort_order') ? ' has-error' : '' }}">
                                <label for="sort_order">{{ __('Sort Order') }}</label>
                                <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $modelNumber->componentTemplates->count()) }}">
                                {!! $errors->first('sort_order', '<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_required" value="1" {{ old('is_required', true) ? 'checked' : '' }}>
                            {{ __('Required by default') }}
                        </label>
                    </div>

                    <div class="form-group{{ $errors->has('notes') ? ' has-error' : '' }}">
                        <label for="notes">{{ trans('general.notes') }}</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        {!! $errors->first('notes', '<span class="help-block">:message</span>') !!}
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('Add Expected Component') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Expected Components') }}</h3>
            </div>
            <div class="box-body">
                @if($modelNumber->componentTemplates->isEmpty())
                    <p class="text-muted">{{ __('No expected components have been configured for this preset yet.') }}</p>
                @else
                    @foreach($modelNumber->componentTemplates as $template)
                        <div class="panel panel-default" id="template-{{ $template->id }}">
                            <div class="panel-heading clearfix">
                                <div class="pull-left">
                                    <strong>{{ $template->expected_name }}</strong>
                                    <span class="text-muted">#{{ $template->sort_order + 1 }}</span>
                                </div>
                                <div class="btn-group btn-group-xs pull-right">
                                    <form method="POST" action="{{ route('models.numbers.components.reorder', [$model, $modelNumber]) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="template_id" value="{{ $template->id }}">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-default" {{ $loop->first ? 'disabled' : '' }}>{{ __('Move Up') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('models.numbers.components.reorder', [$model, $modelNumber]) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="template_id" value="{{ $template->id }}">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-default" {{ $loop->last ? 'disabled' : '' }}>{{ __('Move Down') }}</button>
                                    </form>
                                </div>
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="{{ route('models.numbers.components.update', [$model, $modelNumber, $template]) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <label>{{ __('Catalog Definition') }}</label>
                                            <select name="component_definition_id" class="form-control">
                                                <option value="">{{ __('Freeform expected component') }}</option>
                                                @foreach($componentDefinitions as $definition)
                                                    <option value="{{ $definition->id }}" {{ (int) $template->component_definition_id === (int) $definition->id ? 'selected' : '' }}>
                                                        {{ $definition->name }}
                                                        @if($definition->manufacturer)
                                                            - {{ $definition->manufacturer->name }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>{{ __('Expected Name') }}</label>
                                            <input type="text" class="form-control" name="expected_name" value="{{ $template->expected_name }}" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-4 form-group">
                                            <label>{{ __('Slot Name') }}</label>
                                            <input type="text" class="form-control" name="slot_name" value="{{ $template->slot_name }}">
                                        </div>
                                        <div class="col-sm-2 form-group">
                                            <label>{{ trans('general.qty') }}</label>
                                            <input type="number" min="1" class="form-control" name="expected_qty" value="{{ $template->expected_qty }}" required>
                                        </div>
                                        <div class="col-sm-2 form-group">
                                            <label>{{ __('Sort Order') }}</label>
                                            <input type="number" min="0" class="form-control" name="sort_order" value="{{ $template->sort_order }}">
                                        </div>
                                        <div class="col-sm-4 form-group">
                                            <label>{{ __('Requirement') }}</label>
                                            <div class="checkbox" style="margin-top: 8px;">
                                                <label>
                                                    <input type="checkbox" name="is_required" value="1" {{ $template->is_required ? 'checked' : '' }}>
                                                    {{ __('Required by default') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>{{ trans('general.notes') }}</label>
                                        <textarea class="form-control" name="notes" rows="2">{{ $template->notes }}</textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm">{{ trans('general.save') }}</button>
                                </form>

                                <form method="POST" action="{{ route('models.numbers.components.destroy', [$model, $modelNumber, $template]) }}" style="margin-top: 10px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ trans('general.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@stop
