@extends('layouts/default')

@section('title')
    {{ trans('tests.edit_test_results') }}
@endsection

@push('styles')
<style>
.test-editor {
    padding-bottom: 2rem;
}

.test-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.test-card__headline {
    flex: 1 1 auto;
}

.test-card__title {
    font-weight: 600;
    margin-bottom: 4px;
}

.test-card__expected {
    font-size: 12px;
}

.attachment-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #f4f4f4;
    color: #999;
    margin-left: 6px;
}

.attachment-badge.is-present {
    background: #337ab7;
    color: #fff;
}

.status-toggle {
    display: flex;
    flex-direction: column;
}

.status-toggle .status-btn {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-weight: 600;
}

.status-toggle .status-btn:last-child {
    margin-bottom: 0;
}

.btn-outline-default {
    background: #fff;
    border: 1px solid #dcdcdc;
    color: #555;
}

.btn-outline-default:hover,
.btn-outline-default:focus {
    background: #f7f7f7;
}

.btn-secondary {
    background: #54667a;
    border-color: #455360;
    color: #fff;
}

.btn-secondary:hover,
.btn-secondary:focus {
    background: #455360;
    color: #fff;
}

.status-btn.is-active {
    box-shadow: 0 0 0 2px rgba(51, 122, 183, 0.15);
}

.test-card__actions {
    display: flex;
    flex-wrap: wrap;
    margin-top: 15px;
}

.test-card__actions .btn {
    padding-left: 0;
    padding-right: 12px;
}

.test-card__section {
    margin-top: 12px;
}

.comment-field textarea {
    resize: vertical;
}

.photo-preview img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin-bottom: 6px;
}

.test-toast {
    position: fixed;
    left: 50%;
    bottom: 30px;
    transform: translate(-50%, 20px);
    opacity: 0;
    transition: all .25s ease;
    z-index: 9999;
    pointer-events: none;
}

.test-toast.is-visible {
    opacity: 1;
    transform: translate(-50%, 0);
}

@media (min-width: 768px) {
    .status-toggle {
        flex-direction: row;
    }

    .status-toggle .status-btn {
        flex: 1;
        margin-right: 10px;
        margin-bottom: 0;
    }

    .status-toggle .status-btn:last-child {
        margin-right: 0;
    }
}
</style>
@endpush

