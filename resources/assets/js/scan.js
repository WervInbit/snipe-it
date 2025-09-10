import { BrowserQRCodeReader } from '@zxing/browser';

const video = document.getElementById('scan-video');
const errorEl = document.getElementById('scan-error');
const statusEl = document.getElementById('scan-status');
const startBtn = document.getElementById('scan-start');
const stopBtn = document.getElementById('scan-stop');
const cameraSelect = document.getElementById('camera-select');
const assetInput = document.getElementById('asset-tag');
const form = document.getElementById('scan-manual');
const byTagUrl = '/hardware/bytag/';
const apiByTagUrl = '/api/v1/assets/bytag/';
// Diagnostics elements
const diagBtn = document.getElementById('scan-diagnostics');
const diagReqBtn = document.getElementById('scan-request-perm');
const diagSecure = document.getElementById('diag-secure');
const diagOrigin = document.getElementById('diag-origin');
const diagPerm = document.getElementById('diag-perm');
const diagMedia = document.getElementById('diag-mediadev');
const diagVideos = document.getElementById('diag-videos');
const diagLog = document.getElementById('scan-diag-log');

async function redirectToAsset(data) {
    const value = data.trim();
    try {
        const url = new URL(value);
        window.location.href = url.href;
    } catch (e) {
        try {
            const resp = await fetch(`${apiByTagUrl}${encodeURIComponent(value)}`, { headers: { Accept: 'application/json' } });
            if (resp.ok) {
                const json = await resp.json();
                const id = json && json.data && json.data.id;
                if (id) {
                    window.location.href = `/hardware/${id}`;
                    return;
                }
            }
            showError('Asset not found. Please try again or check the QR code.');
            startScan();
        } catch (err) {
            showError('Asset lookup failed. Please try again.');
            startScan();
        }
    }
}

form.addEventListener('submit', (e) => {
    e.preventDefault();
    const tag = assetInput.value.trim();
    if (tag) {
        redirectToAsset(tag);
    }
});

const codeReader = new BrowserQRCodeReader();

function showError(msg) {
    if (errorEl) {
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
    } else {
        // eslint-disable-next-line no-alert
        alert(msg);
    }
}

function showStatus(msg) {
    if (statusEl) {
        statusEl.textContent = msg;
    }
}

async function startScan() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        const msg = 'Camera access unavailable. Allow permissions or enter the asset tag manually.';
        showError(msg);
        return;
    }

    try {
        if (video) {
            video.setAttribute('playsinline', 'true');
            video.muted = true;
        }

        // Explicitly request camera access to trigger OS/browser permission prompt on mobile
        showStatus('Requesting camera permission...');
        try {
            const preStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } }
            });
            if (video) {
                video.srcObject = preStream;
                if (video.play) video.play().catch(() => {});
            }
            // Immediately release so ZXing can acquire the camera
            preStream.getTracks().forEach(t => t.stop());
        } catch (permErr) {
            showError(`Unable to access camera: ${permErr && permErr.message ? permErr.message : permErr}`);
            return;
        }

        // Choose back camera if available and populate selector
        let deviceId = null;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videos = devices.filter(d => d.kind === 'videoinput');
            const back = videos.find(d => /back|rear|environment/i.test(d.label));
            deviceId = (back && back.deviceId) || (videos[0] && videos[0].deviceId) || null;
            if (cameraSelect) {
                cameraSelect.innerHTML = '';
                videos.forEach((d, i) => {
                    const opt = document.createElement('option');
                    opt.value = d.deviceId;
                    opt.textContent = d.label || `Camera ${i + 1}`;
                    if (d.deviceId === deviceId) opt.selected = true;
                    cameraSelect.appendChild(opt);
                });
            }
        } catch (e) { /* ignore */ }

        showStatus(`Using ${deviceId ? 'selected' : 'default'} camera...`);
        await codeReader.decodeFromVideoDevice(deviceId, video, (result, err) => {
            if (result) {
                const tag = result.getText ? result.getText() : result.text;
                if (tag) {
                    try { codeReader.reset(); } catch (e) { /* ignore */ }
                    redirectToAsset(tag);
                }
            }
            if (err && err.name && err.name !== 'NotFoundException') {
                showError(err.message || String(err));
            }
        });

        // Allow switching cameras on the fly
        if (cameraSelect && !cameraSelect.dataset.bound) {
            cameraSelect.dataset.bound = '1';
            cameraSelect.addEventListener('change', async (ev) => {
                try { codeReader.reset(); } catch (e) { /* ignore */ }
                await codeReader.decodeFromVideoDevice(ev.target.value || null, video, (result, err) => {
                    if (result) {
                        const tag = result.getText ? result.getText() : result.text;
                        if (tag) {
                            try { codeReader.reset(); } catch (e) { /* ignore */ }
                            redirectToAsset(tag);
                        }
                    }
                    if (err && err.name && err.name !== 'NotFoundException') {
                        showError(err.message || String(err));
                    }
                });
            });
        }
    } catch (err) {
        console.error('Unable to access camera', err);
        showError(`Unable to access camera: ${err && err.message ? err.message : err}`);
    }
}

