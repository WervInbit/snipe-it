import * as bootstrap from 'bootstrap';
import debounce from './utils/debounce';

if (!window.bootstrap) {
    window.bootstrap = bootstrap;
}

const { TestsActiveConfig: config } = window;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const toastContainer = document.getElementById('tests-toast-container');
const offlineBanner = document.getElementById('offline-banner');
const completeBtn = document.getElementById('tests-complete-btn');
const repairBtn = document.getElementById('tests-repair-btn');
const progressBar = document.querySelector('.tests-action-bar .progress-bar');
const progressCompletedEl = document.querySelector('[data-progress-completed]');
const progressRemainingEl = document.querySelector('[data-progress-remaining]');
const failuresSummaryEl = document.querySelector('[data-progress-failures]');
const chipButtons = document.querySelectorAll('.chip-button');

const queue = [];
let flushingQueue = false;
const progressState = config?.progress ? { ...config.progress } : { total: 0, completed: 0, remaining: 0, failures: 0 };

function showToast(message, variant = 'success') {
    if (!toastContainer || !message) return;

    const wrapper = document.createElement('div');
    wrapper.className = `toast align-items-center text-bg-${variant}`;
    wrapper.setAttribute('role', 'status');
    wrapper.setAttribute('aria-live', 'polite');
    wrapper.setAttribute('aria-atomic', 'true');
    wrapper.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(wrapper);
    const toast = new bootstrap.Toast(wrapper, { delay: 3000 });
    toast.show();
    wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
}

function setOfflineState(isOffline) {
    if (!offlineBanner) return;
    offlineBanner.classList.toggle('d-none', !isOffline);
}

function buildUpdateUrl(resultId) {
    if (!config?.endpoints?.partialUpdate) {
        return null;
    }
    return config.endpoints.partialUpdate.replace('RESULT_ID', resultId);
}

function buildHeaders(isFormData = false) {
    const headers = {};
    if (!isFormData) {
        headers['Content-Type'] = 'application/json';
    }
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }
    return headers;
}

async function flushQueue() {
    if (flushingQueue || queue.length === 0) return;
    if (!navigator.onLine) return;

    flushingQueue = true;
    setOfflineState(false);

    while (queue.length > 0) {
        const item = queue[0];
        const url = buildUpdateUrl(item.resultId);
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: item.body,
                headers: item.headers,
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Request failed');
            queue.shift();
            const data = await response.json().catch(() => ({}));
            if (data?.message) {
                showToast(data.message, 'success');
            } else {
                showToast(config?.messages?.saved ?? 'Saved', 'success');
            }
        } catch (error) {
            console.error('Failed to flush queued test update', error);
            // Leave in the queue and break so we can retry later.
            setOfflineState(true);
            break;
        }
    }

    flushingQueue = false;
}

function queueUpdate(resultId, payload) {
    const url = buildUpdateUrl(resultId);
    if (!url) return;

    queue.push(payload);
    setOfflineState(true);
    showToast(config?.messages?.queued ?? 'Update queued', 'secondary');
    if (navigator.onLine) {
        flushQueue();
    }
}

async function sendUpdate(resultId, payload) {
    const url = buildUpdateUrl(resultId);
    if (!url) return { ok: false };

    const isFormData = payload instanceof FormData;
    const body = isFormData ? payload : JSON.stringify(payload);
    const headers = buildHeaders(isFormData);

    try {
        const response = await fetch(url, {
            method: 'POST',
            body,
            headers,
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data?.message || response.statusText);
        }

        const data = await response.json().catch(() => ({}));
        if (data?.message) {
            showToast(data.message, 'success');
        } else {
            showToast(config?.messages?.saved ?? 'Saved', 'success');
        }
        return { ok: true, data };
    } catch (error) {
        if (!navigator.onLine || error.message === 'Failed to fetch') {
            if (isFormData) {
                showToast(config?.messages?.photoOffline ?? 'Photo upload not available offline.', 'danger');
                return { ok: false, offline: true };
            }
            queueUpdate(resultId, { resultId, body, headers });
            return { ok: false, queued: true };
        }
        showToast(error.message || 'Save failed', 'danger');
        return { ok: false, error };
    }
}

function updateStatusVisual(card, status) {
    const buttons = card.querySelectorAll('.status-option');
    buttons.forEach((btn) => {
        const btnStatus = btn.dataset.status;
        btn.classList.toggle('active', btnStatus === status);
        btn.setAttribute('aria-pressed', btnStatus === status ? 'true' : 'false');
    });
    const toggle = card.querySelector('.status-toggle');
    if (toggle) {
        toggle.dataset.status = status;
    }
}

function updateNoteVisual(card, hasNote) {
    const button = card.querySelector('[data-comment-toggle]');
    if (!button) return;
    button.classList.toggle('active-chip', hasNote);
}

function updatePhotoVisual(card, photoUrl) {
    const button = card.querySelector('[data-photo-trigger]');
    const container = card.querySelector('[data-photo-container="true"]');
    if (!button || !container) return;
    const img = container.querySelector('img');

    if (photoUrl) {
        button.classList.add('active-chip');
        container.classList.remove('d-none');
        if (img) {
            img.src = photoUrl;
        } else {
            const image = document.createElement('img');
            image.src = photoUrl;
            image.alt = button.textContent.trim();
            container.prepend(image);
        }
    } else {
        button.classList.remove('active-chip');
        if (img) img.remove();
        container.classList.add('d-none');
    }
}

function updateGroupCounts() {
    ['fail', 'open', 'pass'].forEach((groupKey) => {
        const body = document.querySelector(`[data-group-body="${groupKey}"]`);
        const header = document.querySelector(`[data-group-toggle="${groupKey}"]`);
        const count = body ? body.querySelectorAll('.test-card').length : 0;
        if (!header) return;

        const badge = header.querySelector('.badge');
        if (badge) {
            badge.textContent = count;
        }

        if (count === 0) {
            header.classList.add('disabled', 'text-muted');
            header.setAttribute('disabled', 'disabled');
            if (body) {
                body.classList.add('d-none');
                if (!body.querySelector('[data-empty-state]')) {
                    const emptyText = body.dataset.emptyTemplate || 'Nothing here yet.';
                    const empty = document.createElement('div');
                    empty.className = 'text-muted small px-1';
                    empty.dataset.emptyState = 'true';
                    empty.textContent = emptyText;
                    body.appendChild(empty);
                }
            }
        } else {
            header.classList.remove('disabled', 'text-muted');
            header.removeAttribute('disabled');
            if (groupKey !== 'pass' && body) {
                body.classList.remove('d-none');
            }
        }
    });
    updateFailureSummary();
}

function updateFailureSummary() {
    if (!failuresSummaryEl) return;
    const failCards = document.querySelectorAll('[data-group-body="fail"] .test-card');
    if (failCards.length === 0) {
        failuresSummaryEl.classList.add('d-none');
        failuresSummaryEl.textContent = '';
        return;
    }

    const labels = Array.from(failCards).map((card) => card.querySelector('.test-card__label')?.textContent.trim()).filter(Boolean);
    const template = failuresSummaryEl.dataset.template || 'Failing: :list';
    failuresSummaryEl.textContent = template.replace(':list', labels.join(', '));
    failuresSummaryEl.classList.remove('d-none');
}

function moveCardToGroup(card, newStatus) {
    let targetGroup = 'open';
    if (newStatus === 'fail') targetGroup = 'fail';
    if (newStatus === 'pass') targetGroup = 'pass';

    const body = document.querySelector(`[data-group-body="${targetGroup}"]`);
    if (!body) return;

    const emptyState = body.querySelector('[data-empty-state]');
    if (emptyState) {
        emptyState.remove();
    }

    body.appendChild(card);
    if (targetGroup === 'pass') {
        const header = document.querySelector('[data-group-toggle="pass"]');
        if (header?.getAttribute('aria-expanded') === 'true') {
            body.classList.remove('d-none');
        } else {
            body.classList.add('d-none');
        }
    }

    updateGroupCounts();
}

