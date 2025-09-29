@extends('layouts/default')

@section('title')
    {{ __('Model Numbers') }}
@parent
@stop

@section('header_right')
    <form method="GET" class="form-inline" role="search" style="display:inline-block;">
        <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="{{ __('Search model numbers…') }}" value="{{ $search }}">
            <span class="input-group-btn">
                <button type="submit" class="btn btn-default">{{ __('Search') }}</button>
            </span>
        </div>
    </form>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="list-unstyled" style="margin:0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Add Model Number') }}</h3>
                </div>
                <div class="box-body">
                    <form action="{{ route('settings.model_numbers.store') }}" method="POST" class="form-inline">
                        @csrf
                        <div class="form-group" style="margin-right:10px; min-width:240px;">
                            <label class="sr-only" for="model_id">{{ __('Model') }}</label>
                            <select name="model_id" id="model_id" class="form-control select2" required style="width:100%;">
                                <option value="">{{ __('Select a model') }}</option>
                                @foreach($models as $modelId => $modelName)
                                    <option value="{{ $modelId }}" {{ old('model_id') == $modelId ? 'selected' : '' }}>{{ $modelName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-right:10px;">
                            <label class="sr-only" for="code">{{ __('Code') }}</label>
                            <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" placeholder="{{ __('Code') }}" required>
                        </div>
                        <div class="form-group" style="margin-right:10px;">
                            <label class="sr-only" for="label">{{ __('Label (optional)') }}</label>
                            <input type="text" name="label" id="label" class="form-control" value="{{ old('label') }}" placeholder="{{ __('Label (optional)') }}">
                        </div>
                        <div class="checkbox" style="margin-right:10px;">
                            <label>
                                <input type="checkbox" name="make_primary" value="1" {{ old('make_primary') ? 'checked' : '' }}>
                                {{ __('Make primary') }}
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Model Numbers') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Model') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Label') }}</th>
                                <th>{{ __('Primary') }}</th>
                                <th>{{ __('Assets') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                       <tbody>
                           @forelse($modelNumbers as $number)
                               <tr>
                                   <td>
                                       <a href="{{ route('models.show', $number->model_id) }}">{{ optional($number->model)->name ?? __('(deleted)') }}</a>
                                   </td>
                                   <td>
                                        <form action="{{ route('settings.model_numbers.update', $number) }}" method="POST" id="update-model-number-{{ $number->id }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <input type="text" name="code" form="update-model-number-{{ $number->id }}" value="{{ old('code', $number->code) }}" class="form-control" style="min-width:160px;">
                                    </td>
                                    <td>
                                        <input type="text" name="label" form="update-model-number-{{ $number->id }}" value="{{ old('label', $number->label) }}" class="form-control" style="min-width:160px;">
                                    </td>
                                    <td>
                                        @if(optional($number->model)->primary_model_number_id === $number->id)
                                            <span class="label label-success">{{ __('Primary') }}</span>
                                        @else
                                            <form action="{{ route('settings.model_numbers.primary', $number) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-xs btn-default">{{ __('Make Primary') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-default">{{ $number->assets_count }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="submit" form="update-model-number-{{ $number->id }}" class="btn btn-xs btn-primary">{{ __('Save') }}</button>
                                            <a href="{{ route('models.spec.edit', ['model' => $number->model_id, 'model_number_id' => $number->id]) }}" class="btn btn-xs btn-default">{{ __('Edit Spec') }}</a>
                                        </div>
                                        @if(optional($number->model)->primary_model_number_id !== $number->id && $number->assets_count === 0)
                                            <form action="{{ route('settings.model_numbers.destroy', $number) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('{{ __('Are you sure?') }}');">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ __('No model numbers found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer clearfix">
                    {{ $modelNumbers->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            $('.select2').select2();
        });
    </script>
@endpush
