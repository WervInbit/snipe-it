@extends('layouts.default')

@section('title', __('Scan QR Code'))

@section('content')
<div class="container" style="max-width:640px;margin:0 auto;">
    <h1>{{ __('Scan QR Code') }}</h1>

    <!-- Handmatige invoer (fallback) -->
    <form id="scan-manual" class="mb-3">
        <div class="input-group">
            <input type="text" id="asset-tag" class="form-control" placeholder="{{ __('Asset tag handmatig invoeren') }}">
            <button type="submit" class="btn btn-primary">{{ __('Zoek') }}</button>
        </div>
    </form>

    <!-- Videovenster voor camerastream -->
    <div class="border rounded p-2" style="width:100%;max-width:500px;margin:0 auto;">
        <video id="scan-video" playsinline style="width:100%;height:auto;"></video>
    </div>

    <p id="scan-error" class="text-danger mt-2" style="display:none;"></p>
</div>
@endsection

@push('scripts')
    <!-- Laad uw lokaal gehoste ZXing-bibliotheek -->
    <script src="{{ asset('js/zxing-browser.min.js') }}"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const video = document.getElementById('scan-video');
        const assetInput = document.getElementById('asset-tag');
        const form = document.getElementById('scan-manual');
        const errorEl = document.getElementById('scan-error');

        // Handmatige zoek-actie
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const tag = assetInput.value.trim();
            if (tag) {
                // Redirect to Snipe-IT route that resolves an asset by tag
                window.location.href = `/hardware/bytag/${encodeURIComponent(tag)}`;
            }
        });

        // Controleer of de Camera API beschikbaar is
        if (!navigator.mediaDevices) {
            errorEl.textContent = '{{ __("Camera API is niet beschikbaar in deze browser.") }}';
            errorEl.style.display = 'block';
            return;
        }

        // Initialiseer de QR-codelezer van ZXing
        const codeReader = new ZXingBrowser.BrowserQRCodeReader();

        async function startScan() {
            try {
                // Start het decoderen van de camera; null kiest de default camera
                await codeReader.decodeFromVideoDevice(null, video, (result, err) => {
                    if (result) {
                        const text = result.getText ? result.getText() : result.text;
                        if (text) {
                            // Stop de scanner voordat u navigeert
                            codeReader.reset();
                            // Redirect to Snipe-IT route that resolves an asset by tag
                            window.location.href = `/hardware/bytag/${encodeURIComponent(text)}`;
                        }
                    }
                    // Toon foutmeldingen behalve "NotFoundException" (geen QR in frame)
                    if (err && !(err instanceof ZXingBrowser.NotFoundException)) {
                        console.error(err);
                    }
                });
            } catch (err) {
                console.error('Unable to access camera', err);
                errorEl.textContent = '{{ __("Kan camera niet benaderen: ") }}' + err.message;
                errorEl.style.display = 'block';
            }
        }

        startScan();
    });
    </script>
@endpush
