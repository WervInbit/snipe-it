@inject('qrLabels', 'App\\Services\\QrLabelService')
<a href="{{ $qrLabels->url($asset, 'pdf') }}" target="_blank">{{ trans('general.download') }} PDF</a>
