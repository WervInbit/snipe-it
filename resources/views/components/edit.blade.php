@extends('layouts/default')

@section('title')
{{ __('Edit Component') }} {{ $component->component_tag }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.show', $component) }}" class="btn btn-default">
    {{ trans('general.cancel') }}
</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Edit Component') }}</h3>
            </div>

            <form method="POST" action="{{ route('components.update', $component) }}">
                @csrf
                @method('PUT')

                <div class="box-body">
                    <div class="alert alert-info">
                        {{ __('Lifecycle, placement, hierarchy, source, and component tag changes use the dedicated actions on the component page.') }}
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group {{ $errors->has('component_definition_id') ? 'has-error' : '' }}">
                            <label for="component_definition_id">{{ __('Component Definition') }}</label>
                            <select
                                class="form-control select2"
                                id="component_definition_id"
                                name="component_definition_id"
                                style="width: 100%"
                            >
                                <option value="">{{ __('Custom component without a catalog definition') }}</option>
                                @foreach ($componentDefinitions as $definition)
                                    <option
                                        value="{{ $definition->id }}"
                                        @selected((string) old('component_definition_id', $component->component_definition_id) === (string) $definition->id)
                                    >
                                        {{ $definition->name }}
                                        @if ($definition->manufacturer)
                                            - {{ $definition->manufacturer->name }}
                                        @endif
                                        @if (!$definition->is_active)
                                            ({{ __('inactive') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            {!! $errors->first('component_definition_id', '<span class="help-block">:message</span>') !!}
                        </div>

                        <div class="col-md-6 form-group {{ $errors->has('display_name') ? 'has-error' : '' }}">
                            <label for="display_name">{{ __('Display Name') }}</label>
                            <input
                                type="text"
                                class="form-control"
                                id="display_name"
                                name="display_name"
                                value="{{ old('display_name', $component->getRawOriginal('display_name')) }}"
                            >
                            <p class="help-block">{{ __('Required when no catalog definition is selected.') }}</p>
                            {!! $errors->first('display_name', '<span class="help-block">:message</span>') !!}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group {{ $errors->has('serial') ? 'has-error' : '' }}">
                            <label for="serial">{{ trans('admin/hardware/form.serial') }}</label>
                            <input
                                type="text"
                                class="form-control"
                                id="serial"
                                name="serial"
                                value="{{ old('serial', $component->serial) }}"
                            >
                            <p class="help-block">{{ __('Serial changes are recorded in the component event history.') }}</p>
                            {!! $errors->first('serial', '<span class="help-block">:message</span>') !!}
                        </div>

                        <div class="col-md-6 form-group {{ $errors->has('condition_code') ? 'has-error' : '' }}">
                            <label for="condition_code">{{ __('Condition') }}</label>
                            <select class="form-control" id="condition_code" name="condition_code">
                                @foreach ($conditionOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('condition_code', $component->condition_code) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="help-block">{{ __('Condition changes are recorded in the component event history.') }}</p>
                            {!! $errors->first('condition_code', '<span class="help-block">:message</span>') !!}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group {{ $errors->has('supplier_id') ? 'has-error' : '' }}">
                            <label for="supplier_id">{{ trans('general.supplier') }}</label>
                            <select class="form-control select2" id="supplier_id" name="supplier_id" style="width: 100%">
                                <option value="">{{ trans('general.none') }}</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $component->supplier_id) === (string) $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            {!! $errors->first('supplier_id', '<span class="help-block">:message</span>') !!}
                        </div>

                        <div class="col-md-4 form-group {{ $errors->has('purchase_cost') ? 'has-error' : '' }}">
                            <label for="purchase_cost">{{ trans('general.purchase_cost') }}</label>
                            <input
                                type="number"
                                min="0"
                                step="0.0001"
                                class="form-control"
                                id="purchase_cost"
                                name="purchase_cost"
                                value="{{ old('purchase_cost', $component->purchase_cost) }}"
                            >
                            {!! $errors->first('purchase_cost', '<span class="help-block">:message</span>') !!}
                        </div>

                        <div class="col-md-4 form-group {{ $errors->has('received_at') ? 'has-error' : '' }}">
                            <label for="received_at">{{ __('Received Date') }}</label>
                            <input
                                type="date"
                                class="form-control"
                                id="received_at"
                                name="received_at"
                                value="{{ old('received_at', $component->received_at?->format('Y-m-d')) }}"
                            >
                            {!! $errors->first('received_at', '<span class="help-block">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                        <label for="notes">{{ trans('general.notes') }}</label>
                        <textarea class="form-control" id="notes" name="notes" rows="5">{{ old('notes', $component->notes) }}</textarea>
                        {!! $errors->first('notes', '<span class="help-block">:message</span>') !!}
                    </div>
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
                    <a href="{{ route('components.show', $component) }}" class="btn btn-default">
                        {{ trans('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
