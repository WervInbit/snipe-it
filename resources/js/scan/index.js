import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

const defaults = {
  width: 1280,
  height: 720,
  interval: 100,
  beep: true,
  noCodeDelay: 6000,
  navigationRecoveryDelay: 4000,
};

const config = Object.assign({}, defaults, window.scanConfig || {});
const text = Object.assign({
  selectCamera: 'Select camera',
  cameraLabel: 'Camera',
  cameraAccessFailed: 'Unable to access camera',
  cameraUnavailable: 'Camera not available',
  cameraBusy: 'The camera is already in use by another application.',
  permissionHelp: 'Allow camera access and retry, or use manual search.',
  statusStarting: 'Starting camera...',
  statusScanning: 'Camera ready. Hold the code steady inside the frame.',
  statusNoCode: 'No code detected yet. If the image is blurry, move the camera back slowly.',
  statusRetrying: 'Restarting camera...',
  statusRefocusing: 'Refocusing camera...',
  statusSuccess: 'Code detected. Opening record...',
  statusPaused: 'Camera paused. Retry when you are ready.',
  navigationFailed: 'The record did not open. Retry the camera or use manual search.',
}, config.text || {});

const video = document.getElementById('scan-video');
const overlay = document.getElementById('scan-overlay');
const errorEl = document.getElementById('scan-error');
const permissionBanner = document.getElementById('scan-permission');
const permissionText = document.getElementById('scan-permission-text');
const scanArea = document.getElementById('scan-area');
const retryBtn = document.getElementById('scan-switch');
const refocusBtn = document.getElementById('scan-refocus');
const torchBtn = document.getElementById('scan-torch');
const cameraSelect = document.getElementById('scan-camera-select');
const requestBtn = document.getElementById('scan-request');
const hintBanner = document.getElementById('scan-hint');
const successBanner = document.getElementById('scan-success');
const statusEl = document.getElementById('scan-status');
const statusText = document.getElementById('scan-status-text');
const overlayContext = overlay?.getContext('2d') || null;

let scannerControls = null;
let noCodeTimer = null;
let navigationTimer = null;
let navigationRecoveryTimer = null;
let devices = [];
let currentDeviceIndex = 0;
let currentDeviceId = null;
let torchOn = false;
let torchSupported = false;
let scanLocked = false;
let starting = false;
let scanSession = 0;
let startRequest = 0;
let startQueue = Promise.resolve();
let scrolledToCamera = false;

const scrollOffset = 12;

function setVisible(element, visible) {
  if (!element) return;
  element.classList.toggle('d-none', !visible);
  element.style.display = visible ? '' : 'none';
}

function setStatus(state, message) {
  if (scanArea) {
    scanArea.dataset.scanState = state;
  }

  if (!statusEl || !statusText) return;

  statusEl.dataset.state = state;
  statusEl.classList.remove('alert-info', 'alert-warning', 'alert-success', 'alert-danger');

  const className = {
    error: 'alert-danger',
    'no-code': 'alert-warning',
    permission: 'alert-warning',
    success: 'alert-success',
  }[state] || 'alert-info';

  statusEl.classList.add(className);
  statusText.textContent = message;
}

function showError(message) {
  if (errorEl) {
    errorEl.textContent = message;
    setVisible(errorEl, true);
  }
  setStatus('error', message);
}

function hideError() {
  if (!errorEl) return;
  errorEl.textContent = '';
  setVisible(errorEl, false);
}

function showPermissionBanner(message = text.permissionHelp) {
  if (permissionText) {
    permissionText.textContent = message;
  }
  setVisible(permissionBanner, true);
}

function hidePermissionBanner() {
  setVisible(permissionBanner, false);
}

function showHint() {
  setVisible(hintBanner, true);
}

function hideHint() {
  setVisible(hintBanner, false);
}

function showSuccess() {
  if (successBanner) {
    successBanner.textContent = text.statusSuccess;
  }
  setVisible(successBanner, true);
  setStatus('success', text.statusSuccess);
}

function hideSuccess() {
  setVisible(successBanner, false);
}

