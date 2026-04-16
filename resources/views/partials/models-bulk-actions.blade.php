<div id="modelsBulkEditToolbar" class="bulk-edit-toolbar bulk-edit-toolbar--models">
    <form
        method="POST"
        action="{{route('models.bulkedit.index')}}"
        accept-charset="UTF-8"
        class="form-inline bulk-edit-toolbar__form"
        id="modelsBulkForm"
    >
    @csrf
    @if (request('status')!='deleted')
        @can('delete', \App\Models\AssetModel::class)
            <div id="models-toolbar" class="bulk-edit-toolbar__inner">
                <label for="bulk_actions" class="sr-only">{{ trans('general.bulk_actions') }}</label>
                <select name="bulk_actions" class="form-control select2 bulk-edit-toolbar__select" aria-label="bulk_actions">
                    <option value="edit">{{ trans('general.bulk_edit') }}</option>
                    <option value="delete">{{ trans('general.bulk_delete') }}</option>
                </select>
                <button class="btn btn-primary bulk-edit-toolbar__button" id="bulkModelsEditButton" disabled>{{ trans('button.go') }}</button>
            </div>
        @endcan
    @endif
    </form>
</div>


