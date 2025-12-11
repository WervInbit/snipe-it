import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

// Low-res first for better SNR at short/mid range; bump once if the code is too small/far.
const defaults = {
  width: 640,
  height: 480,
  interval: 120,
  beep: true,
  fallbackWidth: 1280,
  fallbackHeight: 720,
  failBeforeFallback: 8,
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
let hintTimer = null;
let devices = [];
let currentDeviceIndex = 0;
let torchOn = false;
let reader = null;
let failCount = 0;
let bumped = false;

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
  if (hintTimer) clearTimeout(hintTimer);
  if (reader) {
    try {
      reader.reset();
    } catch (e) {
      // ignore
    }
  }
  if (stream) stream.getTracks().forEach((t) => t.stop());
  stream = null;
  hintTimer = null;
  reader = null;
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
  failCount = 0;
  bumped = false;

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

    const hints = new Map();
    hints.set(DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.QR_CODE]);
    reader = new BrowserMultiFormatReader(hints);

    await reader.decodeFromVideoDevice(deviceId || null, video, async (result, err) => {
      clearOverlay();

      if (result) {
        failCount = 0;
        const points = result.resultPoints || [];
        if (points.length >= 4) {
          for (let i = 0; i < points.length; i++) {
            const p1 = points[i];
            const p2 = points[(i + 1) % points.length];
            drawLine({ x: p1.x, y: p1.y }, { x: p2.x, y: p2.y });
          }
        }
        beep();
        stop();
        redirect(result.getText());
        return;
      }

      if (err) {
        failCount += 1;
        if (!bumped && failCount >= config.failBeforeFallback && config.fallbackWidth && config.fallbackHeight) {
          bumped = true;
          const [track] = stream?.getVideoTracks() || [];
          if (track?.applyConstraints) {
            try {
              await track.applyConstraints({ width: { ideal: config.fallbackWidth }, height: { ideal: config.fallbackHeight } });
              syncViewportSizes();
            } catch (e) {
              // ignore and keep going
            }
          }
        }
      }
    });

    hintTimer = setTimeout(showHint, 10_000);
  } catch (err) {
    console.error('Unable to access camera', err);
    showError('Unable to access camera');
    showPermissionBanner();
    showManual();
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
