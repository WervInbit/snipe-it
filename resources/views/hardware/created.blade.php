@extends('layouts/default')

@section('title')
    {{ trans('general.asset_creation_complete') }}
@parent
@stop

@inject('qrLabels', 'App\\Services\\QrLabelService')

@section('content')
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box box-success" data-testid="asset-creation-result">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.asset_creation_complete') }}</h2>
                </div>
                <div class="box-body">
                    <p>
                        {{ $createdAssets->count() === 1
                            ? trans('general.asset_creation_summary')
                            : trans('general.assets_creation_summary', ['count' => $createdAssets->count()]) }}
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/hardware/form.tag') }}</th>
                                    <th>{{ trans('admin/hardware/form.serial') }}</th>
                                    <th>{{ trans('general.model') }}</th>
                                    <th class="text-right">{{ trans('general.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($createdAssets as $createdAsset)
                                    @php
                                        $template = $snipeSettings->qr_label_template ?? config('qr_templates.default');
                                        $qrPdf = $qrLabels->url($createdAsset, 'pdf', $template);
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('hardware.show', $createdAsset) }}">{{ $createdAsset->asset_tag }}</a>
                                        </td>
                                        <td>{{ $createdAsset->serial ?: '-' }}</td>
                                        <td>{{ $createdAsset->model?->name ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ $qrPdf }}" target="_blank" rel="noopener" class="btn btn-default btn-sm">
                                                <x-icon type="print" /> {{ trans('general.print_qr') }}
                                            </a>
                                            <a href="{{ route('hardware.show', $createdAsset) }}" class="btn btn-primary btn-sm">
                                                {{ trans('general.open_asset') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (!empty($failures))
                        <div class="alert alert-warning">
                            <strong>{{ trans('general.creation_failures') }}</strong>
                            <ul>
                                @foreach ($failures as $failure)
                                    <li>{{ $failure }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="box-footer">
                    @can('create', \App\Models\Asset::class)
                        <a href="{{ route('hardware.create') }}" class="btn btn-default">
                            <x-icon type="plus" /> {{ trans('general.create_another_asset') }}
                        </a>
                    @endcan
                    <a href="{{ route('hardware.show', $anchorAsset) }}" class="btn btn-primary pull-right">
                        {{ trans('general.open_asset') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop
