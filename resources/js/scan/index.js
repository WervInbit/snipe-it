import jsQR from 'jsqr';

const defaults = {
  width: 1280, // request higher resolution to aid focus/decoding
  height: 720,
  interval: 150, // slightly slower sampling to allow focus/torch to stabilize
  beep: true,
};
const config = Object.assign({}, defaults, window.scanConfig || {});

const video = document.getElementById('scan-video');
const overlay = document.getElementById('scan-overlay');
const errorEl = document.getElementById('scan-error');
const permissionBanner = document.getElementById('scan-permission');
const manualForm = document.getElementById('manual-form');
const manualInput = document.getElementById('manual-tag');
const manualToggle = document.getElementById('manual-toggle');
const switchBtn = document.getElementById('scan-switch');
const refocusBtn = document.getElementById('scan-refocus');
const torchBtn = document.getElementById('scan-torch');
const hintBanner = document.getElementById('scan-hint');

const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
const octx = overlay.getContext('2d');

let stream = null;
let timer = null;
let hintTimer = null;
let devices = [];
let currentDeviceIndex = 0;
let torchOn = false;

function showError(msg) {
  if (!errorEl) return;
  errorEl.textContent = msg;
  errorEl.classList.remove('d-none');
  errorEl.style.display = '';
}

function hideError() {
  if (!errorEl) return;
  errorEl.classList.add('d-none');
  errorEl.textContent = '';
  errorEl.style.display = 'none';
}

function showManual() {
  if (manualForm) {
    manualForm.classList.remove('d-none');
    manualForm.style.display = '';
  }
}

function showPermissionBanner() {
  if (permissionBanner) {
    permissionBanner.classList.remove('d-none');
    permissionBanner.style.display = '';
  }
}

function hidePermissionBanner() {
  if (permissionBanner) {
    permissionBanner.classList.add('d-none');
    permissionBanner.style.display = 'none';
  }
}

function beep() {
  if (!config.beep || !window.AudioContext) return;
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  const ac = new AudioContext();
  const osc = ac.createOscillator();
  osc.type = 'sine';
  osc.frequency.value = 600;
  osc.connect(ac.destination);
  osc.start();
  osc.stop(ac.currentTime + 0.15);
}

function drawLine(begin, end) {
  octx.beginPath();
  octx.moveTo(begin.x, begin.y);
  octx.lineTo(end.x, end.y);
  octx.lineWidth = 4;
  octx.strokeStyle = '#00FF00';
  octx.stroke();
}

function clearOverlay() {
  octx.clearRect(0, 0, overlay.width, overlay.height);
}

function showHint() {
  if (hintBanner) {
    hintBanner.classList.remove('d-none');
    hintBanner.style.display = '';
  }
}

function hideHint() {
  if (hintBanner) {
    hintBanner.classList.add('d-none');
    hintBanner.style.display = 'none';
  }
}

function redirect(tag) {
  window.location.href = `/hardware/bytag/${encodeURIComponent(tag)}`;
}

function stop() {
  if (timer) clearInterval(timer);
  if (hintTimer) clearTimeout(hintTimer);
  if (stream) stream.getTracks().forEach((t) => t.stop());
  stream = null;
  timer = null;
  hintTimer = null;
}

async function enumerateVideoInputs() {
  if (!navigator.mediaDevices?.enumerateDevices) {
    return [];
  }
  const allDevices = await navigator.mediaDevices.enumerateDevices();
  return allDevices.filter((device) => device.kind === 'videoinput');
}

async function start(deviceId = null) {
  stop();
  hideError();
  hidePermissionBanner();
  hideHint();

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    showError('Camera not available');
    showPermissionBanner();
    showManual();
    return;
  }

  const videoConstraints = deviceId
    ? { deviceId: { exact: deviceId } }
    : {
        facingMode: { ideal: 'environment' },
        width: { ideal: config.width },
        height: { ideal: config.height },
      };

  // Prefer autofocus; some devices honour these hints.
  if (!videoConstraints.advanced) {
    videoConstraints.advanced = [];
  }
  videoConstraints.advanced.push({ focusMode: 'continuous' }, { focusMode: 'auto' });

  const constraints = {
    video: videoConstraints,
    audio: false,
  };

  try {
    stream = await navigator.mediaDevices.getUserMedia(constraints);
    video.srcObject = stream;
    await video.play();

    syncViewportSizes();

    timer = setInterval(sample, config.interval);
    hintTimer = setTimeout(showHint, 10_000);
  } catch (err) {
    console.error('Unable to access camera', err);
    showError('Unable to access camera');
    showPermissionBanner();
    showManual();
  }
}

