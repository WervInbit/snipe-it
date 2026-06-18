@php
    $serialId = $serialId ?? 'component_serial';
    $currentSerial = (string) ($currentSerial ?? $component?->serial ?? '');
    $isEnabled = old('serial_change_enabled', '0') === '1';
    $serialValue = old('serial', $currentSerial);
@endphp

<div class="form-group {{ $errors->has('serial') ? 'has-error' : '' }}"
     data-component-serial-control
     data-original-serial="{{ $currentSerial }}">
    <label for="{{ $serialId }}">{{ trans('admin/hardware/form.serial') }}</label>
    <div class="input-group">
        <input type="text"
               class="form-control"
               id="{{ $serialId }}"
               name="serial"
               value="{{ $serialValue }}"
               data-component-serial-input
               @disabled(!$isEnabled)>
        <span class="input-group-btn">
            <button type="button"
                    class="btn btn-default"
                    data-component-serial-toggle
                    data-label-add="{{ __('Add serial') }}"
                    data-label-change="{{ __('Change serial') }}">
                {{ $currentSerial !== '' ? __('Change serial') : __('Add serial') }}
            </button>
        </span>
    </div>
    <input type="hidden" name="serial_change_enabled" value="{{ $isEnabled ? '1' : '0' }}" data-component-serial-enabled>
    <input type="hidden" name="serial_change_confirmed" value="0" data-component-serial-confirmed>
    <p class="help-block">
        {{ __('Serial is locked by default. Use the button to capture or correct the component serial before removing it.') }}
    </p>
    {!! $errors->first('serial', '<span class="help-block">:message</span>') !!}
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
                function normalize(value) {
                    return (value || '').trim();
                }

                function updateButtonLabel(control) {
                    var button = control.querySelector('[data-component-serial-toggle]');
                    var original = normalize(control.getAttribute('data-original-serial'));

                    if (!button) {
                        return;
                    }

                    button.textContent = original
                        ? button.getAttribute('data-label-change')
                        : button.getAttribute('data-label-add');
                }

                function setSerialControlEnabled(control, enabled) {
                    var input = control.querySelector('[data-component-serial-input]');
                    var enabledField = control.querySelector('[data-component-serial-enabled]');
                    var confirmedField = control.querySelector('[data-component-serial-confirmed]');

                    if (!input || !enabledField) {
                        return;
                    }

                    input.disabled = !enabled;
                    enabledField.value = enabled ? '1' : '0';

                    if (confirmedField) {
                        confirmedField.value = '0';
                    }

                    if (enabled) {
                        input.focus();
                        input.select();
                    }
                }

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-component-serial-toggle]');
                    if (!button) {
                        return;
                    }

                    var control = button.closest('[data-component-serial-control]');
                    if (control) {
                        setSerialControlEnabled(control, true);
                    }
                });

                document.addEventListener('submit', function (event) {
                    var form = event.target;
                    var controls = form.querySelectorAll('[data-component-serial-control]');

                    controls.forEach(function (control) {
                        var enabledField = control.querySelector('[data-component-serial-enabled]');
                        var confirmedField = control.querySelector('[data-component-serial-confirmed]');
                        var input = control.querySelector('[data-component-serial-input]');
                        var original = normalize(control.getAttribute('data-original-serial'));
                        var current = normalize(input ? input.value : '');

                        if (!enabledField || enabledField.value !== '1' || !original || current === original) {
                            return;
                        }

                        if (confirmedField && confirmedField.value === '1') {
                            return;
                        }

                        if (!window.confirm('{{ __('This component already has a serial number. Are you sure you want to change it?') }}')) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            if (input) {
                                input.focus();
                            }
                            return;
                        }

                        if (confirmedField) {
                            confirmedField.value = '1';
                        }
                    });
                }, true);

                document.querySelectorAll('[data-component-serial-control]').forEach(function (control) {
                    updateButtonLabel(control);
                });

                window.refreshComponentSerialControl = function (control, serial) {
                    if (!control) {
                        return;
                    }

                    var value = serial || '';
                    var input = control.querySelector('[data-component-serial-input]');
                    var enabledField = control.querySelector('[data-component-serial-enabled]');
                    var confirmedField = control.querySelector('[data-component-serial-confirmed]');

                    control.setAttribute('data-original-serial', value);

                    if (input) {
                        input.value = value;
                        input.disabled = true;
                    }

                    if (enabledField) {
                        enabledField.value = '0';
                    }

                    if (confirmedField) {
                        confirmedField.value = '0';
                    }

                    updateButtonLabel(control);
                };
            })();
        </script>
    @endpush
@endonce
