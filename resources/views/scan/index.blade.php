@extends('layouts/default')

@section('title')
    {{ __('Scan QR Code') }}
    @parent
@stop

@section('content')
<div class="text-center">
    <h1>{{ __('Scan a QR code') }}</h1>
    <p>{{ __('Align the QR code within the frame.') }}</p>
    <video id="qr-video" style="width:100%;max-width:400px;height:auto;border:1px solid #ccc;" autoplay muted playsinline></video>
    <canvas id="qr-canvas" hidden></canvas>
    <button id="switch-camera" class="btn btn-secondary mt-2" style="display:none;">
        {{ __('Switch Camera') }}
    </button>
    <div id="qr-result" class="mt-3"></div>
</div>
@stop

@section('moar_scripts')
<script src="https://unpkg.com/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
    (function () {
        const video = document.getElementById('qr-video');
        const canvasElement = document.getElementById('qr-canvas');
        const canvas = canvasElement.getContext('2d');
        const result = document.getElementById('qr-result');
        const switchBtn = document.getElementById('switch-camera');
        const byTagUrl = "{{ url('hardware/bytag') }}/";
        let currentStream;
        let devices = [];
        let currentDeviceId;

        async function start(deviceId) {
            const constraints = deviceId ? { video: { deviceId: { exact: deviceId } } } : { video: { facingMode: 'environment' } };
            currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = currentStream;
            video.setAttribute('playsinline', true);
            await video.play();
            devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
            if (devices.length > 1) switchBtn.style.display = 'inline-block';
            currentDeviceId = deviceId || devices[0].deviceId;
            requestAnimationFrame(tick);
        }

        function switchCamera() {
            if (devices.length < 2) return;
            const idx = devices.findIndex(d => d.deviceId === currentDeviceId);
            const nextDevice = devices[(idx + 1) % devices.length];
            currentStream.getTracks().forEach(t => t.stop());
            start(nextDevice.deviceId);
        }

        function tick() {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvasElement.height = video.videoHeight;
                canvasElement.width = video.videoWidth;
                canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
                const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                if (code) {
                    currentStream.getTracks().forEach(t => t.stop());
                    const data = code.data.trim();
                    let target = data;
                    try {
                        const parsed = new URL(data);
                        target = parsed.href;
                    } catch (e) {
                        target = byTagUrl + encodeURIComponent(data);
                    }
                    window.location.href = target;
                    return;
                }
            }
            requestAnimationFrame(tick);
        }

        switchBtn.addEventListener('click', switchCamera);

        start().catch(err => {
            result.textContent = err.message || '{{ __('Camera unavailable') }}';
        });
    })();
</script>
@stop
