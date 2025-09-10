import jsQR from 'jsqr';

// Tested on mobile devices indoors; 480x360 @200ms gave fast scans without overheating.
const defaults = { width: 480, height: 360, interval: 200, beep: true };
const config = Object.assign({}, defaults, window.scanConfig || {});

const video = document.getElementById('scan-video');
const overlay = document.getElementById('scan-overlay');
const startBtn = document.getElementById('scan-start');
const errorEl = document.getElementById('scan-error');
const manualForm = document.getElementById('manual-form');
const manualInput = document.getElementById('manual-tag');

const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
const octx = overlay.getContext('2d');

let stream = null;
let timer = null;

function showError(msg) {
  errorEl.textContent = msg;
  errorEl.classList.remove('d-none');
}

function showManual() {
  if (manualForm) manualForm.classList.remove('d-none');
}

function beep() {
  if (!config.beep || !window.AudioContext) return;
  const ac = new (window.AudioContext || window.webkitAudioContext)();
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

function redirect(tag) {
  window.location.href = `/hardware/bytag/${encodeURIComponent(tag)}`;
}

function stop() {
  if (timer) clearInterval(timer);
  if (stream) stream.getTracks().forEach(t => t.stop());
}

async function start() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    showError('Camera not available');
    showManual();
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment', width: { ideal: config.width }, height: { ideal: config.height } },
      audio: false
    });
    video.srcObject = stream;
    await video.play();
    canvas.width = config.width;
    canvas.height = config.height;
    overlay.width = config.width;
    overlay.height = config.height;
    timer = setInterval(sample, config.interval);
  } catch (err) {
    showError('Unable to access camera');
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

if (startBtn) {
  startBtn.addEventListener('click', () => {
    startBtn.classList.add('d-none');
    start();
  });
}

if (manualForm) {
  manualForm.addEventListener('submit', e => {
    e.preventDefault();
    const tag = manualInput.value.trim();
    if (tag) redirect(tag);
  });
}

export { start, stop, config };