function updateProgressCounts(oldStatus, newStatus) {
    if (oldStatus === newStatus) return;

    const isCompleteStatus = (status) => status === 'pass' || status === 'fail';

    if (oldStatus === 'nvt' && isCompleteStatus(newStatus)) {
        progressState.completed += 1;
        progressState.remaining = Math.max(0, progressState.remaining - 1);
    } else if (isCompleteStatus(oldStatus) && newStatus === 'nvt') {
        progressState.completed = Math.max(0, progressState.completed - 1);
        progressState.remaining += 1;
    }

    if (oldStatus === 'fail') {
        progressState.failures = Math.max(0, progressState.failures - 1);
    }
    if (newStatus === 'fail') {
        progressState.failures += 1;
    }
}

function refreshProgressUI() {
    if (progressCompletedEl) {
        const template = progressCompletedEl.dataset.template || ':completed / :total';
        progressCompletedEl.textContent = template
            .replace(':completed', progressState.completed)
            .replace(':total', progressState.total);
    }

    if (progressRemainingEl) {
        const template = progressRemainingEl.dataset.template || ':remaining remaining';
        progressRemainingEl.textContent = template.replace(':remaining', progressState.remaining);
    }

    if (progressBar) {
        const width = progressState.total
            ? Math.round((progressState.completed / Math.max(1, progressState.total)) * 100)
            : 0;
        progressBar.style.width = `${width}%`;
        progressBar.setAttribute('aria-valuenow', progressState.completed);
        progressBar.setAttribute('aria-valuemax', progressState.total);
    }

    if (completeBtn) {
        const disabled = !(progressState.remaining === 0 && progressState.failures === 0);
        completeBtn.disabled = disabled;
        completeBtn.classList.toggle('disabled', disabled);
    }

    if (repairBtn) {
        const disabled = progressState.failures === 0;
        repairBtn.disabled = disabled;
        repairBtn.classList.toggle('disabled', disabled);
    }
}

function initialiseGroupToggles() {
    document.querySelectorAll('[data-group-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.dataset.groupToggle;
            const body = document.querySelector(`[data-group-body="${group}"]`);
            if (!body || button.classList.contains('disabled')) return;
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            body.classList.toggle('d-none', expanded);
        });
    });
}

