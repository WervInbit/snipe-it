@if($recentAssets->isNotEmpty())
    <div class="row" data-testid="dashboard-recent-assets">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.recent_devices') }}</h2>
                </div>
                <div class="box-body no-padding table-responsive">
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{{ trans('general.asset_tag') }}</th>
                                <th>{{ trans('general.model') }}</th>
                                <th>{{ trans('general.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAssets as $recentAsset)
                                <tr>
                                    <td>
                                        <a href="{{ route('hardware.show', $recentAsset) }}">{{ $recentAsset->asset_tag }}</a>
                                    </td>
                                    <td>{{ $recentAsset->model?->name ?? trans('general.unknown') }}</td>
                                    <td>{{ $recentAsset->assetstatus?->name ?? trans('general.unknown') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
