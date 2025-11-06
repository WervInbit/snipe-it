import jsQR from 'jsqr';

const defaults = { width: 640, height: 360, interval: 100, beep: true };
const config = Object.assign({}, defaults, window.scanConfig || {});

const video = document.getElementById('scan-video');
const overlay = document.getElementById('scan-overlay');
const errorEl = document.getElementById('scan-error');
const permissionBanner = document.getElementById('scan-permission');
const manualForm = document.getElementById('manual-form');
const manualInput = document.getElementById('manual-tag');
const manualToggle = document.getElementById('manual-toggle');
const switchBtn = document.getElementById('scan-switch');
const hintBanner = document.getElementById('scan-hint');

const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
const octx = overlay.getContext('2d');

let stream = null;
let timer = null;
let hintTimer = null;
let devices = [];
let currentDeviceIndex = 0;

function showError(msg) {
  if (!errorEl) return;
  errorEl.textContent = msg;
  errorEl.classList.remove('d-none');
}

function hideError() {
  if (!errorEl) return;
  errorEl.classList.add('d-none');
  errorEl.textContent = '';
}

function showManual() {
  if (manualForm) manualForm.classList.remove('d-none');
}

function showPermissionBanner() {
  if (permissionBanner) permissionBanner.classList.remove('d-none');
}

function hidePermissionBanner() {
  if (permissionBanner) permissionBanner.classList.add('d-none');
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
  if (hintBanner) hintBanner.classList.remove('d-none');
}

function hideHint() {
  if (hintBanner) hintBanner.classList.add('d-none');
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

  const constraints = {
    video: deviceId
      ? { deviceId: { exact: deviceId } }
      : { facingMode: 'environment', width: { ideal: config.width }, height: { ideal: config.height } },
    audio: false,
  };

  try {
    stream = await navigator.mediaDevices.getUserMedia(constraints);
    video.srcObject = stream;
    await video.play();
    canvas.width = config.width;
    canvas.height = config.height;
    overlay.width = config.width;
    overlay.height = config.height;
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

async function init() {
  devices = await enumerateVideoInputs();
  if (devices.length > 1 && switchBtn) {
    switchBtn.classList.remove('d-none');
    switchBtn.disabled = false;
  } else if (switchBtn) {
    switchBtn.disabled = true;
    switchBtn.classList.add('d-none');
  }
  await start(devices[currentDeviceIndex]?.deviceId || null);
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

init().catch((error) => {
  console.error('Failed to initialise scanner', error);
  showError('Unable to access camera');
  showPermissionBanner();
  showManual();
});

export { start, stop, config };