function initialiseCard(card) {
    const initialStatus = card.dataset.initialStatus || 'nvt';
    card.dataset.currentStatus = initialStatus;
    updateStatusVisual(card, initialStatus);

    const noteValue = card.querySelector('[data-note-input]')?.value.trim();
    if (noteValue) {
        const noteContainer = card.querySelector('[data-note-container="true"]');
        if (noteContainer) noteContainer.classList.remove('d-none');
        updateNoteVisual(card, true);
    }

    const photoContainer = card.querySelector('[data-photo-container="true"]');
    if (photoContainer && !photoContainer.classList.contains('d-none')) {
        updatePhotoVisual(card, photoContainer.querySelector('img')?.src || null);
    }

    card.querySelectorAll('.status-option').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!config?.canUpdate) return;
            const newStatus = button.dataset.status;
            const previousStatus = card.dataset.currentStatus;
            if (!newStatus || !previousStatus) return;

            card.dataset.currentStatus = newStatus;
            updateStatusVisual(card, newStatus);
            const oldStatus = previousStatus;
            moveCardToGroup(card, newStatus);
            updateGroupCounts();
            updateProgressCounts(oldStatus, newStatus);
            refreshProgressUI();

            const response = await sendUpdate(card.dataset.resultId, { status: newStatus });
            if (!response.ok && !response.queued) {
                // Revert
                card.dataset.currentStatus = oldStatus;
                updateStatusVisual(card, oldStatus);
                moveCardToGroup(card, oldStatus);
                updateGroupCounts();
                updateProgressCounts(newStatus, oldStatus);
                refreshProgressUI();
            }
        });
    });

    const instructionsToggle = card.querySelector('[data-instructions-toggle]');
    if (instructionsToggle) {
        const instructions = card.querySelector('.test-card__instructions');
        instructionsToggle.addEventListener('click', () => {
            if (!instructions) return;
            const hidden = instructions.classList.contains('d-none');
            instructions.classList.toggle('d-none', !hidden);
            instructionsToggle.setAttribute('aria-expanded', hidden ? 'true' : 'false');
        });
    }

    const noteToggle = card.querySelector('[data-comment-toggle]');
    const noteContainer = card.querySelector('[data-note-container="true"]');
    const noteInput = card.querySelector('[data-note-input]');
    if (noteToggle && noteContainer && noteInput) {
        noteToggle.addEventListener('click', () => {
            const hidden = noteContainer.classList.contains('d-none');
            noteContainer.classList.toggle('d-none', !hidden);
            if (hidden) {
                noteInput.focus();
            }
        });

        const debouncedSave = debounce(async (value) => {
            updateNoteVisual(card, value.trim().length > 0);
            await sendUpdate(card.dataset.resultId, { note: value });
        }, 600);

        noteInput.addEventListener('input', (event) => {
            debouncedSave(event.target.value);
        });
    }

    const photoTrigger = card.querySelector('[data-photo-trigger]');
    const photoInput = card.querySelector('[data-photo-input]');
    const photoRemove = card.querySelector('[data-photo-remove]');

    if (photoTrigger && photoInput) {
        photoTrigger.addEventListener('click', () => {
            photoInput.click();
        });

        photoInput.addEventListener('change', async () => {
            const file = photoInput.files?.[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('photo', file);

            const response = await sendUpdate(card.dataset.resultId, formData);
            if (response.ok && response.data?.photo_url) {
                updatePhotoVisual(card, response.data.photo_url);
                showToast(config?.messages?.photoReplaced ?? 'Photo replaced', 'success');
                let removeBtn = card.querySelector('[data-photo-remove]');
                if (!removeBtn) {
                    removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-link text-danger p-0 small';
                    removeBtn.dataset.photoRemove = 'true';
                    removeBtn.textContent = config?.messages?.removePhoto ?? 'Remove photo';
                    card.querySelector('[data-photo-container="true"]')?.appendChild(removeBtn);
                    removeBtn.addEventListener('click', async () => {
                        const resp = await sendUpdate(card.dataset.resultId, { remove_photo: true });
                        if (resp.ok) {
                            updatePhotoVisual(card, null);
                            removeBtn.remove();
                        }
                    });
                }
            } else if (!response.ok) {
                photoInput.value = '';
            }
        });
    }

    if (photoRemove) {
        photoRemove.addEventListener('click', async () => {
            const response = await sendUpdate(card.dataset.resultId, { remove_photo: true });
            if (response.ok) {
                updatePhotoVisual(card, null);
                photoRemove.remove();
            }
        });
    }
}

function initialiseCards() {
    document.querySelectorAll('.test-card').forEach((card) => {
        card.dataset.resultId = card.dataset.resultId || card.getAttribute('data-result-id');
        initialiseCard(card);
    });
    updateGroupCounts();
    refreshProgressUI();
}

function initialiseActions() {
    if (completeBtn && config?.actions?.completeUrl) {
        completeBtn.addEventListener('click', () => {
            if (completeBtn.classList.contains('disabled')) return;
            window.location.href = config.actions.completeUrl;
        });
    }

    if (repairBtn && config?.actions?.repairUrl) {
        repairBtn.addEventListener('click', () => {
            if (repairBtn.classList.contains('disabled')) return;
            window.location.href = config.actions.repairUrl;
        });
    }
}

if (config?.runId) {
    window.addEventListener('online', () => {
        setOfflineState(false);
        flushQueue();
    });

    window.addEventListener('offline', () => {
        setOfflineState(true);
    });

    document.addEventListener('DOMContentLoaded', () => {
        initialiseGroupToggles();
        initialiseCards();
        initialiseActions();
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/tests-sw.js').catch((error) => {
                console.error('Failed to register test service worker', error);
            });
        }
    });
}
