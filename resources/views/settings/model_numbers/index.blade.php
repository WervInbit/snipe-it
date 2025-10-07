@extends('layouts/default')

@section('title')
    {{ __('Model Numbers') }}
@parent
@stop

@section('header_right')
    <a href="{{ route('settings.model_numbers.create') }}" class="btn btn-primary" style="margin-right: 10px;">{{ __('Create New') }}</a>
    <form method="GET" class="form-inline" role="search" style="display:inline-block;">
        <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="{{ __('Search model numbers...') }}" value="{{ $search }}">
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
                    <h3 class="box-title">{{ __('Model Numbers') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Model') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Label') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Assets') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($modelNumbers as $number)
                                @php($model = $number->model)
                                @php($isPrimary = optional($model)->primary_model_number_id === $number->id)
                                <tr>
                                    <td>
                                        <a href="{{ route('models.show', $number->model_id) }}">{{ optional($model)->name ?? __('(deleted)') }}</a>
                                    </td>
                                    <td class="monospace">{{ $number->code }}</td>
                                    <td>{{ $number->label ?: __('—') }}</td>
                                    <td>
                                        @if($isPrimary)
                                            <span class="label label-success">{{ __('Primary') }}</span>
                                        @elseif($number->isDeprecated())
                                            <span class="label label-default">{{ __('Deprecated') }}</span>
                                        @else
                                            <span class="label label-info">{{ __('Active') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-default">{{ $number->assets_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('settings.model_numbers.edit', $number) }}" class="btn btn-default">{{ __('Edit') }}</a>
                        <a href="{{ route('models.numbers.spec.edit', ['model' => $number->model_id, 'modelNumber' => $number->id]) }}" class="btn btn-default">{{ __('Edit Spec') }}</a>
                                        </div>
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