function sample() {
  if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
  clearOverlay();
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const code = jsQR(imageData.data, imageData.width, imageData.height);
  if (code) {
    const loc = code.location;
    drawLine(loc.topLeftCorner, loc.topRightCorner);
    drawLine(loc.topRightCorner, loc.bottomRightCorner);
    drawLine(loc.bottomRightCorner, loc.bottomLeftCorner);
    drawLine(loc.bottomLeftCorner, loc.topLeftCorner);
    beep();
    stop();
    redirect(code.data);
  }
}

async function switchCamera() {
  if (devices.length <= 1) return;
  currentDeviceIndex = (currentDeviceIndex + 1) % devices.length;
  await start(devices[currentDeviceIndex].deviceId);
}

async function toggleTorch() {
  const [track] = stream?.getVideoTracks() || [];
  if (!track?.getCapabilities || !track.applyConstraints) return;
  const caps = track.getCapabilities();
  if (!caps.torch) return;
  torchOn = !torchOn;
  await track.applyConstraints({ advanced: [{ torch: torchOn }] });
  if (torchBtn) {
    torchBtn.classList.toggle('active', torchOn);
    torchBtn.setAttribute('aria-pressed', torchOn ? 'true' : 'false');
  }
}

async function refocus() {
  const enableBtn = () => {
    if (refocusBtn) refocusBtn.disabled = false;
  };

  if (refocusBtn) refocusBtn.disabled = true;

  try {
    if (!stream) {
      await start(devices[currentDeviceIndex]?.deviceId || null);
      return;
    }

    const [track] = stream.getVideoTracks();
    if (track?.getCapabilities && track.applyConstraints) {
      const caps = track.getCapabilities();
      const advanced = [];
      if (caps.focusMode && caps.focusMode.length) {
        const mode = caps.focusMode.includes('continuous') ? 'continuous' : caps.focusMode[0];
        advanced.push({ focusMode: mode });
      }
      if (caps.focusDistance) {
        const { min, max } = caps.focusDistance;
        const mid = typeof min === 'number' && typeof max === 'number' ? (min + max) / 2 : undefined;
        if (typeof mid === 'number') {
          advanced.push({ focusDistance: mid });
        }
      }
      if (advanced.length) {
        await track.applyConstraints({ advanced });
        return;
      }
    }

    await start(track?.getSettings?.().deviceId || devices[currentDeviceIndex]?.deviceId || null);
  } catch (err) {
    console.warn('Refocus failed; restarting stream', err);
    await start(devices[currentDeviceIndex]?.deviceId || null);
  } finally {
    enableBtn();
  }
}

async function init() {
  devices = await enumerateVideoInputs();
  const backIndex = devices.findIndex((d) => /back|rear|environment/i.test((d.label || '').toLowerCase()));
  if (backIndex >= 0) {
    currentDeviceIndex = backIndex;
  } else {
    currentDeviceIndex = 0;
  }
  if (devices.length > 1 && switchBtn) {
    switchBtn.classList.remove('d-none');
    switchBtn.disabled = false;
  } else if (switchBtn) {
    switchBtn.disabled = true;
    switchBtn.classList.add('d-none');
  }
  const initialDeviceId = backIndex >= 0 ? devices[backIndex].deviceId : null;
  await start(initialDeviceId);
}

function syncViewportSizes() {
  const rect = video.getBoundingClientRect();
  const vw = Math.max(1, Math.round(rect.width || config.width));
  const vh = Math.max(1, Math.round(rect.height || config.height));
  canvas.width = vw;
  canvas.height = vh;
  overlay.width = vw;
  overlay.height = vh;
}

if (manualForm) {
  manualForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const tag = manualInput.value.trim();
    if (tag) redirect(tag);
  });
}

if (manualToggle) {
  manualToggle.addEventListener('click', () => {
    manualForm.classList.toggle('d-none');
    if (!manualForm.classList.contains('d-none')) {
      manualInput?.focus();
    }
  });
}

if (switchBtn) {
  switchBtn.addEventListener('click', switchCamera);
}

if (refocusBtn) {
  refocusBtn.addEventListener('click', () => {
    refocus().catch((error) => {
      console.error('Refocus failed', error);
      if (refocusBtn) refocusBtn.disabled = false;
    });
  });
}

if (torchBtn) {
  torchBtn.addEventListener('click', () => {
    toggleTorch().catch((error) => console.error('Torch toggle failed', error));
  });
}

document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    stop();
  } else {
    init();
  }
});

window.addEventListener('beforeunload', () => {
  stop();
});

window.addEventListener('resize', () => {
  if (stream) {
    syncViewportSizes();
  }
});

init().catch((error) => {
  console.error('Failed to initialise scanner', error);
  showError('Unable to access camera');
  showPermissionBanner();
  showManual();
});

export { start, stop, config };