@section('content')
<div class="container test-editor">
    <div class="row test-card-grid">
        @foreach ($testRun->results as $result)
            @php
                $definition = $result->attributeDefinition;
                $label = $definition?->label ?? optional($result->type)->name;
                $instructions = trim((string) (optional($result->type)->instructions ?: ($definition?->instructions ?? $definition?->help_text)));
                $expectedDisplay = $result->expected_value;
                if ($definition && $definition->datatype === \App\Models\AttributeDefinition::DATATYPE_BOOL && $expectedDisplay !== null) {
                    $expectedDisplay = $expectedDisplay === '1' ? __('Yes') : __('No');
                }
            @endphp
            <div class="col-xs-12 col-md-6">
                <article class="panel panel-default test-card" data-result-id="{{ $result->id }}">
                    <header class="panel-heading test-card__header">
                        <div class="test-card__headline">
                            <div class="test-card__title">
                                {{ $label }}
                                @if($instructions !== '')
                                    <i class="fas fa-info-circle" data-tooltip="true" title="{{ $instructions }}"></i>
                                @endif
                            </div>
                            @if ($expectedDisplay !== null)
                                <div class="test-card__expected text-muted">{{ __('Expected: :value', ['value' => $expectedDisplay]) }}</div>
                            @endif
                        </div>
                        <div class="test-card__meta text-right">
                            <span class="attachment-badge note {{ $result->note ? 'is-present' : '' }}" title="{{ $result->note ? trans('general.note_added') : trans('general.add_note') }}">
                                <i class="fas fa-comment"></i>
                            </span>
                            <span class="attachment-badge photo {{ $result->photo_path ? 'is-present' : '' }}" title="{{ $result->photo_path ? trans('general.photo_added') : trans('general.attach_photo') }}">
                                <i class="fas fa-camera"></i>
                            </span>
                        </div>
                    </header>
                    <div class="panel-body">
                        <div class="status-toggle" role="group" aria-label="{{ trans('tests.edit_test_results') }}">
                            <button type="button" class="btn btn-lg status-btn {{ $result->status === 'pass' ? 'btn-success is-active' : 'btn-outline-default' }}" data-status="pass" aria-pressed="{{ $result->status === 'pass' ? 'true' : 'false' }}">
                                <i class="fas fa-check"></i>
                                <span>{{ trans('tests.pass') }}</span>
                            </button>
                            <button type="button" class="btn btn-lg status-btn {{ $result->status === 'fail' ? 'btn-danger is-active' : 'btn-outline-default' }}" data-status="fail" aria-pressed="{{ $result->status === 'fail' ? 'true' : 'false' }}">
                                <i class="fas fa-times"></i>
                                <span>{{ trans('tests.fail') }}</span>
                            </button>
                            <button type="button" class="btn btn-lg status-btn {{ $result->status === 'nvt' ? 'btn-secondary is-active' : 'btn-outline-default' }}" data-status="nvt" aria-pressed="{{ $result->status === 'nvt' ? 'true' : 'false' }}">
                                <i class="fas fa-question"></i>
                                <span>{{ trans('tests.nvt') }}</span>
                            </button>
                        </div>
                        <input type="hidden" class="result-status" value="{{ $result->status }}">

                        <div class="test-card__actions">
                            <button type="button" class="btn btn-link comment-toggle">
                                <i class="fas fa-comment"></i>
                                <span class="comment-toggle__label">
                                    {{ $result->note ? trans('general.edit_note') : trans('general.add_note') }}
                                </span>
                            </button>
                            <button type="button" class="btn btn-link photo-button">
                                <i class="fas fa-camera"></i>
                                <span class="photo-button__label">
                                    {{ $result->photo_path ? trans('general.replace_photo') : trans('general.attach_photo') }}
                                </span>
                            </button>
                        </div>

                        <div class="comment-field test-card__section" @if(!$result->note) hidden @endif>
                            <textarea class="form-control note-input" rows="3" placeholder="{{ trans('general.add_note') }}">{{ $result->note }}</textarea>
                        </div>

                        <input type="file" class="photo-input" accept="image/*" capture="environment" hidden>
                        <div class="photo-preview test-card__section" @if(!$result->photo_path) hidden @endif>
                            @if($result->photo_path)
                                <img src="/{{ $result->photo_path }}" alt="{{ trans('general.photo_added') }}" class="img-responsive">
                            @endif
                            <button type="button" class="btn btn-link btn-sm remove-photo">{{ trans('button.remove') }}</button>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function ($) {
        const debounce = (fn, delay = 400) => {
            let timer;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        };

        const notify = (message, type = 'info') => {
            const $toast = $('<div class="test-toast alert alert-' + type + '">' + message + '</div>');
            $('body').append($toast);
            requestAnimationFrame(() => $toast.addClass('is-visible'));
            setTimeout(() => $toast.removeClass('is-visible'), 2200);
            setTimeout(() => $toast.remove(), 2600);
        };

        const updateBadges = ($card, payload) => {
            if (payload.hasOwnProperty('note')) {
                const hasNote = payload.note && payload.note.trim().length > 0;
                $card.find('.attachment-badge.note').toggleClass('is-present', hasNote);
                $card.find('.comment-toggle__label').text(
                    hasNote ? @json(trans('general.edit_note')) : @json(trans('general.add_note'))
                );
            }

            if (payload.hasOwnProperty('photo')) {
                const hasPhoto = !!payload.photo;
                $card.find('.attachment-badge.photo').toggleClass('is-present', hasPhoto);
                $card.find('.photo-button__label').text(
                    hasPhoto ? @json(trans('general.replace_photo')) : @json(trans('general.attach_photo'))
                );
            }
        };

        const sendUpdate = (resultId, payload, formData = null) => {
            const endpoint = "{{ route('test-results.partial-update', [$asset->id, $testRun->id, 'result' => '__RESULT__']) }}".replace('__RESULT__', resultId);

            const options = {
                url: endpoint,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success(response) {
                    if (response?.message) {
                        notify(response.message, 'success');
                    }
                    const $card = $('.test-card[data-result-id="' + resultId + '"]');
                    const badgeUpdates = {};
                    if (response && Object.prototype.hasOwnProperty.call(response, 'note')) {
                        badgeUpdates.note = response.note;
                    } else if (payload && Object.prototype.hasOwnProperty.call(payload, 'note')) {
                        badgeUpdates.note = payload.note;
                    }
                    if (response && Object.prototype.hasOwnProperty.call(response, 'photo')) {
                        badgeUpdates.photo = response.photo;
                    } else if (payload && Object.prototype.hasOwnProperty.call(payload, 'photo')) {
                        badgeUpdates.photo = payload.photo;
                    }
                    if (Object.keys(badgeUpdates).length) {
                        updateBadges($card, badgeUpdates);
                    }
                },
                error(xhr) {
                    notify(xhr.responseJSON?.message || xhr.statusText || 'Save failed', 'danger');
                }
            };

            if (formData instanceof FormData) {
                options.data = formData;
                options.processData = false;
                options.contentType = false;
            } else {
                options.data = payload;
            }

            return $.ajax(options);
        };

        $(function () {
            const $cards = $('.test-card');

            $cards.find('.status-btn').on('click', function () {
                const $btn = $(this);
                const $card = $btn.closest('.test-card');
                const status = $btn.data('status');

                $btn.closest('.status-toggle').find('.status-btn').each(function () {
                    $(this)
                        .removeClass('btn-success btn-danger btn-secondary is-active')
                        .addClass('btn-outline-default')
                        .attr('aria-pressed', 'false');
                });

                $btn
                    .removeClass('btn-outline-default')
                    .addClass(status === 'pass' ? 'btn-success' : status === 'fail' ? 'btn-danger' : 'btn-secondary')
                    .addClass('is-active')
                    .attr('aria-pressed', 'true');

                $card.find('.result-status').val(status);
                sendUpdate($card.data('result-id'), { status });
            });

            $cards.find('.comment-toggle').on('click', function () {
                const $card = $(this).closest('.test-card');
                const $field = $card.find('.comment-field');
                const isHidden = $field.prop('hidden');
                $field.prop('hidden', !isHidden);
                if (isHidden) {
                    setTimeout(() => $card.find('.note-input').trigger('focus'), 50);
                }
            });

            const saveNote = debounce(function () {
                const $textarea = $(this);
                const $card = $textarea.closest('.test-card');
                const note = $textarea.val();
                updateBadges($card, { note });
                sendUpdate($card.data('result-id'), { note });
            }, 600);

            $cards.find('.note-input').on('input', saveNote);

            $cards.find('.photo-button').on('click', function () {
                $(this).closest('.test-card').find('.photo-input').trigger('click');
            });

            $cards.find('.photo-input').on('change', function () {
                const input = this;
                const file = input.files && input.files[0];
                const $card = $(input).closest('.test-card');
                if (!file) {
                    return;
                }

                const formData = new FormData();
                formData.append('photo', file);

                sendUpdate($card.data('result-id'), {}, formData).done(function (response) {
                    const photoUrl = response?.photo_url;
                    if (photoUrl) {
                        const $preview = $card.find('.photo-preview');
                        $preview.prop('hidden', false);
                        $preview.find('img').remove();
                        $('<img>', {
                            src: photoUrl,
                            class: 'img-responsive',
                            alt: @json(trans('general.photo_added'))
                        }).prependTo($preview);
                        updateBadges($card, { photo: true });
                    }
                }).fail(function () {
                    input.value = '';
                });
            });

            $cards.find('.remove-photo').on('click', function () {
                const $card = $(this).closest('.test-card');
                sendUpdate($card.data('result-id'), { remove_photo: true }).done(function () {
                    $card.find('.photo-preview').prop('hidden', true).find('img').remove();
                    $card.find('.photo-input').val('');
                    updateBadges($card, { photo: false });
                });
            });
        });
    })(jQuery);
</script>
@endsection
