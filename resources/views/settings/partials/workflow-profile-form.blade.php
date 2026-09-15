@php
    $profileId = $profile?->id ?? 'create';
    $selectedCategories = $profile?->categories?->pluck('id')->all() ?? [];
    $selectedDependencies = ($profile?->prerequisites ?? collect())->pluck('id')->all();
    $dependencyValues = array_map('intval', (array) old('dependency_profile_ids', $selectedDependencies));
@endphp

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    <label for="workflow-profile-name-{{ $profileId }}">{{ __('Name') }}</label>
    <input type="text"
           class="form-control"
           id="workflow-profile-name-{{ $profileId }}"
           name="name"
           value="{{ old('name', $profile?->name) }}">
    {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('slug') ? ' has-error' : '' }}">
    <label for="workflow-profile-slug-{{ $profileId }}">{{ __('Slug') }}</label>
    <input type="text"
           class="form-control"
           id="workflow-profile-slug-{{ $profileId }}"
           name="slug"
           value="{{ old('slug', $profile?->slug) }}">
    <span class="help-block">{{ __('Leave blank on create to generate this from the profile name.') }}</span>
    {!! $errors->first('slug', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
    <label for="workflow-profile-description-{{ $profileId }}">{{ __('Description') }}</label>
    <textarea class="form-control"
              id="workflow-profile-description-{{ $profileId }}"
              name="description"
              rows="3">{{ old('description', $profile?->description) }}</textarea>
    {!! $errors->first('description', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('category_ids') ? ' has-error' : '' }}">
    <label for="workflow-profile-categories-{{ $profileId }}">{{ __('Categories') }}</label>
    <select name="category_ids[]"
            id="workflow-profile-categories-{{ $profileId }}"
            class="form-control"
            multiple>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ in_array($category->id, old('category_ids', $selectedCategories)) ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    <span class="help-block">{{ __('Leave empty to allow this profile for every asset category.') }}</span>
    {!! $errors->first('category_ids', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('display_order') ? ' has-error' : '' }}">
    <label for="workflow-profile-display-order-{{ $profileId }}">{{ __('Display Order') }}</label>
    <input type="number"
           min="0"
           class="form-control"
           id="workflow-profile-display-order-{{ $profileId }}"
           name="display_order"
           value="{{ old('display_order', $profile?->display_order) }}">
    {!! $errors->first('display_order', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('repeat_policy') ? ' has-error' : '' }}">
    <label for="workflow-profile-repeat-policy-{{ $profileId }}">{{ __('Repeat Policy') }}</label>
    <select class="form-control"
            id="workflow-profile-repeat-policy-{{ $profileId }}"
            name="repeat_policy">
        <option value="{{ \App\Models\WorkflowProfile::REPEAT_OVERRIDE_REQUIRED }}" {{ old('repeat_policy', $profile?->repeat_policy ?? \App\Models\WorkflowProfile::REPEAT_OVERRIDE_REQUIRED) === \App\Models\WorkflowProfile::REPEAT_OVERRIDE_REQUIRED ? 'selected' : '' }}>
            {{ __('Authorized Start new confirmation required') }}
        </option>
        <option value="{{ \App\Models\WorkflowProfile::REPEAT_NEVER }}" {{ old('repeat_policy', $profile?->repeat_policy ?? \App\Models\WorkflowProfile::REPEAT_OVERRIDE_REQUIRED) === \App\Models\WorkflowProfile::REPEAT_NEVER ? 'selected' : '' }}>
            {{ __('Do not allow repeats') }}
        </option>
    </select>
    <span class="help-block">{{ __('An unfinished run is always continued. A finished or stale run remains editable; starting another is a separate, audited action.') }}</span>
    {!! $errors->first('repeat_policy', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('dependency_profile_ids') ? ' has-error' : '' }}">
    <label for="workflow-profile-dependencies-{{ $profileId }}">{{ __('Dependencies') }}</label>
    @if($dependencyProfiles->where('id', '!=', $profile?->id)->isNotEmpty())
        <select name="dependency_profile_ids[]"
                id="workflow-profile-dependencies-{{ $profileId }}"
                class="form-control"
                data-workflow-dependency-select
                data-placeholder="{{ __('Search and select prerequisite workflows') }}"
                multiple>
            @foreach($dependencyProfiles->where('id', '!=', $profile?->id) as $dependencyProfile)
                <option value="{{ $dependencyProfile->id }}" @selected(in_array((int) $dependencyProfile->id, $dependencyValues, true))>
                    {{ $loop->iteration }}. {{ $dependencyProfile->name }}{{ !$dependencyProfile->is_active ? ' - '.__('Inactive') : '' }}
                </option>
            @endforeach
        </select>
    @else
        <p class="form-control-static text-muted">{{ __('Create another workflow profile before adding dependencies.') }}</p>
    @endif
    <span class="help-block">{{ __('Every item marked Required in this workflow in each selected prerequisite must pass or be done. Dependencies can be changed later; circular and inactive selections are rejected.') }}</span>
    {!! $errors->first('dependency_profile_ids', '<span class="help-block">:message</span>') !!}
</div>

<div class="form-group{{ $errors->has('execution_level') ? ' has-error' : '' }}">
    <label for="workflow-profile-execution-level-{{ $profileId }}">{{ __('Who may execute this workflow?') }}</label>
    <select class="form-control"
            id="workflow-profile-execution-level-{{ $profileId }}"
            name="execution_level">
        <option value="{{ \App\Models\WorkflowProfile::EXECUTION_OPERATOR }}" @selected(old('execution_level', $profile?->execution_level ?? \App\Models\WorkflowProfile::EXECUTION_OPERATOR) === \App\Models\WorkflowProfile::EXECUTION_OPERATOR)>{{ __('Operator or above') }}</option>
        <option value="{{ \App\Models\WorkflowProfile::EXECUTION_SENIOR }}" @selected(old('execution_level', $profile?->execution_level ?? \App\Models\WorkflowProfile::EXECUTION_OPERATOR) === \App\Models\WorkflowProfile::EXECUTION_SENIOR)>{{ __('Senior or Supervisor') }}</option>
        <option value="{{ \App\Models\WorkflowProfile::EXECUTION_SUPERVISOR }}" @selected(old('execution_level', $profile?->execution_level ?? \App\Models\WorkflowProfile::EXECUTION_OPERATOR) === \App\Models\WorkflowProfile::EXECUTION_SUPERVISOR)>{{ __('Supervisor only') }}</option>
    </select>
    {!! $errors->first('execution_level', '<span class="help-block">:message</span>') !!}
</div>

<input type="hidden" name="is_active" value="0">
<div class="checkbox">
    <label>
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $profile?->is_active ?? true) ? 'checked' : '' }}>
        {{ __('Active') }}
    </label>
</div>
{!! $errors->first('is_active', '<span class="help-block">:message</span>') !!}

<input type="hidden" name="is_default" value="0">
<div class="checkbox">
    <label>
        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $profile?->is_default ?? false) ? 'checked' : '' }}>
        {{ __('Default profile') }}
    </label>
</div>

<input type="hidden" name="blocks_sale_readiness" value="0">
<div class="checkbox">
    <label>
        <input type="checkbox" name="blocks_sale_readiness" value="1" {{ old('blocks_sale_readiness', $profile?->blocks_sale_readiness ?? false) ? 'checked' : '' }}>
        {{ __('Required before Ready for Sale') }}
    </label>
</div>
