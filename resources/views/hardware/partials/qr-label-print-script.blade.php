<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]');
    var csrf = token ? token.getAttribute('content') : '';

    document.querySelectorAll('.qr-server-print-button').forEach(function (button) {
        if (button.getAttribute('data-qr-print-bound') === '1') return;
        button.setAttribute('data-qr-print-bound', '1');

        var templateSelector = button.getAttribute('data-template-selector');
        var templateField = templateSelector ? document.querySelector(templateSelector) : null;
        var queueSelector = button.getAttribute('data-queue-selector');
        var queueField = queueSelector ? document.querySelector(queueSelector) : null;

        button.addEventListener('click', function () {
            var templateValue = templateField ? templateField.value : button.getAttribute('data-template');
            if (!templateValue) return;

            button.disabled = true;
            button.classList.add('disabled');
            var queueValue = queueField ? queueField.value : null;

            fetch(button.getAttribute('data-print-url'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    template: templateValue,
                    queue: queueValue
                })
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (payload) {
                    var msg = payload.data && payload.data.message ? payload.data.message : (payload.ok ? @json(trans('general.label_sent_to_printer')) : @json(trans('general.printing_failed')));
                    if (window.toastr) {
                        payload.ok ? toastr.success(msg) : toastr.error(msg);
                    } else {
                        alert(msg);
                    }
                })
                .catch(function () {
                    var msg = @json(trans('general.printing_failed'));
                    if (window.toastr) {
                        toastr.error(msg);
                    } else {
                        alert(msg);
                    }
                })
                .then(function () {
                    button.disabled = false;
                    button.classList.remove('disabled');
                });
        });
    });
})();
</script>
