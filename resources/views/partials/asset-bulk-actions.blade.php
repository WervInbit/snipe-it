<div id="{{ (isset($id_divname)) ? $id_divname : 'assetsBulkEditToolbar' }}" style="min-width:400px">
    <form
    method="POST"
    action="{{ route('hardware/bulkedit') }}"
    accept-charset="UTF-8"
    class="form-inline"
    id="{{ (isset($id_formname)) ? $id_formname : 'assetsBulkForm' }}"
>
    @csrf

    {{-- The sort and order will only be used if the cookie is actually empty (like on first-use) --}}
    <input name="sort" type="hidden" value="assets.id">
    <input name="order" type="hidden" value="asc">
    <label for="bulk_actions">
        <span class="sr-only">
            {{ trans('button.bulk_actions') }}
        </span>
    </label>
    <select name="bulk_actions" class="form-control select2" aria-label="bulk_actions" style="min-width: 350px !important;">
        @if ((isset($status)) && ($status == 'Deleted'))
            @can('delete', \App\Models\Asset::class)
                <option value="restore">{{trans('button.restore')}}</option>
            @endcan
        @else

            @can('update', \App\Models\Asset::class)
                <option value="edit">{{ trans('button.edit') }}</option>
                <option value="batch-edit">{{ trans('general.batch_edit') }}</option>
                <option value="maintenance">{{ trans('button.add_maintenance') }}</option>
            @endcan

            @can('delete', \App\Models\Asset::class)
                <option value="delete">{{ trans('button.delete') }}</option>
            @endcan

            <option value="labels" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=l" : ''}}>{{ trans_choice('button.generate_labels', 2) }}</option>
            <option value="qr">{{ trans('general.generate_qrs') }}</option>
        @endif
    </select>

    @php($qrTemplates = config('qr_templates.templates'))
    @php($selectedQrTemplate = old('qr_template', $snipeSettings->qr_label_template ?? config('qr_templates.default')))
    @if (!empty($qrTemplates))
        @if (count($qrTemplates) === 1)
            <input type="hidden" name="qr_template" value="{{ array_key_first($qrTemplates) }}">
        @else
            <label for="bulk_qr_template" class="sr-only">
                {{ trans('admin/settings/general.qr_label_template') }}
            </label>
            <select
                name="qr_template"
                id="bulk_qr_template"
                class="form-control select2"
                aria-label="qr_template"
                style="min-width: 220px !important; margin-left: 10px;"
                data-placeholder="{{ trans('admin/settings/general.qr_label_template') }}"
            >
                @foreach($qrTemplates as $key => $tpl)
                    <option value="{{ $key }}" @selected($selectedQrTemplate === $key)>
                        {{ $tpl['name'] }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted" style="margin-left: 10px;">
                {{ trans('general.bulk_qr_template_hint') }}
            </small>
        @endif
    @endif

    <button class="btn btn-primary" id="{{ (isset($id_button)) ? $id_button : 'bulkAssetEditButton' }}" disabled>{{ trans('button.go') }}</button>
    </form>
</div>
