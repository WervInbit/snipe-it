@extends('layouts/default')

@section('title')
    {{ trans('admin/settings/general.test_settings_title') }}
@parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('admin/testtypes/general.create_title') }}</h3>
                </div>
                <form method="POST" action="{{ route('settings.testtypes.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group @error('name') has-error @enderror">
                            <label for="create-name">{{ trans('admin/testtypes/general.name') }}</label>
                            <input type="text" class="form-control" id="create-name" name="name" value="{{ old('name') }}">
                            @error('name')
                                <span class="help-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group @error('slug') has-error @enderror">
                            <label for="create-slug">{{ trans('admin/testtypes/general.slug') }}</label>
                            <input type="text" class="form-control" id="create-slug" name="slug" value="{{ old('slug') }}">
                            <span class="help-block">{{ trans('admin/testtypes/general.slug_hint') }}</span>
                            @error('slug')
                                <span class="help-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group @error('attribute_definition_id') has-error @enderror">
                            <label for="create-attribute">{{ trans('admin/testtypes/general.attribute') }}</label>
                            <select name="attribute_definition_id" id="create-attribute" class="form-control js-test-attribute" data-placeholder="{{ trans('admin/testtypes/general.attribute_placeholder') }}">
                                <option value="">{{ trans('general.none') }}</option>
                                @foreach($attributeDefinitions as $attribute)
                                    <option value="{{ $attribute->id }}"{{ (string)$attribute->id === old('attribute_definition_id') ? ' selected' : '' }}>
                                        {{ $attribute->label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('attribute_definition_id')
                                <span class="help-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group @error('instructions') has-error @enderror">
                            <label for="create-instructions">{{ trans('admin/testtypes/general.instructions') }}</label>
                            <textarea name="instructions" id="create-instructions" class="form-control" rows="3">{{ old('instructions') }}</textarea>
                            @error('instructions')
                                <span class="help-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group @error('tooltip') has-error @enderror">
                            <label for="create-tooltip">{{ trans('admin/testtypes/general.tooltip') }}</label>
                            <input type="text" class="form-control" id="create-tooltip" name="tooltip" value="{{ old('tooltip') }}">
                            @error('tooltip')
                                <span class="help-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" type="submit">{{ trans('button.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('admin/testtypes/general.existing_title') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/testtypes/general.name') }}</th>
                                <th>{{ trans('admin/testtypes/general.slug') }}</th>
                                <th>{{ trans('admin/testtypes/general.attribute') }}</th>
                                <th>{{ trans('admin/testtypes/general.instructions') }}</th>
                                <th>{{ trans('admin/testtypes/general.tooltip') }}</th>
                                <th class="text-right">{{ trans('button.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($testTypes as $type)
                                <tr>
                                    <td colspan="6">
                                        <form method="POST" action="{{ route('settings.testtypes.update', $type) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <input type="text" name="name" value="{{ $type->name }}" class="form-control">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" name="slug" value="{{ $type->slug }}" class="form-control">
                                                </div>
                                                <div class="col-md-2">
                                                    <select name="attribute_definition_id" class="form-control js-test-attribute" data-placeholder="{{ trans('admin/testtypes/general.attribute_placeholder') }}">
                                                        <option value="">{{ trans('general.none') }}</option>
                                                        @foreach($attributeDefinitions as $attribute)
                                                            <option value="{{ $attribute->id }}"{{ (string)($type->attribute_definition_id) === (string)$attribute->id ? ' selected' : '' }}>
                                                                {{ $attribute->label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <textarea name="instructions" class="form-control" rows="2">{{ $type->instructions }}</textarea>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" name="tooltip" value="{{ $type->tooltip }}" class="form-control">
                                                </div>
                                                <div class="col-md-1 text-right">
                                                    <button class="btn btn-primary btn-sm" type="submit">{{ trans('button.save') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('settings.testtypes.destroy', $type) }}" class="inline-block" onsubmit="return confirm('{{ trans('admin/testtypes/general.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-xs" type="submit">{{ trans('button.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">{{ trans('general.no_results') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@push('scripts')
<script>
    $(function () {
        $('.js-test-attribute').select2({
            allowClear: true,
            placeholder: function(){
                return $(this).data('placeholder');
            }
        });
    });
</script>
@endpush
