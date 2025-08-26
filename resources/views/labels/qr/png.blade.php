@inject('qrLabels', 'App\\Services\\QrLabelService')
<img src="{{ $qrLabels->url($asset, 'png') }}" alt="QR Code">
