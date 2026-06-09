<!-- Status -->
<div class="form-group {{ $errors->has('status_id') ? ' has-error' : '' }}">
    <label for="status_id" class="col-md-3 control-label">{{ trans('admin/hardware/form.status') }}</label>
    <div class="col-md-7 col-sm-12">
        @php
            $__status_options = $statuslabel_list;
            $selectedStatus = old('status_id', $item->status_id);
            $canMoveSaleLifecycle = Gate::allows('assets.sale_transition');
            $statusLabelsById = \App\Models\Statuslabel::query()
                ->whereIn('id', array_keys($__status_options))
                ->get()
                ->keyBy('id');
            $__status_options = collect($__status_options)
                ->filter(function ($label, $id) use ($selectedStatus, $statusLabelsById, $canMoveSaleLifecycle) {
                    if ((string) $selectedStatus === (string) $id) {
                        return true;
                    }

                    $statusLabel = $statusLabelsById->get((int) $id);
                    $isPreSale = \App\Models\Asset::isPreSaleStatus($statusLabel);
                    $isSold = \App\Models\Asset::isSoldStatus($statusLabel);

                    if (($isPreSale || $isSold) && !$canMoveSaleLifecycle) {
                        return false;
                    }

                    return true;
                })
                ->all();
        @endphp

        @if (session('requires_ack_failed_tests'))
            <div class="alert alert-warning" role="alert" style="margin-bottom:10px;">
                <p class="mb-2">{{ trans('tests.status_change_prompt') }}</p>
                @if (session('test_issue_details'))
                    <ul class="mb-0">
                        @foreach ((array) session('test_issue_details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if (session('requires_ack_component_issues'))
            <div class="alert alert-warning" role="alert" style="margin-bottom:10px;">
                <p class="mb-2">{{ __('Attached damaged or needs-attention components remain on this asset. Submit again to confirm the selling-state change.') }}</p>
                @if (session('component_issue_details'))
                    <ul class="mb-0">
                        @foreach ((array) session('component_issue_details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <select
            name="status_id"
            id="status_select_id"
            class="form-control status_id"
            style="width:100%;"
            aria-label="status_id"
            {{ $required ? 'required' : '' }}
        >
            @foreach($__status_options as $key => $value)
                <option value="{{ $key }}" {{ (string) $selectedStatus === (string) $key ? 'selected' : '' }}>
                    {{ $value }}
                </option>
            @endforeach
        </select>
        {!! $errors->first('status_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}

        <div id="status_helptext" style="margin-top:10px;">
            <p id="selected_status_status" style="display:none;"></p>
        </div>
    </div>
    <div class="col-md-2 col-sm-12 text-left">
        @can('create', \App\Models\Statuslabel::class)
            <a href='{{ route('modal.show', 'statuslabel') }}' data-toggle="modal"  data-target="#createModal" data-select='status_select_id' class="btn btn-sm btn-primary">{{ trans('button.new') }}</a>
        @endcan

        <span class="status_spinner" style="padding-left: 10px; color: green; display:none; width: 30px;"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> </span>
    </div>

</div>
