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

        @php($selectedStatus = old('status_id', $item->status_id))
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

    <div class="col-md-7 col-sm-11 col-md-offset-3" style="margin-top:10px;">
        <textarea
            class="form-control"
            name="status_change_note"
            id="status_change_note"
            rows="2"
            placeholder="{{ __('Add a note for this status change (optional)') }}"
        >{{ old('status_change_note') }}</textarea>
        {!! $errors->first('status_change_note', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>

</div>
