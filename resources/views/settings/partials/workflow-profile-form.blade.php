@php
    $profileId = $profile?->id ?? 'create';
    $selectedCategories = $profile?->categories?->pluck('id')->all() ?? [];
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
           value="{{ old('display_order', $profile?->display_order ?? 0) }}">
    {!! $errors->first('display_order', '<span class="help-block">:message</span>') !!}
</div>

<input type="hidden" name="is_active" value="0">
<div class="checkbox">
    <label>
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $profile?->is_active ?? true) ? 'checked' : '' }}>
        {{ __('Active') }}
    </label>
</div>

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
        {{ __('Block Ready for Sale when incomplete or failed') }}
    </label>
</div>