async function runDiagnostics() {
    try {
        if (diagSecure) diagSecure.textContent = window.isSecureContext ? 'true' : 'false';
        if (diagOrigin) diagOrigin.textContent = window.location.origin;
        if (diagMedia) diagMedia.textContent = (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) ? 'available' : 'missing';

        // Permissions API
        let permState = 'unknown';
        try {
            if (navigator.permissions && navigator.permissions.query) {
                const p = await navigator.permissions.query({ name: 'camera' });
                permState = p && p.state || 'unknown';
            }
        } catch (e) {
            permState = 'unsupported';
        }
        if (diagPerm) diagPerm.textContent = permState;

        // List devices; if labels empty, try to nudge permission with a minimal gUM
        let devices = [];
        try {
            devices = await navigator.mediaDevices.enumerateDevices();
        } catch (e) {
            // try to trigger permission prompt via getUserMedia
            try {
                await navigator.mediaDevices.getUserMedia({ video: true });
                devices = await navigator.mediaDevices.enumerateDevices();
            } catch (ee) {
                if (diagLog) diagLog.textContent = `enumerateDevices failed: ${ee && ee.message ? ee.message : ee}`;
            }
        }
        const videos = devices.filter(d => d.kind === 'videoinput');
        if (diagVideos) diagVideos.textContent = `${videos.length} (${videos.map(v => v.label || 'unlabeled').join(', ') || 'none'})`;
        if (diagLog) {
            diagLog.textContent = JSON.stringify(devices.map(d => ({ kind: d.kind, label: d.label, id: d.deviceId })), null, 2);
        }
    } catch (e) {
        if (diagLog) diagLog.textContent = `Diagnostics error: ${e && e.message ? e.message : e}`;
    }
}

// Start scanning on explicit user action to ensure permission prompt appears
if (startBtn) {
    startBtn.addEventListener('click', () => {
        if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; }
        startScan();
    });
}

if (diagBtn) {
    diagBtn.addEventListener('click', () => runDiagnostics());
}

// Direct permission request helper for stubborn browsers
async function requestPermissionNow() {
    try {
        showStatus('Requesting camera permission via getUserMedia...');
        const s = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        // Immediately stop; this is only to obtain permission
        s.getTracks().forEach(t => t.stop());
        showStatus('Permission granted.');
        // Re-run diagnostics to update permission/device labels
        runDiagnostics();
    } catch (e) {
        console.error('Permission request failed', e);
        showError(`${e && e.name ? e.name + ': ' : ''}${e && e.message ? e.message : e}`);
    }
}

if (diagReqBtn) {
    diagReqBtn.addEventListener('click', () => requestPermissionNow());
}

// Stop scanning and release camera
async function stopScan() {
    try { codeReader.reset(); } catch (e) { /* ignore */ }
    if (video && video.srcObject) {
        try { video.srcObject.getTracks().forEach(t => t.stop()); } catch (e) { /* ignore */ }
        video.srcObject = null;
    }
    showStatus('Camera stopped.');
}

if (stopBtn) {
    stopBtn.addEventListener('click', () => stopScan());
}

// Expose for inline onclick fallback (ensures Start works even if event binding fails)
window.snipeStartScan = () => { if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; } startScan(); };
window.snipeStopScan  = () => stopScan();

// Autostart if permission already granted (labels are visible)
if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
    navigator.mediaDevices.enumerateDevices().then((devices) => {
        const anyLabeled = devices.some(d => d.kind === 'videoinput' && d.label);
        if (anyLabeled) {
            startScan();
        }
    }).catch(() => {});
}
