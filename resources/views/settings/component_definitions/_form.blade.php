@csrf
@if(($method ?? 'POST') !== 'POST')
    @method($method)
@endif

<div class="box box-default">
    <div class="box-body">
        <div class="row">
            <div class="col-md-6 form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="name">{{ __('Name') }}</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name) }}" required>
                {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-3 form-group {{ $errors->has('part_code') ? 'has-error' : '' }}">
                <label for="part_code">{{ __('Part Code') }}</label>
                <input type="text" class="form-control" id="part_code" name="part_code" value="{{ old('part_code', $item->part_code) }}">
                {!! $errors->first('part_code', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-3 form-group {{ $errors->has('model_number') ? 'has-error' : '' }}">
                <label for="model_number">{{ __('Model Number') }}</label>
                <input type="text" class="form-control" id="model_number" name="model_number" value="{{ old('model_number', $item->model_number) }}">
                {!! $errors->first('model_number', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group {{ $errors->has('category_id') ? 'has-error' : '' }}">
                <label for="category_id">{{ __('Category') }}</label>
                <select class="form-control" id="category_id" name="category_id">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('category_id', $item->category_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                {!! $errors->first('category_id', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-4 form-group {{ $errors->has('manufacturer_id') ? 'has-error' : '' }}">
                <label for="manufacturer_id">{{ __('Manufacturer') }}</label>
                <select class="form-control" id="manufacturer_id" name="manufacturer_id">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($manufacturers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('manufacturer_id', $item->manufacturer_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                {!! $errors->first('manufacturer_id', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label>{{ __('Status') }}</label>
                <div class="checkbox" style="margin-top:8px;">
                    <label>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $item->exists ? $item->is_active : true))>
                        {{ __('Active') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group {{ $errors->has('spec_summary') ? 'has-error' : '' }}">
            <label for="spec_summary">{{ __('Specification Summary') }}</label>
            <textarea class="form-control" id="spec_summary" name="spec_summary" rows="5">{{ old('spec_summary', $item->spec_summary) }}</textarea>
            {!! $errors->first('spec_summary', '<span class="help-block">:message</span>') !!}
        </div>
    </div>

    <div class="box-footer">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('settings.component_definitions.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
    </div>
</div>
