@can('delete', \App\Models\Location::class)
    <div id="locationsBulkEditToolbar" class="bulk-edit-toolbar bulk-edit-toolbar--locations">
    <form
        method="POST"
        action="{{ route('locations.bulkdelete.show') }}"
        accept-charset="UTF-8"
        class="form-inline bulk-edit-toolbar__form"
        id="locationsBulkForm"
    >
            @csrf
            <div id="locations-toolbar" class="bulk-edit-toolbar__inner">
                <label for="bulk_actions" class="sr-only">{{ trans('general.bulk_actions') }}</label>
                <select name="bulk_actions" class="form-control select2 bulk-edit-toolbar__select" aria-label="bulk_actions">
                    <option value="delete">{{ trans('general.bulk_delete') }}</option>
                </select>
                <button class="btn btn-primary bulk-edit-toolbar__button" id="bulkLocationsEditButton" disabled>{{ trans('button.go') }}</button>
            </div>

    </form>
    </div>
@endcan

