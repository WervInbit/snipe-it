import { BrowserQRCodeReader } from '@zxing/browser';

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

const codeReader = new BrowserQRCodeReader();

async function startScan() {
    if (!navigator.mediaDevices) {
        return;
    }

    try {
        await codeReader.decodeFromVideoDevice(undefined, video, (result, err) => {
            if (result) {
                const tag = result.getText ? result.getText() : result.text;
                if (tag) {
                    window.location.href = `/assets/${encodeURIComponent(tag)}`;
                }
            }
        });
    } catch (err) {
        console.error('Unable to access camera', err);
    }
}

startScan();
