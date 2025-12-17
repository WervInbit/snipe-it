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
const scanArea = document.getElementById('scan-area');
const switchBtn = document.getElementById('scan-switch');
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
let scrolledToCamera = false;
let currentDeviceId = null;
const scrollOffset = 12; // px to keep header/nav visible
const MIN_SCAN_HEIGHT = 240;

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

    resizeScanArea();
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
              resizeScanArea();
              syncViewportSizes();
            } catch (e) {
              // ignore and keep going
            }
          }
        }
      }
    });

    hintTimer = setTimeout(showHint, 10_000);
    if (!scrolledToCamera && scanArea) {
      scrolledToCamera = true;
      const rect = scanArea.getBoundingClientRect();
      const targetY = window.pageYOffset + rect.top - scrollOffset;
      window.scrollTo({ top: Math.max(0, targetY), behavior: 'smooth' });
    }
  } catch (err) {
    console.error('Unable to access camera', err);
    showError('Unable to access camera');
    showPermissionBanner();
  }
}

async function switchCamera() {
  if (devices.length <= 1) {
    await start(currentDeviceId);
    return;
  }
  currentDeviceIndex = (currentDeviceIndex + 1) % devices.length;
  currentDeviceId = devices[currentDeviceIndex].deviceId;
  await start(currentDeviceId);
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
  try {
    if (!stream) {
      await start(currentDeviceId);
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

    await start(track?.getSettings?.().deviceId || currentDeviceId);
  } catch (err) {
    console.warn('Refocus failed; restarting stream', err);
    await start(currentDeviceId);
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
  if (switchBtn) {
    switchBtn.disabled = false;
  }
  const initialDeviceId = backIndex >= 0 ? devices[backIndex].deviceId : devices[0]?.deviceId || null;
  currentDeviceId = initialDeviceId;
  await start(initialDeviceId);
}

function syncViewportSizes() {
  resizeScanArea();
  const rect = video.getBoundingClientRect();
  const vw = Math.max(1, Math.round(rect.width || config.width));
  const vh = Math.max(1, Math.round(rect.height || config.height));
  canvas.width = vw;
  canvas.height = vh;
  overlay.width = vw;
  overlay.height = vh;
}

function resizeScanArea() {
  if (!scanArea || !video) return;
  const track = stream?.getVideoTracks?.()[0];
  const settings = track?.getSettings?.() || {};
  const widthSetting = settings.width || video.videoWidth || config.width;
  const heightSetting = settings.height || video.videoHeight || config.height;
  const aspect = heightSetting && widthSetting ? heightSetting / widthSetting : config.height / config.width;
  const containerWidth = Math.max(1, scanArea.clientWidth || config.width);
  const idealHeight = Math.round(containerWidth * aspect);
  const maxHeight = Math.max(MIN_SCAN_HEIGHT, Math.round(window.innerHeight * 0.7));
  const height = Math.min(maxHeight, Math.max(MIN_SCAN_HEIGHT, idealHeight));
  scanArea.style.height = `${height}px`;
}

if (switchBtn) {
  switchBtn.addEventListener('click', switchCamera);
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

if (video) {
  video.addEventListener('loadedmetadata', () => {
    resizeScanArea();
    syncViewportSizes();
  });
}

init().catch((error) => {
  console.error('Failed to initialise scanner', error);
  showError('Unable to access camera');
  showPermissionBanner();
});

export { start, stop, config };
