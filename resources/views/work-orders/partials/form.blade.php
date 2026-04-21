@php
    $selectedVisibleUsers = old('visible_user_ids', isset($workOrder) ? $workOrder->visibleUsers->pluck('id')->all() : []);
    $portalVisibility = old('visibility_profile', $workOrder->visibility_profile ?: \App\Models\WorkOrder::VISIBILITY_PROFILE_FULL);
    $portalSettings = $workOrder->portal_visibility_json ?? [];
@endphp

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Summary') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6 form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                <label for="title">{{ __('Title') }}</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $workOrder->title) }}" required>
                {!! $errors->first('title', '<span class="help-block">:message</span>') !!}
            </div>
            <div class="col-md-3 form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                <label for="status">{{ trans('general.status') }}</label>
                <select name="status" id="status" class="form-control">
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $workOrder->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first('status', '<span class="help-block">:message</span>') !!}
            </div>
            <div class="col-md-3 form-group {{ $errors->has('priority') ? 'has-error' : '' }}">
                <label for="priority">{{ __('Priority') }}</label>
                <select name="priority" id="priority" class="form-control">
                    <option value="">{{ trans('general.select') }}</option>
                    @foreach($priorityOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('priority', $workOrder->priority) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first('priority', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group {{ $errors->has('company_id') ? 'has-error' : '' }}">
                <label for="company_id">{{ trans('general.company') }}</label>
                <select name="company_id" id="company_id" class="form-control">
                    <option value="">{{ trans('general.none') }}</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ (int) old('company_id', $workOrder->company_id) === (int) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
                {!! $errors->first('company_id', '<span class="help-block">:message</span>') !!}
            </div>
            <div class="col-md-6 form-group {{ $errors->has('primary_contact_user_id') ? 'has-error' : '' }}">
                <label for="primary_contact_user_id">{{ __('Primary Contact') }}</label>
                <select name="primary_contact_user_id" id="primary_contact_user_id" class="form-control">
                    <option value="">{{ trans('general.none') }}</option>
                    @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}" {{ (int) old('primary_contact_user_id', $workOrder->primary_contact_user_id) === (int) $contact->id ? 'selected' : '' }}>
                        {{ $contact->present()->fullName() }}
                    </option>
                    @endforeach
                </select>
                {!! $errors->first('primary_contact_user_id', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group {{ $errors->has('intake_date') ? 'has-error' : '' }}">
                <label for="intake_date">{{ __('Intake Date') }}</label>
                <input type="date" name="intake_date" id="intake_date" class="form-control" value="{{ old('intake_date', optional($workOrder->intake_date)->format('Y-m-d')) }}">
                {!! $errors->first('intake_date', '<span class="help-block">:message</span>') !!}
            </div>
            <div class="col-md-6 form-group {{ $errors->has('due_date') ? 'has-error' : '' }}">
                <label for="due_date">{{ __('Due Date') }}</label>
                <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', optional($workOrder->due_date)->format('Y-m-d')) }}">
                {!! $errors->first('due_date', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
            <label for="description">{{ trans('general.notes') }}</label>
            <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $workOrder->description) }}</textarea>
            {!! $errors->first('description', '<span class="help-block">:message</span>') !!}
        </div>
    </div>
</div>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Portal Visibility') }}</h3>
    </div>
    <div class="box-body">
        @if($workOrder->exists)
        <div class="form-group">
            <label>{{ __('Work Order Number') }}</label>
            <p class="form-control-static">{{ $workOrder->work_order_number }}</p>
        </div>
        @endif

        <div class="form-group {{ $errors->has('visibility_profile') ? 'has-error' : '' }}">
            <label for="visibility_profile">{{ __('Visibility Profile') }}</label>
            <select name="visibility_profile" id="visibility_profile" class="form-control">
                @foreach($visibilityProfileOptions as $value => $label)
                <option value="{{ $value }}" {{ $portalVisibility === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            {!! $errors->first('visibility_profile', '<span class="help-block">:message</span>') !!}
            <p class="help-block">{{ __('Full shows component activity. Basic hides component activity. Custom uses the toggles below.') }}</p>
        </div>

        <div class="checkbox">
            <label>
                <input type="checkbox" name="portal_show_components" value="1" {{ old('portal_show_components', data_get($portalSettings, 'show_components')) ? 'checked' : '' }}>
                {{ __('Show component activity in the customer portal') }}
            </label>
        </div>

        <div class="checkbox">
            <label>
                <input type="checkbox" name="portal_show_notes_customer" value="1" {{ old('portal_show_notes_customer', data_get($portalSettings, 'show_notes_customer', true)) ? 'checked' : '' }}>
                {{ __('Show customer notes in the customer portal') }}
            </label>
        </div>

        @if(auth()->user()?->can('manageVisibility', $workOrder ?: \App\Models\WorkOrder::class))
        <div class="form-group">
            <label for="visible_user_ids">{{ __('Explicit Visible Users') }}</label>
            <select name="visible_user_ids[]" id="visible_user_ids" class="form-control" multiple size="8">
                @foreach($visibleUsers as $visibleUser)
                <option value="{{ $visibleUser->id }}" {{ in_array($visibleUser->id, $selectedVisibleUsers, true) ? 'selected' : '' }}>
                    {{ $visibleUser->present()->fullName() }}
                </option>
                @endforeach
            </select>
            <p class="help-block">{{ __('Portal users added here can see this work order even without a company match.') }}</p>
        </div>
        @endif
    </div>
</div>
