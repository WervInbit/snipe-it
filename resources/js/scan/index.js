import jsQR from 'jsqr';

// Tweak width/height or interval to balance speed and accuracy on mobile devices.
// Tested at 640x480 @400ms on Pixel 5 and iPhone 12 in indoor lighting.
export const config = {
    width: 640,
    height: 480,
    interval: 400,
};

const video = document.getElementById('scan-video');
const overlay = document.getElementById('scan-overlay');
const errorEl = document.getElementById('scan-error');
const statusEl = document.getElementById('scan-status');
const cameraSelect = document.getElementById('camera-select');
const startBtn = document.getElementById('scan-start');
const stopBtn = document.getElementById('scan-stop');
const form = document.getElementById('scan-manual');
const assetInput = document.getElementById('asset-tag');

const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
let stream = null;
let loop = null;

function showError(msg) {
    if (errorEl) {
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
    }
}

function clearError() {
    if (errorEl) {
        errorEl.style.display = 'none';
        errorEl.textContent = '';
    }
}

function showStatus(msg) {
    if (statusEl) {
        statusEl.textContent = msg;
    }
}

function beep() {
    try {
        const ac = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ac.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = 880;
        osc.connect(ac.destination);
        osc.start();
        osc.stop(ac.currentTime + 0.15);
    } catch (e) {
        // ignore
    }
}

function drawBox(loc) {
    const octx = overlay.getContext('2d');
    octx.clearRect(0, 0, overlay.width, overlay.height);
    if (!loc) return;
    octx.strokeStyle = 'lime';
    octx.lineWidth = 4;
    octx.beginPath();
    octx.moveTo(loc.topLeftCorner.x, loc.topLeftCorner.y);
    octx.lineTo(loc.topRightCorner.x, loc.topRightCorner.y);
    octx.lineTo(loc.bottomRightCorner.x, loc.bottomRightCorner.y);
    octx.lineTo(loc.bottomLeftCorner.x, loc.bottomLeftCorner.y);
    octx.closePath();
    octx.stroke();
}

function redirect(tag) {
    window.location.href = `/hardware/bytag/${encodeURIComponent(tag)}`;
}

function stop() {
    if (loop) {
        clearInterval(loop);
        loop = null;
    }
    if (stream) {
        stream.getTracks().forEach((t) => t.stop());
        stream = null;
    }
    drawBox();
    showStatus('');
}

async function start() {
    clearError();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showError('Camera access unavailable. Enter the asset tag manually.');
        return;
    }

    try {
        const constraints = {
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: config.width },
                height: { ideal: config.height },
            },
        };
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = stream;
        await video.play();

        const track = stream.getVideoTracks()[0].getSettings();
        const w = track.width || config.width;
        const h = track.height || config.height;
        canvas.width = overlay.width = w;
        canvas.height = overlay.height = h;

        if (cameraSelect) {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videos = devices.filter((d) => d.kind === 'videoinput');
            cameraSelect.innerHTML = '';
            videos.forEach((d, i) => {
                const opt = document.createElement('option');
                opt.value = d.deviceId;
                opt.textContent = d.label || `Camera ${i + 1}`;
                if (track.deviceId === d.deviceId) opt.selected = true;
                cameraSelect.appendChild(opt);
            });
        }

        loop = setInterval(scan, config.interval);
    } catch (e) {
        showError(`Unable to access camera: ${e.message || e}`);
    }
}

async function switchCamera(id) {
    if (!id) return;
    stop();
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: id }, width: { ideal: config.width }, height: { ideal: config.height } },
        });
        video.srcObject = stream;
        await video.play();
        loop = setInterval(scan, config.interval);
    } catch (e) {
        showError(`Unable to switch camera: ${e.message || e}`);
    }
}

function scan() {
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' });
    if (code) {
        drawBox(code.location);
        beep();
        stop();
        redirect(code.data);
    } else {
        drawBox();
    }
}

if (startBtn) startBtn.addEventListener('click', start);
if (stopBtn) stopBtn.addEventListener('click', stop);
if (cameraSelect) cameraSelect.addEventListener('change', (e) => switchCamera(e.target.value));

if (form) {
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const tag = assetInput.value.trim();
        if (tag) redirect(tag);
    });
}

// Auto-start if permission already granted
navigator.permissions?.query({ name: 'camera' }).then((p) => {
    if (p.state === 'granted') start();
}).catch(() => {});

// Expose control for debugging/tuning
window.scanModule = { start, stop, config };
