import * as bootstrap from 'bootstrap';
import debounce from './utils/debounce';

if (!window.bootstrap) {
    window.bootstrap = bootstrap;
}

const config = window.TestsActiveConfig || {};
const getBootstrapNamespace = () => window.bootstrap || bootstrap || null;
const getJquery = () => window.jQuery || window.$ || null;

const createModalController = (element) => {
    if (!element) return null;
    const bootstrapNs = getBootstrapNamespace();
    if (bootstrapNs?.Modal && typeof bootstrapNs.Modal === 'function') {
        try {
            return new bootstrapNs.Modal(element);
        } catch (error) {
            console.warn('Failed to instantiate bootstrap.Modal, falling back to jQuery plugin', error);
        }
    }

    const $ = getJquery();
    if ($?.fn?.modal) {
        const $el = $(element);
        return {
            show: () => $el.modal('show'),
            hide: () => $el.modal('hide'),
        };
    }

    return null;
};

const getCollapseController = (element) => {
    if (!element) return null;
    const bootstrapNs = getBootstrapNamespace();
    if (bootstrapNs?.Collapse?.getOrCreateInstance) {
        return bootstrapNs.Collapse.getOrCreateInstance(element, { toggle: false });
    }

    const $ = getJquery();
    if ($?.fn?.collapse) {
        const $el = $(element);
        return {
            toggle: () => $el.collapse('toggle'),
            show: () => $el.collapse('show'),
            hide: () => $el.collapse('hide'),
        };
    }

    return null;
};

