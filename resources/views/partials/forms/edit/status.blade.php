<!-- Status -->
<div class="form-group {{ $errors->has('status_id') ? ' has-error' : '' }}">
    <label for="status_id" class="col-md-3 control-label">{{ trans('admin/hardware/form.status') }}</label>
    <div class="col-md-7 col-sm-11">
        @php
            $__status_options = $statuslabel_list;
            $user = auth()->user();
            $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
            $isSuper = $user && method_exists($user, 'isSuperUser') ? $user->isSuperUser() : false;
            if (!($isAdmin || $isSuper)) {
                // Hide Ready for Sale for non-admin/supervisor users
                $__status_options = collect($__status_options)
                    ->reject(function($label){ return stripos($label, 'Ready for Sale') !== false; })
                    ->all();
            }
        @endphp

        @if (session('requires_ack_failed_tests'))
            <div class="alert alert-warning" role="alert" style="margin-bottom:10px;">
                {{ __('This asset has not passed all tests. Submit again to confirm Ready for Sale.') }}
            </div>
        @endif

        <x-input.select
            name="status_id"
            id="status_select_id"
            :options="$__status_options"
            :selected="old('status_id', $item->status_id)"
            :required="$required"
            class="status_id"
            style="width:100%;"
            aria-label="status_id"
        />
        {!! $errors->first('status_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
    <div class="col-md-2 col-sm-2 text-left">

        @can('create', \App\Models\Statuslabel::class)
            <a href='{{ route('modal.show', 'statuslabel') }}' data-toggle="modal"  data-target="#createModal" data-select='status_select_id' class="btn btn-sm btn-primary">{{ trans('button.new') }}</a>
        @endcan

        <span class="status_spinner" style="padding-left: 10px; color: green; display:none; width: 30px;"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> </span>

    </div>

    <div class="col-md-7 col-sm-11 col-md-offset-3" id="status_helptext">
        <p id="selected_status_status" style="display:none;"></p>
    </div>

</div>
