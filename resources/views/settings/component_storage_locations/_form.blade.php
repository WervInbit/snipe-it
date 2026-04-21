@csrf
@if(($method ?? 'POST') !== 'POST')
    @method($method)
@endif

<div class="box box-default">
    <div class="box-body">
        <div class="row">
            <div class="col-md-4 form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="name">{{ __('Name') }}</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name) }}" required>
                {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-4 form-group {{ $errors->has('code') ? 'has-error' : '' }}">
                <label for="code">{{ __('Code') }}</label>
                <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $item->code) }}">
                {!! $errors->first('code', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-4 form-group {{ $errors->has('type') ? 'has-error' : '' }}">
                <label for="type">{{ __('Type') }}</label>
                <select class="form-control" id="type" name="type" required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $item->type ?: 'general') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first('type', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group {{ $errors->has('site_location_id') ? 'has-error' : '' }}">
                <label for="site_location_id">{{ __('Site Location') }}</label>
                <select class="form-control" id="site_location_id" name="site_location_id">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($siteLocations as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('site_location_id', $item->site_location_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                {!! $errors->first('site_location_id', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-6 form-group">
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
    </div>

    <div class="box-footer">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('settings.component_storage_locations.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
    </div>
</div>