const bootTestsActiveUI = () => {
    if (window.TestsActiveUIBootstrapped || !config.runId) {
        return;
    }

    const grid = document.getElementById('testGrid');
    if (!grid) {
        return;
    }

    window.TestsActiveUIBootstrapped = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const indicator = document.getElementById('saveIndicator');
    const indicatorIcons = indicator
        ? {
              saving: indicator.querySelector('[data-state="saving"]'),
              clean: indicator.querySelector('[data-state="clean"]'),
              error: indicator.querySelector('[data-state="error"]'),
          }
        : {};
    let pendingSaves = 0;
    let openDrawerId = null;

    const photoDeleteModalEl = document.getElementById('photoDeleteModal');
    const photoDeleteModal = createModalController(photoDeleteModalEl);
    const photoViewerModalEl = document.getElementById('photoViewerModal');
    const photoViewerModal = createModalController(photoViewerModalEl);
    const viewerImg = document.getElementById('viewerImg');
    const deleteConfirmBtn = document.getElementById('confirmPhotoDeleteBtn');
    let deleteContext = null;

    const progressState = {
        total: Number(config.progress?.total ?? 0),
        completed: Number(config.progress?.completed ?? 0),
        remaining: Number(config.progress?.remaining ?? 0),
        failures: Number(config.progress?.failures ?? 0),
        blockingFailures: Number(
            config.progress?.blocking_failures ?? config.progress?.blockingFailures ?? config.progress?.failures ?? 0
        ),
    };

    const progressBar = document.querySelector('[data-progress-bar]');
    const progressCompletedEls = document.querySelectorAll('[data-progress-completed]');
    const progressRemainingEls = document.querySelectorAll('[data-progress-remaining]');
    const failuresSummaryEls = document.querySelectorAll('[data-progress-failures]');
    const completeBtn = document.getElementById('tests-complete-btn');
    const completeConfirmModalEl = document.getElementById('testsCompleteConfirmModal');
    const completeConfirmModal = createModalController(completeConfirmModalEl);
    const completeConfirmContinue = document.getElementById('testsCompleteConfirmContinue');
    const completeConfirmFailedBlock = completeConfirmModalEl?.querySelector('[data-tests-complete-failed-block]');
    const completeConfirmFailedList = completeConfirmModalEl?.querySelector('[data-tests-complete-failed]');
    const completeConfirmIncompleteBlock = completeConfirmModalEl?.querySelector('[data-tests-complete-incomplete-block]');
    const completeConfirmIncompleteList = completeConfirmModalEl?.querySelector('[data-tests-complete-incomplete]');

    const noteMessageTemplate = config.messages?.noteSaved ?? '';
    const photoEmptyTemplate = config.messages?.photoDrawerEmpty ?? '';
    const removePhotoLabel = config.messages?.removePhoto ?? '';
    const confirmPrompt = config.messages?.completeConfirmPrompt ?? '';
    const confirmFailedLabel = config.messages?.completeConfirmFailed ?? '';
    const confirmIncompleteLabel = config.messages?.completeConfirmIncomplete ?? '';

    const noteDebouncers = new WeakMap();

    const buildUpdateUrl = (resultId) => {
        if (!config.endpoints?.partialUpdate) return null;
        return config.endpoints.partialUpdate.replace('RESULT_ID', resultId);
    };

    const setIndicator = (state) => {
        if (!indicator) return;
        Object.values(indicatorIcons).forEach((icon) => {
            if (icon) icon.classList.add('d-none');
        });
        if (!state) return;
        const icon = indicatorIcons[state];
        if (icon) {
            icon.classList.remove('d-none');
        }
    };

    const beginSave = () => {
        pendingSaves += 1;
        setIndicator('saving');
    };

    const endSave = (ok = true) => {
        pendingSaves = Math.max(0, pendingSaves - 1);
        if (pendingSaves > 0) {
            setIndicator('saving');
            return;
        }
        setIndicator(ok ? 'clean' : 'error');
    };

    const submitFormData = async (resultId, formData) => {
        const url = buildUpdateUrl(resultId);
        if (!url) return { ok: false };

        beginSave();
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : undefined,
                body: formData,
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Request failed');
            const data = await response.json().catch(() => ({}));
            endSave(true);
            return { ok: true, data };
        } catch (error) {
            console.error('Failed to update test result', error);
            endSave(false);
            return { ok: false, error };
        }
    };

    const applyLayout = (enabled) => {
        if (!grid) return;
        if (enabled) {
            grid.classList.add('compact-2col');
            grid.dataset.mode = 'two';
        } else {
            grid.classList.remove('compact-2col');
            grid.dataset.mode = 'one';
        }
    };

    const layoutKey = config.layoutKey || 'tests.layout.twoCol';
    const readLayoutPref = () => {
        try {
            return window.localStorage.getItem(layoutKey) === '1';
        } catch {
            return false;
        }
    };
    const writeLayoutPref = (enabled) => {
        try {
            window.localStorage.setItem(layoutKey, enabled ? '1' : '0');
        } catch {
            // ignore storage failures
        }
    };

    const twoColChk = document.getElementById('twoColChk');
    const toggleTwoColBtn = document.getElementById('toggleTwoCol');
    const initialTwoCol = readLayoutPref();
    if (twoColChk) {
        twoColChk.checked = initialTwoCol;
    }
    applyLayout(initialTwoCol);

    toggleTwoColBtn?.addEventListener('click', () => {
        const enabled = !(twoColChk?.checked ?? false);
        if (twoColChk) {
            twoColChk.checked = enabled;
        }
        applyLayout(enabled);
        writeLayoutPref(enabled);
    });

    const updateFailureSummary = () => {
        if (!failuresSummaryEls.length) return;
        const template = failuresSummaryEls[0].dataset.template || ':failures';
        const text = template.replace(':failures', progressState.failures);
        failuresSummaryEls.forEach((el) => {
            el.textContent = text;
            el.classList.toggle('text-danger', progressState.failures > 0);
        });
    };

    const refreshProgressUI = () => {
        progressCompletedEls.forEach((el) => {
            const template = el.dataset.template || ':completed / :total';
            el.textContent = template
                .replace(':completed', progressState.completed)
                .replace(':total', progressState.total);
        });
        progressRemainingEls.forEach((el) => {
            const template = el.dataset.template || ':remaining';
            el.textContent = template.replace(':remaining', progressState.remaining);
        });
        if (progressBar) {
            const width = progressState.total
                ? Math.round((progressState.completed / Math.max(1, progressState.total)) * 100)
                : 0;
            progressBar.style.width = `${width}%`;
            progressBar.setAttribute('aria-valuenow', progressState.completed);
            progressBar.setAttribute('aria-valuemax', progressState.total);
        }
        if (completeBtn) {
            completeBtn.disabled = false;
            completeBtn.classList.remove('disabled');
        }
        updateFailureSummary();
    };

    const updateProgressCounts = (oldStatus, newStatus, isRequired) => {
        if (oldStatus === newStatus) return;
        const isComplete = (status) => status === 'pass' || status === 'fail';

        if (isRequired) {
            if (!isComplete(oldStatus) && isComplete(newStatus)) {
                progressState.completed += 1;
                progressState.remaining = Math.max(0, progressState.remaining - 1);
            } else if (isComplete(oldStatus) && !isComplete(newStatus)) {
                progressState.completed = Math.max(0, progressState.completed - 1);
                progressState.remaining += 1;
            }
        }

        if (oldStatus === 'fail') {
            progressState.failures = Math.max(0, progressState.failures - 1);
            if (isRequired) {
                progressState.blockingFailures = Math.max(0, progressState.blockingFailures - 1);
            }
        }
        if (newStatus === 'fail') {
            progressState.failures += 1;
            if (isRequired) {
                progressState.blockingFailures += 1;
            }
        }
    };

    const setStatusPressedState = (card, status) => {
        const passBtn = card.querySelector('[data-action="set-pass"]');
        const failBtn = card.querySelector('[data-action="set-fail"]');
        passBtn?.setAttribute('aria-pressed', status === 'pass' ? 'true' : 'false');
        failBtn?.setAttribute('aria-pressed', status === 'fail' ? 'true' : 'false');
        const pill = card.querySelector('[data-status-pill]');
        if (pill) {
            pill.dataset.status = status;
            const label = pill.querySelector('[data-status-label]');
            if (label) {
                if (status === 'pass') {
                    label.textContent = card.dataset.statusPassLabel || 'Pass';
                } else if (status === 'fail') {
                    label.textContent = card.dataset.statusFailLabel || 'Fail';
                } else {
                    label.textContent = card.dataset.statusNvtLabel || 'N/A';
                }
            }
        }
    };

    const applyNoteSavedTimestamp = (textarea) => {
        if (!textarea) return;
        const target = textarea.closest('.collapse')?.querySelector('[data-note-saved]');
        if (!target) return;
        if (!noteMessageTemplate) {
            target.textContent = new Date().toLocaleString();
            return;
        }
        target.textContent = noteMessageTemplate.replace(':time', new Date().toLocaleString());
    };

    const setNoteIndicator = (card, hasNote) => {
        const indicator = card?.querySelector('[data-note-indicator]');
        if (!indicator) return;
        indicator.classList.toggle('is-active', !!hasNote);
    };

    const setPhotoIndicator = (card, hasPhoto) => {
        const indicator = card?.querySelector('[data-photo-indicator]');
        if (!indicator) return;
        indicator.classList.toggle('is-active', !!hasPhoto);
    };

    const ensurePhotoEmptyState = (gallery) => {
        if (!gallery) return false;
        const hasThumb = gallery.querySelector('[data-photo-node="true"]');
        let empty = gallery.querySelector('[data-photo-empty]');
        if (hasThumb) {
            empty?.remove();
            return true;
        }
        if (!empty) {
            empty = document.createElement('span');
            empty.dataset.photoEmpty = 'true';
            empty.className = 'text-muted small';
            empty.textContent = photoEmptyTemplate || '';
            gallery.prepend(empty);
        }
        return false;
    };

    const createPhotoThumb = (photo) => {
        const normalized = typeof photo === 'string' ? { url: photo } : photo || {};
        const wrapper = document.createElement('div');
        wrapper.className = 'testing-photos-thumb';
        wrapper.dataset.photoNode = 'true';
        if (normalized.id) {
            wrapper.dataset.photoId = normalized.id;
        }

        const img = document.createElement('img');
        img.src = normalized.url || '';
        img.alt = '';
        img.dataset.action = 'open-photo';
        if (normalized.id) {
            img.dataset.photoId = normalized.id;
        }
        wrapper.appendChild(img);

        if (config.canUpdate) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-link text-danger p-0 small mt-1';
            removeBtn.dataset.action = 'confirm-remove-photo';
            if (normalized.id) {
                removeBtn.dataset.photoId = normalized.id;
            }
            removeBtn.textContent = removePhotoLabel;
            wrapper.appendChild(removeBtn);
        }

        return wrapper;
    };

    const cards = grid.querySelectorAll('[data-result-id]');
    cards.forEach((card) => {
        const initialStatus = card.dataset.initialStatus || 'nvt';
        card.dataset.currentStatus = initialStatus;
        setStatusPressedState(card, initialStatus);
        const noteField = card.querySelector('textarea[data-bind="note"]');
        setNoteIndicator(card, noteField?.value.trim().length > 0);
        const hasPhoto = !!card.querySelector('[data-photo-node="true"]');
        setPhotoIndicator(card, hasPhoto);
    });

    refreshProgressUI();
    setIndicator('clean');

    const getCompletionIssues = () => {
        const failed = [];
        const incomplete = [];
        cards.forEach((card) => {
            const status = card.dataset.currentStatus || 'nvt';
            const isRequired = card.dataset.isRequired !== '0';
            const label = card.dataset.testLabel || '';
            if (status === 'fail') {
                failed.push(label || card.id || 'Unknown');
            }
            if (isRequired && status === 'nvt') {
                incomplete.push(label || card.id || 'Unknown');
            }
        });
        return { failed, incomplete };
    };

    const renderCompletionIssues = (issues) => {
        if (!completeConfirmModalEl) return;
        const renderList = (items, block, list) => {
            if (!block || !list) return;
            if (!items.length) {
                block.style.display = 'none';
                list.innerHTML = '';
                return;
            }
            block.style.display = '';
            list.innerHTML = '';
            items.forEach((item) => {
                const li = document.createElement('li');
                li.textContent = item;
                list.appendChild(li);
            });
        };

        renderList(issues.failed, completeConfirmFailedBlock, completeConfirmFailedList);
        renderList(issues.incomplete, completeConfirmIncompleteBlock, completeConfirmIncompleteList);
    };

    const buildCompletionPrompt = (issues) => {
        const lines = [];
        if (confirmPrompt) {
            lines.push(confirmPrompt);
        }
        if (issues.failed.length) {
            lines.push(`${confirmFailedLabel || 'Failed'}: ${issues.failed.join(', ')}`);
        }
        if (issues.incomplete.length) {
            lines.push(`${confirmIncompleteLabel || 'Not executed'}: ${issues.incomplete.join(', ')}`);
        }
        return lines.join('\n');
    };

    const buildStatusPayload = (status) => {
        const formData = new FormData();
        if (status === 'nvt') {
            formData.set('status', '');
        } else {
            formData.set('status', status);
        }
        return formData;
    };

    const handleStatusChange = async (card, nextStatus) => {
        const resultId = card.dataset.resultId;
        if (!resultId) return;
        const isRequired = card.dataset.isRequired !== '0';

        const previousStatus = card.dataset.currentStatus || 'nvt';
        card.dataset.currentStatus = nextStatus;
        setStatusPressedState(card, nextStatus);
        updateProgressCounts(previousStatus, nextStatus, isRequired);
        refreshProgressUI();

        const response = await submitFormData(resultId, buildStatusPayload(nextStatus));
        if (!response.ok) {
            card.dataset.currentStatus = previousStatus;
            setStatusPressedState(card, previousStatus);
            updateProgressCounts(nextStatus, previousStatus, isRequired);
            refreshProgressUI();
        }
    };

    const maybeCloseOtherDrawer = (targetId) => {
        if (openDrawerId && openDrawerId !== targetId) {
            const current = document.getElementById(openDrawerId);
            if (current) {
                const instance = getCollapseController(current);
                instance?.hide();
            }
        }
        openDrawerId = targetId;
    };

    grid.addEventListener('shown.bs.collapse', (event) => {
        openDrawerId = event.target.id;
    });

    grid.addEventListener('hidden.bs.collapse', (event) => {
        if (openDrawerId === event.target.id) {
            openDrawerId = null;
        }
    });

    grid.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-action]');
        if (!trigger) return;

        const card = trigger.closest('[data-result-id]');
        const action = trigger.dataset.action;

        if (!card && !['open-photo'].includes(action)) {
            return;
        }

        switch (action) {
            case 'toggle-help': {
                const targetId = trigger.getAttribute('aria-controls');
                if (!targetId) return;
                const element = document.getElementById(targetId);
                if (!element) return;
                const instance = getCollapseController(element);
                instance?.toggle();
                break;
            }
            case 'toggle-note':
            case 'toggle-photos': {
                const targetId = trigger.getAttribute('aria-controls');
                if (!targetId) return;
                const element = document.getElementById(targetId);
                if (!element) return;
                maybeCloseOtherDrawer(targetId);
                const instance = getCollapseController(element);
                instance?.toggle();
                break;
            }
            case 'set-pass':
            case 'set-fail': {
                if (!config.canUpdate) return;
                const desired = action === 'set-pass' ? 'pass' : 'fail';
                const current = card.dataset.currentStatus || 'nvt';
                const nextStatus = current === desired ? 'nvt' : desired;
                handleStatusChange(card, nextStatus);
                break;
            }
            case 'open-photo': {
                if (!photoViewerModal || !viewerImg) return;
                const src = trigger.getAttribute('src') || trigger.dataset.photoUrl;
                if (!src) return;
                viewerImg.src = src;
                photoViewerModal.show();
                break;
            }
            case 'confirm-remove-photo': {
                if (!photoDeleteModal) return;
                const photoId = trigger.dataset.photoId;
                if (!photoId) return;
                deleteContext = {
                    resultId: card?.dataset.resultId,
                    gallery: card?.querySelector('[data-photo-gallery]'),
                    photoId,
                };
                photoDeleteModal.show();
                break;
            }
            default:
                break;
        }
    });

    deleteConfirmBtn?.addEventListener('click', async () => {
        if (!deleteContext || !deleteContext.resultId) return;

        const formData = new FormData();
        if (deleteContext.photoId) {
            formData.set('remove_photo_id', deleteContext.photoId);
        } else {
            formData.set('remove_photo', '1');
        }
        const response = await submitFormData(deleteContext.resultId, formData);
        if (response.ok) {
            const gallery = deleteContext.gallery;
            let thumb = null;
            if (deleteContext.photoId) {
                thumb = gallery?.querySelector(`[data-photo-node="true"][data-photo-id="${deleteContext.photoId}"]`);
            } else {
                thumb = gallery?.querySelector('[data-photo-node="true"]');
            }
            thumb?.remove();
            const hasPhotos = ensurePhotoEmptyState(gallery);
            const card = gallery?.closest('[data-result-id]');
            setPhotoIndicator(card, hasPhotos);
        }
        photoDeleteModal?.hide();
        deleteContext = null;
    });

    grid.addEventListener('change', async (event) => {
        const input = event.target.closest('input[type="file"][data-action="upload-photo"]');
        if (!input || !input.files?.length) return;
        const card = input.closest('[data-result-id]');
        if (!card) return;
        const resultId = card.dataset.resultId;
        const files = Array.from(input.files);

        for (const file of files) {
            const formData = new FormData();
            formData.set('photo', file);
            const response = await submitFormData(resultId, formData);
            if (response.ok && response.data?.photo) {
                const gallery = card.querySelector('[data-photo-gallery]');
                const photoData = response.data.photo || {
                    id: response.data.photo_id,
                    url: response.data.photo_url,
                };
                const thumb = createPhotoThumb(photoData);
                gallery?.appendChild(thumb);
                const hasPhotos = ensurePhotoEmptyState(gallery);
                setPhotoIndicator(card, hasPhotos);
            }
        }
        input.value = '';
    });

    grid.addEventListener('input', (event) => {
        const textarea = event.target.closest('textarea[data-bind="note"]');
        if (!textarea || !config.canUpdate) return;
        const card = textarea.closest('[data-result-id]');
        if (!card) return;
        const resultId = card.dataset.resultId;
        setNoteIndicator(card, textarea.value.trim().length > 0);

        let handler = noteDebouncers.get(textarea);
        if (!handler) {
            handler = debounce(async () => {
                const formData = new FormData();
                formData.set('note', textarea.value);
                const response = await submitFormData(resultId, formData);
                if (response.ok) {
                    applyNoteSavedTimestamp(textarea);
                }
            }, 700);
            noteDebouncers.set(textarea, handler);
        }
        handler();
    });

    completeConfirmContinue?.addEventListener('click', () => {
        if (config.actions?.completeUrl) {
            window.location.href = config.actions.completeUrl;
        }
    });

    completeBtn?.addEventListener('click', () => {
        if (!config.actions?.completeUrl) {
            return;
        }

        const issues = getCompletionIssues();
        if (!issues.failed.length && !issues.incomplete.length) {
            window.location.href = config.actions.completeUrl;
            return;
        }

        if (completeConfirmModal) {
            renderCompletionIssues(issues);
            completeConfirmModal.show();
            return;
        }

        const message = buildCompletionPrompt(issues);
        if (!message || window.confirm(message)) {
            window.location.href = config.actions.completeUrl;
        }
    });

    photoViewerModalEl?.addEventListener('hidden.bs.modal', () => {
        if (viewerImg) {
            viewerImg.src = '';
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTestsActiveUI);
} else {
    bootTestsActiveUI();
}
