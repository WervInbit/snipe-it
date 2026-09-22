@extends('layouts/edit-form', [
    'createText' => trans('admin/statuslabels/table.create') ,
    'updateText' => trans('admin/statuslabels/table.update'),
    'helpTitle' => trans('admin/statuslabels/table.about'),
    'helpText' => trans('admin/statuslabels/table.info', [
        'fork_documentation_url' => route('help.api-compatibility'),
    ]),
    'formAction' => (isset($item->id)) ? route('statuslabels.update', ['statuslabel' => $item->id]) : route('statuslabels.store'),
])

{{-- Page content --}}
@section('content')
<style>
    .input-group-addon {
        width: 30px;
    }
</style>

@parent
@stop

@section('inputFields')

@include ('partials.forms.edit.name', ['translated_name' => trans('general.name')])

<!-- Label type -->
<div class="form-group{{ $errors->has('statuslabel_types') ? ' has-error' : '' }}">
    <label for="statuslabel_types" class="col-md-3 control-label">
        {{ trans('admin/statuslabels/table.status_type') }}
    </label>
    <div class="col-md-7 required">
        <x-input.select
            name="statuslabel_types"
            :options="$statuslabel_types"
            :selected="$item->getStatuslabelType()"
            style="width: 100%; min-width:400px"
            aria-label="statuslabel_types"
        />
        {!! $errors->first('statuslabel_types', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<!-- Stable lifecycle semantics -->
<div class="form-group{{ $errors->has('lifecycle_stage') ? ' has-error' : '' }}">
    <label for="lifecycle_stage" class="col-md-3 control-label">
        {{ trans('admin/statuslabels/table.lifecycle_stage') }}
    </label>
    <div class="col-md-7">
        <x-input.select
            name="lifecycle_stage"
            :options="$lifecycle_stages"
            :selected="old('lifecycle_stage', $item->lifecycle_stage)"
            style="width: 100%; min-width:400px"
            aria-label="lifecycle_stage"
        />
        <p class="help-block">{{ trans('admin/statuslabels/table.lifecycle_stage_help') }}</p>
        {!! $errors->first('lifecycle_stage', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<!-- Chart color -->
<div class="form-group{{ $errors->has('color') ? ' has-error' : '' }}">
    <label for="color" class="col-md-3 control-label">{{ trans('admin/statuslabels/table.color') }}</label>
    <div class="col-md-9">
        <div class="input-group color">
            <input class="form-control col-md-10" maxlength="20" name="color" type="text" id="color" value="{{ old('color', $item->color) }}">
            <div class="input-group-addon"><i></i></div>
        </div><!-- /.input group -->
        {!! $errors->first('color', '<span class="alert-msg" aria-hidden="true">:message</span>') !!}
    </div>
</div>

@include ('partials.forms.edit.notes')

<!-- Show in Nav -->
<div class="form-group{{ $errors->has('notes') ? ' has-error' : '' }}">
    <div class="col-md-9 col-md-offset-3">
        <label class="form-control">
            <input type="checkbox" value="1" name="show_in_nav" id="show_in_nav" {{ old('show_in_nav', $item->show_in_nav) == '1' ? ' checked="checked"' : '' }}> {{ trans('admin/statuslabels/table.show_in_nav') }}
        </label>
    </div>
</div>

<!-- Set as Default -->
<div class="form-group{{ $errors->has('default_label') ? ' has-error' : '' }}">

    <div class="col-md-9 col-md-offset-3">
        <label class="form-control">
            <input type="checkbox" value="1" name="default_label" id="default_label" {{ old('default_label', $item->default_label) == '1' ? ' checked="checked"' : '' }}>
             {{ trans('admin/statuslabels/table.default_label') }}
        </label>
        <p class="help-block"> {{ trans('admin/statuslabels/table.default_label_help') }}</p>
    </div>
</div>

<!-- Require transition note -->
<div class="form-group">
    <div class="col-md-9 col-md-offset-3">
        <label class="form-control">
            <input type="checkbox" value="1" name="requires_note" id="requires_note" {{ old('requires_note', $item->requires_note) ? ' checked="checked"' : '' }}>
            {{ trans('admin/statuslabels/table.requires_note') }}
        </label>
        <p class="help-block">{{ trans('admin/statuslabels/table.requires_note_help') }}</p>
    </div>
</div>

<div class="form-group">
    <div class="col-md-9 col-md-offset-3">
        <h3>{{ trans('admin/statuslabels/table.access_title') }}</h3>
        <p class="help-block">{{ trans('admin/statuslabels/table.access_help') }}</p>
        @php
            $accessOptions = [
                0 => trans('admin/statuslabels/table.access_inherit'),
                1 => trans('admin/statuslabels/table.access_allow'),
                -1 => trans('admin/statuslabels/table.access_deny'),
            ];
            $groupRules = $item->exists
                ? $item->accessRules->where('subject_type', \App\Models\StatusLabelAccessRule::SUBJECT_GROUP)->keyBy('subject_id')
                : collect();
        @endphp
        <div class="table-responsive">
            <table class="table table-condensed table-striped">
                <thead>
                    <tr>
                        <th>{{ trans('general.group') }}</th>
                        <th>{{ trans('admin/statuslabels/table.access_view') }}</th>
                        <th>{{ trans('admin/statuslabels/table.access_select') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accessGroups as $group)
                        @php($groupRule = $groupRules->get($group->id))
                        <tr>
                            <td>{{ $group->name }}</td>
                            <td>
                                <select class="form-control" name="status_access_groups[{{ $group->id }}][view]">
                                    @foreach($accessOptions as $value => $label)
                                        <option value="{{ $value }}" {{ (int) old("status_access_groups.{$group->id}.view", $groupRule?->view_value ?? 0) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-control" name="status_access_groups[{{ $group->id }}][select]">
                                    @foreach($accessOptions as $value => $label)
                                        <option value="{{ $value }}" {{ (int) old("status_access_groups.{$group->id}.select", $groupRule?->select_value ?? 0) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($userAccessRules->isNotEmpty())
            <h4>{{ trans('admin/statuslabels/table.user_overrides') }}</h4>
            <div class="table-responsive">
                <table class="table table-condensed table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('general.user') }}</th>
                            <th>{{ trans('admin/statuslabels/table.access_view') }}</th>
                            <th>{{ trans('admin/statuslabels/table.access_select') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($userAccessRules as $userRule)
                            <tr>
                                <td>{{ $userRule->user?->getFullNameAttribute() ?? trans('general.unknown') }}</td>
                                <td>
                                    <select class="form-control" name="status_access_users[{{ $userRule->subject_id }}][view]">
                                        @foreach($accessOptions as $value => $label)
                                            <option value="{{ $value }}" {{ (int) old("status_access_users.{$userRule->subject_id}.view", $userRule->view_value) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="status_access_users[{{ $userRule->subject_id }}][select]">
                                        @foreach($accessOptions as $value => $label)
                                            <option value="{{ $value }}" {{ (int) old("status_access_users.{$userRule->subject_id}.select", $userRule->select_value) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h4>{{ trans('admin/statuslabels/table.add_user_override') }}</h4>
        <div class="row">
            <div class="col-md-6">
                <select class="js-data-ajax" data-endpoint="users" data-placeholder="{{ trans('general.select_user') }}" name="new_status_access_user_id" style="width:100%">
                    <option value="">{{ trans('general.select_user') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control" name="new_status_access_user_view" aria-label="{{ trans('admin/statuslabels/table.access_view') }}">
                    @foreach($accessOptions as $value => $label)
                        <option value="{{ $value }}">{{ trans('admin/statuslabels/table.access_view') }}: {{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control" name="new_status_access_user_select" aria-label="{{ trans('admin/statuslabels/table.access_select') }}">
                    @foreach($accessOptions as $value => $label)
                        <option value="{{ $value }}">{{ trans('admin/statuslabels/table.access_select') }}: {{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

@stop

@section('moar_scripts')
    <!-- bootstrap color picker -->
    <script nonce="{{ csrf_token() }}">

        $(function() {
            $('.color').colorpicker({
                color: `{{ old('color', $item->color) ?: '#AA3399' }}`,
                format: 'hex'
            });
        });

    </script>

@stop
