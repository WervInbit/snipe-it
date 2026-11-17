@forelse ($profileDefinitions as $definition)
    @php($profile = $definition['profile'])
    <section class="panel panel-default legacy-workflow-profile" data-profile-id="{{ $profile->id }}">
        <div class="panel-heading">
            <strong>{{ $profile->name }}</strong>
            <span class="pull-right">
                <button type="button" class="btn btn-link btn-xs js-legacy-profile-select">{{ trans('tests.legacy_select_items') }}</button>
                <button type="button" class="btn btn-link btn-xs js-legacy-profile-clear">{{ trans('tests.legacy_clear') }}</button>
            </span>
        </div>
        <div class="panel-body">
            @foreach ($definition['items'] as $profileItem)
                <div class="checkbox">
                    <label>
                        <input type="checkbox"
                               name="legacy_profile_item_ids[]"
                               value="{{ $profileItem->id }}"
                               class="js-legacy-workflow-item">
                        <strong>{{ $profileItem->item->name }}</strong>
                        @unless ($profileItem->is_required)
                            <span class="label label-default">{{ trans('tests.legacy_optional') }}</span>
                        @endunless
                        @if (trim((string) $profileItem->item->instructions) !== '')
                            <span class="help-block">{{ $profileItem->item->instructions }}</span>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>
    </section>
@empty
    <div class="alert alert-info">{{ trans('tests.legacy_no_items') }}</div>
@endforelse
