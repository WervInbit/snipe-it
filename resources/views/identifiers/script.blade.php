<script nonce="{{ csrf_token() }}">
(function () {
    const text = @json(trans('identifiers'));
    const endpoint = @json(route('identifiers.check'));
    const tracked = new Map();
    const normalize = value => String(value || '').trim().toUpperCase();
    function context(input) {
        const scope = input.closest('[data-identifier-scope]') || document.querySelector('[data-identifier-page]');
        if (!scope) return null;
        const name = input.name || '';
        const tag = /^(asset_tags\[\d+\]|asset_tag|component_tag)$/.test(name);
        if (!tag && !/^(serials\[\d+\]|serial)$/.test(name)) return null;
        const index = name.match(/\[(\d+)\]/);
        return {type: scope.dataset.identifierType, record_id: scope.dataset.identifierId || '', field: tag ? 'tag' : 'serial',
            flag: index ? 'allow_duplicate_' + (tag ? 'tags' : 'serials') + '[' + index[1] + ']'
                : 'allow_duplicate_' + (tag ? 'tag' : 'serial')};
    }
    const message = (template, ctx, count) => template.replace(':field', text[ctx.field]).replace(':count', count);
    function attach(input) {
        if (tracked.has(input) || input.type === 'hidden') return;
        const ctx = context(input);
        if (!ctx || !input.form) return;
        const box = document.createElement('div');
        box.className = 'alert alert-warning identifier-duplicate-warning'; box.setAttribute('role', 'status'); box.hidden = true;
        const caption = document.createElement('div'), matches = document.createElement('div');
        const label = document.createElement('label'), checkbox = document.createElement('input');
        checkbox.type = 'checkbox'; checkbox.name = ctx.flag; checkbox.value = '1';
        label.append(checkbox, document.createTextNode(' ' + message(text.confirm, ctx, 0)));
        box.append(caption, matches, label);
        (input.closest('.form-group') || input.parentElement).append(box);
        const state = {ctx, box, caption, matches, label, checkbox, version: 0, checkedValue: '', pending: false};
        tracked.set(input, state);
        input.addEventListener('input', () => { checkbox.checked = false; refreshForm(input.form); });
        input.addEventListener('change', () => refreshForm(input.form));
        input.addEventListener('focus', () => check(input));
        checkbox.addEventListener('change', () => input.setCustomValidity(''));
        check(input);
    }
    function refreshForm(form) {
        tracked.forEach((state, input) => { if (input.form === form) check(input); });
    }
    function check(input) {
        const state = tracked.get(input);
        if (!state || !input.isConnected) return;
        clearTimeout(state.timer);
        if (state.controller) state.controller.abort();
        const version = ++state.version, value = normalize(input.value);
        if (state.checkedValue !== value) state.checkbox.checked = false;
        state.checkedValue = value;
        input.setCustomValidity('');
        if (!value || input.disabled) {
            state.box.hidden = true; state.checkbox.disabled = true; state.pending = false;
            state.requiresConfirmation = false; state.failed = false; return;
        }
        state.pending = true;
        state.timer = setTimeout(async () => {
            const ctx = context(input);
            const controller = new AbortController();
            state.controller = controller;
            const timeout = setTimeout(() => controller.abort(), 10000);
            try {
                const params = new URLSearchParams({type: ctx.type, field: ctx.field, value: input.value});
                if (ctx.record_id) params.set('record_id', ctx.record_id);
                const response = await fetch(endpoint + '?' + params, {signal: controller.signal, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
                if (!response.ok) throw new Error('duplicate check failed');
                const result = await response.json();
                if (version !== state.version) return;
                let localCount = 0;
                tracked.forEach((other, otherInput) => {
                    if (otherInput !== input && otherInput.isConnected && !otherInput.disabled && otherInput.form === input.form
                        && other.ctx.field === ctx.field && normalize(otherInput.value) === value) localCount++;
                });
                const count = result.count + localCount;
                const unchanged = result.unchanged === true;
                state.box.hidden = count === 0;
                state.caption.textContent = message(text.warning, ctx, count);
                state.matches.replaceChildren();
                result.matches.forEach(match => {
                    const link = document.createElement('a'); link.href = match.url; link.target = '_blank'; link.rel = 'noopener';
                    link.textContent = match.label + ' (#' + match.id + ') ' + (match.name || '');
                    state.matches.append(link, document.createElement('br'));
                });
                if (result.matches.length < result.count) state.matches.append(document.createTextNode(text.restricted));
                state.label.hidden = unchanged || count === 0; state.checkbox.disabled = unchanged || count === 0;
                state.requiresConfirmation = count > 0 && !unchanged; state.failed = false;
                input.setCustomValidity('');
            } catch (error) {
                if (version !== state.version) return;
                state.box.hidden = false; state.caption.textContent = text.check_failed;
                state.matches.replaceChildren(); state.label.hidden = true; state.checkbox.disabled = true; state.failed = true;
            } finally { clearTimeout(timeout); if (version === state.version) state.pending = false; }
        }, 250);
    }
    document.addEventListener('submit', event => {
        let blocked = null;
        tracked.forEach((state, input) => {
            if (!input.isConnected || input.form !== event.target || input.disabled || !normalize(input.value)) return;
            if (state.pending || state.failed || (state.requiresConfirmation && !state.checkbox.checked)) {
                const reason = state.pending ? text.checking : state.failed ? text.check_failed : message(text.confirm_required, state.ctx, 0);
                input.setCustomValidity(reason); state.box.hidden = false; state.caption.textContent = reason;
                blocked = blocked || input;
            } else input.setCustomValidity('');
        });
        if (blocked) { event.preventDefault(); blocked.reportValidity(); }
    }, true);
    function discover() { document.querySelectorAll('input[name]').forEach(attach); }
    document.addEventListener('DOMContentLoaded', () => {
        discover();
        new MutationObserver(discover).observe(document.body, {childList: true, subtree: true});
        if (window.jQuery) window.jQuery(document).on('shown.bs.modal', () => tracked.forEach((state, input) => check(input)));
    });
})();
</script>
