<script nonce="{{ csrf_token() }}">
    (function () {
        function applyUppercase($input) {
            if (!$input || !$input.length) {
                return;
            }

            var $wrapper = $input.closest('.js-model-number-case-wrapper');
            var overrideEnabled = $wrapper.find('.js-case-override-input').val() === '1';

            if (overrideEnabled) {
                return;
            }

            var value = $input.val();
            if (!value) {
                return;
            }

            var upper = value.toUpperCase();
            if (value !== upper) {
                $input.val(upper);
            }
        }

        function syncCaseOverrideState($wrapper) {
            var $toggle = $wrapper.find('.js-case-override-toggle');
            var $hidden = $wrapper.find('.js-case-override-input');

            if (!$toggle.length || !$hidden.length) {
                return;
            }

            var active = $hidden.val() === '1';
            $toggle.toggleClass('btn-warning active', active);
            $toggle.toggleClass('btn-default', !active);
            $toggle.attr('aria-pressed', active ? 'true' : 'false');
        }

        function toggleCaseOverride($button) {
            var $wrapper = $button.closest('.js-model-number-case-wrapper');
            var $hidden = $wrapper.find('.js-case-override-input');

            if (!$hidden.length) {
                return;
            }

            var active = $hidden.val() === '1';
            $hidden.val(active ? '0' : '1');
            syncCaseOverrideState($wrapper);

            if (active) {
                applyUppercase($wrapper.find('.js-uppercase-input'));
            }
        }

        $(document).on('click', '.js-model-number-case-wrapper .js-case-override-toggle', function (event) {
            event.preventDefault();
            toggleCaseOverride($(this));
        });

        $(document).on('input', '.js-model-number-case-wrapper .js-uppercase-input', function () {
            applyUppercase($(this));
        });

        $('.js-model-number-case-wrapper').each(function () {
            var $wrapper = $(this);
            syncCaseOverrideState($wrapper);
            $wrapper.find('.js-uppercase-input').each(function () {
                applyUppercase($(this));
            });
        });
    })();
</script>