function clearTimers() {
  if (noCodeTimer) window.clearTimeout(noCodeTimer);
  if (navigationTimer) window.clearTimeout(navigationTimer);
  if (navigationRecoveryTimer) window.clearTimeout(navigationRecoveryTimer);
  noCodeTimer = null;
  navigationTimer = null;
  navigationRecoveryTimer = null;
}

function updateControls(activeStream = false) {
  if (retryBtn) retryBtn.disabled = starting;
  if (requestBtn) requestBtn.disabled = starting;
  if (cameraSelect) cameraSelect.disabled = starting || devices.length === 0;
  if (refocusBtn) refocusBtn.disabled = starting || !activeStream;
  if (torchBtn) torchBtn.disabled = starting || !activeStream || !torchSupported;
}

function disableControlsForNavigation() {
  [retryBtn, requestBtn, cameraSelect, refocusBtn, torchBtn].forEach((control) => {
    if (control) control.disabled = true;
  });
}

function beep() {
  if (!config.beep) return;

  const AudioContextClass = window.AudioContext || window.webkitAudioContext;
  if (!AudioContextClass) return;

  try {
    const audioContext = new AudioContextClass();
    const oscillator = audioContext.createOscillator();
    oscillator.type = 'sine';
    oscillator.frequency.value = 600;
    oscillator.connect(audioContext.destination);
    oscillator.onended = () => {
      audioContext.close().catch(() => {});
    };
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.15);
  } catch (error) {
    // Audio feedback is optional and may be blocked until the user interacts.
  }
}

function clearOverlay() {
  if (!overlay || !overlayContext) return;
  overlayContext.clearRect(0, 0, overlay.width, overlay.height);
}

function syncViewportSizes() {
  if (!overlay || !video) return;

  const rect = scanArea?.getBoundingClientRect?.() || video.getBoundingClientRect();
  overlay.width = Math.max(1, Math.round(video.videoWidth || rect.width || config.width));
  overlay.height = Math.max(1, Math.round(video.videoHeight || rect.height || config.height));
}

