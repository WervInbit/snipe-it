import jsQR from 'jsqr';

const video = document.getElementById('scan-video');
const assetInput = document.getElementById('asset-tag');
const form = document.getElementById('scan-manual');

form.addEventListener('submit', (e) => {
    e.preventDefault();
    const tag = assetInput.value.trim();
    if (tag) {
        window.location.href = `/assets/${encodeURIComponent(tag)}`;
    }
});

function startScan() {
    if (!navigator.mediaDevices) {
        return;
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();
            requestAnimationFrame(tick);
        })
        .catch(err => {
            console.error('Unable to access camera', err);
        });
}

function tick() {
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        const canvas = document.createElement('canvas');
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, canvas.width, canvas.height);
        if (code) {
            window.location.href = `/assets/${encodeURIComponent(code.data)}`;
            return;
        }
    }
    requestAnimationFrame(tick);
}

startScan();
