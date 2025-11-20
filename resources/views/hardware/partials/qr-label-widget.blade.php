@php($templateName = $qrTemplates[$selectedTemplate]['name'] ?? $selectedTemplate)
<div class="panel panel-default qr-label-panel">
    <div class="panel-heading">
        <strong>{{ trans('general.print_qr') }}</strong>
        <span class="text-muted">· {{ $templateName }}</span>
    </div>
    <div class="panel-body text-center">
        @php($assetLabel = $asset->name ?: ($asset->asset_tag ?? $asset->id))
        @if ($qrPng)
            <img
                src="{{ $qrPng }}"
                class="img-thumbnail"
                style="height: 150px; width: 150px; margin-bottom: 10px;"
                alt="{{ trans('general.qr_preview_for', ['asset' => $assetLabel]) }}"
            >
        @endif

        <form method="get" class="form-inline" style="margin-bottom: 10px;">
            @foreach(request()->except('template') as $key => $value)
                @if (is_array($value))
                    @foreach($value as $entry)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $entry }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="asset-template-picker" class="sr-only">{{ trans('admin/settings/general.qr_label_template') }}</label>
            <select
                name="template"
                id="asset-template-picker"
                class="form-control"
                onchange="this.form.submit()"
                aria-label="{{ trans('admin/settings/general.qr_label_template') }}"
            >
                @foreach($qrTemplates as $key => $tpl)
                    <option value="{{ $key }}" @selected($selectedTemplate === $key)>
                        {{ $tpl['name'] }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="btn-group">
            @if ($qrPdf)
                <a href="{{ $qrPdf }}" target="_blank" class="btn btn-primary">
                    <x-icon type="print" /> {{ trans('general.print_pdf') }}
                </a>
            @endif
            @if ($qrPng)
                <a href="{{ $qrPng }}" download class="btn btn-default">
                    <x-icon type="download" /> {{ trans('general.download_png') }}
                </a>
            @endif
        </div>

        <button
            type="button"
            class="btn btn-success btn-block"
            id="qr-server-print-button"
            data-print-url="{{ route('hardware.print-label', $asset) }}"
            data-template-selector="#asset-template-picker"
        >
            <x-icon type="print" /> {{ __('Print to LabelWriter') }}
        </button>
    </div>
    <div class="panel-footer small text-muted text-left">
        {{ trans('general.qr_template_hint') }}
    </div>
</div>
<script>
(function () {
    var button = document.getElementById('qr-server-print-button');
    if (!button) return;
    var templateSelector = button.getAttribute('data-template-selector');
    var templateField = document.querySelector(templateSelector);
    var token = document.querySelector('meta[name="csrf-token"]');
    var csrf = token ? token.getAttribute('content') : '';

    button.addEventListener('click', function () {
        if (!templateField) return;
        button.disabled = true;
        button.classList.add('disabled');

        fetch(button.getAttribute('data-print-url'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                template: templateField.value
            })
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (payload) {
                var msg = payload.data && payload.data.message ? payload.data.message : (payload.ok ? 'Label sent to printer.' : 'Printing failed.');
                if (window.toastr) {
                    payload.ok ? toastr.success(msg) : toastr.error(msg);
                } else {
                    alert(msg);
                }
            })
            .catch(function () {
                var msg = 'Printing failed.';
                if (window.toastr) {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            })
            .then(function () {
                button.disabled = false;
                button.classList.remove('disabled');
            });
    });
})();
</script>