function clearAssetSearch(tag) {
  const keys = ['assetsListingTable.bs.table.searchText'];
  const storages = [];

  ['localStorage', 'sessionStorage'].forEach((storageName) => {
    try {
      if (window[storageName]) {
        storages.push(window[storageName]);
      }
    } catch (error) {
      // Storage can be blocked in privacy modes; scanning must still continue.
    }
  });

  storages.forEach((store) => {
    keys.forEach((key) => {
      try {
        const value = store.getItem(key);
        if (value && (!tag || value === tag)) {
          store.removeItem(key);
        }
      } catch (error) {
        // Storage cleanup is best effort and must never block navigation.
      }
    });
  });

  keys.forEach((key) => {
    try {
      const match = document.cookie.match(new RegExp('(?:^|; )' + key.replace(/\./g, '\\.') + '=([^;]*)'));
      const value = match ? decodeURIComponent(match[1]) : null;
      if (value && (!tag || value === tag)) {
        document.cookie = `${key}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
      }
    } catch (error) {
      // Cookie cleanup is best effort and must never block navigation.
    }
  });
}

function buildResolveUrl(code) {
  const basePath = config.resolveBasePath || '/scan/resolve';
  const normalizedBasePath = basePath.endsWith('/') ? basePath.slice(0, -1) : basePath;
  const url = new URL(`${normalizedBasePath}/${encodeURIComponent(code)}`, window.location.origin);

  Object.entries(config.resolveQuery || {}).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      url.searchParams.set(key, value);
    }
  });

  return url.toString();
}

function redirect(code) {
  window.location.assign(buildResolveUrl(code));
}

function activeStream() {
  const source = video?.srcObject;
  return source && typeof source.getTracks === 'function' ? source : null;
}

function stopActiveScanner({
  invalidate = true,
  clearVideo = true,
  cancelPending = true,
} = {}) {
  if (invalidate) {
    scanSession += 1;
  }
  if (cancelPending) {
    startRequest += 1;
  }

  clearTimers();

  const controls = scannerControls;
  scannerControls = null;
  if (controls?.stop) {
    try {
      controls.stop();
    } catch (error) {
      // The stream can already be stopped by the browser during navigation.
    }
  }

  const stream = activeStream();
  if (stream) {
    stream.getTracks().forEach((track) => track.stop());
  }

  if (clearVideo && video) {
    video.srcObject = null;
  }

  torchOn = false;
  torchSupported = false;
  if (torchBtn) {
    torchBtn.classList.remove('active');
    torchBtn.setAttribute('aria-pressed', 'false');
  }
  clearOverlay();
}

async function enumerateVideoInputs() {
  if (!navigator.mediaDevices?.enumerateDevices) {
    return [];
  }

  const allDevices = await navigator.mediaDevices.enumerateDevices();
  return allDevices.filter((device) => device.kind === 'videoinput');
}

function renderCameraOptions(selectedId = null) {
  if (!cameraSelect) return;

  const placeholder = cameraSelect.querySelector('option[value=""]');
  const placeholderOption = placeholder ? placeholder.cloneNode(true) : document.createElement('option');
  placeholderOption.value = '';
  placeholderOption.textContent = text.selectCamera;

  cameraSelect.innerHTML = '';
  cameraSelect.appendChild(placeholderOption);

  devices.forEach((device, index) => {
    const option = document.createElement('option');
    option.value = device.deviceId || '';
    option.textContent = device.label || `${text.cameraLabel} ${index + 1}`;
    option.selected = Boolean(selectedId && selectedId === device.deviceId);
    cameraSelect.appendChild(option);
  });

  updateControls(Boolean(activeStream()));
}

function createVideoConstraints(deviceId) {
  const constraints = {
    width: { ideal: config.width },
    height: { ideal: config.height },
  };

  if (deviceId) {
    constraints.deviceId = { exact: deviceId };
  } else {
    constraints.facingMode = { ideal: 'environment' };
  }

  return constraints;
}

async function applySupportedContinuousFocus(track) {
  if (!track?.getCapabilities || !track.applyConstraints) {
    return false;
  }

  const capabilities = track.getCapabilities();
  const focusModes = Array.isArray(capabilities.focusMode) ? capabilities.focusMode : [];
  if (!focusModes.includes('continuous')) {
    return false;
  }

  try {
    await track.applyConstraints({
      advanced: [{ focusMode: 'continuous' }],
    });
    return true;
  } catch (error) {
    return false;
  }
}

function updateTrackCapabilities(track) {
  torchSupported = false;

  if (track?.getCapabilities) {
    const capabilities = track.getCapabilities();
    torchSupported = capabilities.torch === true;
  }

  updateControls(Boolean(track));
}

function cameraErrorMessage(error) {
  if (['NotFoundError', 'DevicesNotFoundError', 'OverconstrainedError'].includes(error?.name)) {
    return text.cameraUnavailable;
  }

  if (['NotReadableError', 'TrackStartError', 'AbortError'].includes(error?.name)) {
    return text.cameraBusy;
  }

  return text.cameraAccessFailed;
}

function scheduleNoCodeFeedback(session) {
  if (noCodeTimer) {
    window.clearTimeout(noCodeTimer);
  }

  noCodeTimer = window.setTimeout(() => {
    if (session !== scanSession || scanLocked || !activeStream()) {
      return;
    }

    setStatus('no-code', text.statusNoCode);
    showHint();
  }, config.noCodeDelay);
}

function recoverFromNavigationFailure() {
  scanLocked = false;
  starting = false;
  hideSuccess();
  showError(text.navigationFailed);
  updateControls(false);
}

function handleDecodeResult(result, session) {
  if (session !== scanSession || scanLocked || !result) {
    return;
  }

  const code = String(result.getText?.() || '').trim();
  if (!code) {
    return;
  }

  scanLocked = true;
  clearTimers();
  hideHint();
  clearAssetSearch(code);
  beep();
  showSuccess();
  disableControlsForNavigation();
  stopActiveScanner({ invalidate: true });

  navigationTimer = window.setTimeout(() => {
    try {
      redirect(code);
    } catch (error) {
      recoverFromNavigationFailure();
    }
  }, 150);

  navigationRecoveryTimer = window.setTimeout(() => {
    if (!document.hidden) {
      recoverFromNavigationFailure();
    }
  }, config.navigationRecoveryDelay);
}

async function refreshDeviceList(selectedId = null) {
  try {
    devices = await enumerateVideoInputs();
  } catch (error) {
    devices = [];
  }

  const resolvedId = selectedId || currentDeviceId;
  const activeIndex = devices.findIndex((device) => device.deviceId === resolvedId);
  if (activeIndex >= 0) {
    currentDeviceIndex = activeIndex;
    currentDeviceId = devices[activeIndex].deviceId;
  }

  renderCameraOptions(currentDeviceId);
}

async function startNow(deviceId = null, state = 'starting') {
  const session = scanSession + 1;
  scanSession = session;
  stopActiveScanner({ invalidate: false, cancelPending: false });

  starting = true;
  scanLocked = false;
  currentDeviceId = deviceId || currentDeviceId;
  hideError();
  hidePermissionBanner();
  hideHint();
  hideSuccess();
  updateControls(false);
  setStatus(state, state === 'retrying' ? text.statusRetrying : text.statusStarting);

  if (!video || !navigator.mediaDevices?.getUserMedia) {
    starting = false;
    showError(text.cameraUnavailable);
    showPermissionBanner(text.permissionHelp);
    updateControls(false);
    return;
  }

  const hints = new Map();
  hints.set(DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.QR_CODE]);
  hints.set(DecodeHintType.TRY_HARDER, true);
  if (typeof DecodeHintType.ALSO_INVERTED !== 'undefined') {
    hints.set(DecodeHintType.ALSO_INVERTED, true);
  }

  const localReader = new BrowserMultiFormatReader(hints, {
    delayBetweenScanAttempts: config.interval,
    delayBetweenScanSuccess: 500,
  });

  try {
    const controls = await localReader.decodeFromConstraints({
      video: createVideoConstraints(currentDeviceId),
      audio: false,
    }, video, (result) => {
      handleDecodeResult(result, session);
    });

    if (session !== scanSession) {
      controls.stop();
      return;
    }

    scannerControls = controls;
    const stream = activeStream();
    const track = stream?.getVideoTracks?.()[0] || null;
    const activeId = track?.getSettings?.().deviceId || currentDeviceId;
    currentDeviceId = activeId || null;

    await applySupportedContinuousFocus(track);
    if (session !== scanSession) {
      controls.stop();
      return;
    }

    updateTrackCapabilities(track);
    syncViewportSizes();
    setStatus('scanning', text.statusScanning);
    scheduleNoCodeFeedback(session);
    await refreshDeviceList(currentDeviceId);
    if (session !== scanSession) {
      controls.stop();
      return;
    }

    starting = false;
    updateControls(Boolean(track));

    if (!scrolledToCamera && scanArea) {
      scrolledToCamera = true;
      const rect = scanArea.getBoundingClientRect();
      const targetY = window.pageYOffset + rect.top - scrollOffset;
      window.scrollTo({ top: Math.max(0, targetY), behavior: 'smooth' });
    }
  } catch (error) {
    if (session !== scanSession) {
      return;
    }

    starting = false;
    stopActiveScanner({ invalidate: false, cancelPending: false });
    if (['NotFoundError', 'DevicesNotFoundError', 'OverconstrainedError'].includes(error?.name)) {
      currentDeviceId = null;
      await refreshDeviceList();
    }
    if (session !== scanSession) {
      return;
    }
    const message = cameraErrorMessage(error);
    showError(message);
    showPermissionBanner(text.permissionHelp);
    updateControls(false);
  }
}

function start(deviceId = null, state = 'starting') {
  const request = startRequest + 1;
  startRequest = request;

  stopActiveScanner({ cancelPending: false });
  starting = true;
  updateControls(false);
  setStatus(state, state === 'retrying' ? text.statusRetrying : text.statusStarting);

  startQueue = startQueue
    .catch(() => {})
    .then(() => {
      if (request !== startRequest || document.hidden) {
        return;
      }

      return startNow(deviceId, state);
    });

  return startQueue;
}

async function retryCamera() {
  await start(currentDeviceId, 'retrying');
}

async function refocus() {
  const stream = activeStream();
  const track = stream?.getVideoTracks?.()[0] || null;

  if (!track) {
    await retryCamera();
    return;
  }

  setStatus('retrying', text.statusRefocusing);
  hideHint();

  const focused = await applySupportedContinuousFocus(track);
  if (!focused) {
    await retryCamera();
    return;
  }

  setStatus('scanning', text.statusScanning);
  scheduleNoCodeFeedback(scanSession);
}

async function toggleTorch() {
  const track = activeStream()?.getVideoTracks?.()[0] || null;
  if (!track?.getCapabilities || !track.applyConstraints) return;

  const capabilities = track.getCapabilities();
  if (capabilities.torch !== true) return;

  const nextTorchState = !torchOn;
  await track.applyConstraints({ advanced: [{ torch: nextTorchState }] });
  torchOn = nextTorchState;
  if (torchBtn) {
    torchBtn.classList.toggle('active', torchOn);
    torchBtn.setAttribute('aria-pressed', torchOn ? 'true' : 'false');
  }
}

async function init() {
  setStatus('starting', text.statusStarting);
  await refreshDeviceList();

  const preferredIndex = devices.findIndex((device) => /back|rear|environment/i.test(device.label || ''));
  currentDeviceIndex = preferredIndex >= 0 ? preferredIndex : 0;
  currentDeviceId = devices[currentDeviceIndex]?.deviceId || null;
  renderCameraOptions(currentDeviceId);
  await start(currentDeviceId);
}

if (retryBtn) {
  retryBtn.addEventListener('click', () => {
    retryCamera().catch((error) => showError(cameraErrorMessage(error)));
  });
}

if (refocusBtn) {
  refocusBtn.addEventListener('click', () => {
    refocus().catch((error) => showError(cameraErrorMessage(error)));
  });
}

if (torchBtn) {
  torchBtn.addEventListener('click', () => {
    toggleTorch().catch((error) => {
      torchSupported = false;
      updateControls(Boolean(activeStream()));
      console.warn('Torch control is no longer available', error);
    });
  });
}

if (cameraSelect) {
  cameraSelect.addEventListener('change', (event) => {
    const selectedId = event.target.value || null;
    const selectedIndex = devices.findIndex((device) => device.deviceId === selectedId);
    currentDeviceIndex = selectedIndex >= 0 ? selectedIndex : 0;
    currentDeviceId = selectedId || devices[currentDeviceIndex]?.deviceId || null;
    start(currentDeviceId, 'retrying').catch((error) => showError(cameraErrorMessage(error)));
  });
}

if (requestBtn) {
  requestBtn.addEventListener('click', () => {
    retryCamera().catch((error) => showError(cameraErrorMessage(error)));
  });
}

document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    stopActiveScanner();
    starting = false;
    scanLocked = false;
    hideHint();
    hideSuccess();
    setStatus('paused', text.statusPaused);
    updateControls(false);
    return;
  }

  retryCamera().catch((error) => showError(cameraErrorMessage(error)));
});

window.addEventListener('beforeunload', () => {
  stopActiveScanner();
});

window.addEventListener('resize', () => {
  if (activeStream()) {
    syncViewportSizes();
  }
});

if (video) {
  video.addEventListener('loadedmetadata', syncViewportSizes);

  init().catch((error) => {
    starting = false;
    stopActiveScanner({ invalidate: false });
    showError(cameraErrorMessage(error));
    showPermissionBanner(text.permissionHelp);
    updateControls(false);
  });
}

export {
  applySupportedContinuousFocus,
  buildResolveUrl,
  config,
  createVideoConstraints,
  start,
  stopActiveScanner as stop,
};
