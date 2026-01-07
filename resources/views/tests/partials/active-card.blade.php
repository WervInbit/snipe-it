@php
    use App\Models\TestResult;

    $status = $result['status'] ?? TestResult::STATUS_NVT;
    $note = $result['note'] ?? '';
    $hasNote = trim((string) $note) !== '';
    $photos = $result['photos'] ?? [];
    $hasPhoto = !empty($photos);
    $isRequired = $result['is_required'] ?? true;
@endphp

<article class="testing-card"
         id="test-{{ $result['id'] }}"
         data-result-id="{{ $result['id'] }}"
         data-testid="test-item-{{ $result['slug'] }}"
         data-test-label="{{ $result['label'] }}"
         data-current-status="{{ $status }}"
         data-initial-status="{{ $status }}"
         data-is-required="{{ $isRequired ? '1' : '0' }}"
         data-status-pass-label="{{ trans('tests.status_pass') }}"
         data-status-fail-label="{{ trans('tests.status_fail') }}"
         data-status-nvt-label="{{ trans('tests.status_nvt') }}">
    <div class="testing-card__body">
        <div class="testing-card__head">
            <div>
            <div class="testing-card__title h5 mb-0">
                {{ $result['label'] }}
                @if(!$isRequired)
                    <span class="testing-card__optional">{{ trans('tests.optional') }}</span>
                @endif
            </div>
            </div>
            @if(!empty($result['instructions']))
                <button type="button"
                        class="btn btn-link p-0 text-muted"
                        data-action="toggle-help"
                        data-bs-toggle="collapse"
                        data-bs-target="#instructions-{{ $result['id'] }}"
                        aria-controls="instructions-{{ $result['id'] }}"
                        aria-expanded="false">
                    <i class="fas fa-circle-info" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ trans('tests.show_instructions') }}</span>
                </button>
            @endif
        </div>

        <div class="btn-group testing-card__toggle testing-card__toggle--center" role="group" aria-label="{{ trans('tests.set_status') }}">
            <button type="button"
                    class="btn btn-outline-success"
                    data-action="set-pass"
                    aria-pressed="{{ $status === TestResult::STATUS_PASS ? 'true' : 'false' }}"
                    {{ ($canUpdate ?? false) ? '' : 'disabled' }}>
                <i class="fas fa-check me-1" aria-hidden="true"></i>{{ trans('tests.status_pass') }}
            </button>
            <button type="button"
                    class="btn btn-outline-danger"
                    data-action="set-fail"
                    aria-pressed="{{ $status === TestResult::STATUS_FAIL ? 'true' : 'false' }}"
                    {{ ($canUpdate ?? false) ? '' : 'disabled' }}>
                <i class="fas fa-xmark me-1" aria-hidden="true"></i>{{ trans('tests.status_fail') }}
            </button>
        </div>
    </div>

    @if(!empty($result['instructions']))
        <section class="collapse testing-drawer" id="instructions-{{ $result['id'] }}">
            <div class="testing-drawer__body small text-muted">
                {!! nl2br(e($result['instructions'])) !!}
            </div>
        </section>
    @endif

    <div class="testing-card__footer">
        <button type="button"
                class="testing-card__cta testing-card__cta--center text-start"
                data-action="toggle-note"
                data-bs-toggle="collapse"
                data-bs-target="#note-{{ $result['id'] }}"
                aria-controls="note-{{ $result['id'] }}"
                aria-expanded="false">
            <span class="testing-card__cta-content">
                <i class="fas fa-note-sticky" aria-hidden="true"></i>
                <span class="testing-card__cta-label">{{ trans('tests.note_cta') }}</span>
            </span>
            <span class="testing-card__cta-indicator {{ $hasNote ? 'is-active' : '' }}"
                  data-note-indicator
                  aria-hidden="true"></span>
        </button>
        <button type="button"
                class="testing-card__cta testing-card__cta--center text-start"
                data-action="toggle-photos"
                data-bs-toggle="collapse"
                data-bs-target="#photos-{{ $result['id'] }}"
                aria-controls="photos-{{ $result['id'] }}"
                aria-expanded="false">
            <span class="testing-card__cta-content">
                <i class="fas fa-camera" aria-hidden="true"></i>
                <span class="testing-card__cta-label">{{ trans('tests.photo_cta') }}</span>
            </span>
            <span class="testing-card__cta-indicator {{ $hasPhoto ? 'is-active' : '' }}"
                  data-photo-indicator
                  aria-hidden="true"></span>
        </button>
    </div>

    <section class="collapse testing-drawer" id="note-{{ $result['id'] }}" data-role="drawer-note">
        <div class="testing-drawer__body">
            <label class="form-label small" for="note-field-{{ $result['id'] }}">{{ trans('tests.note_field_label') }}</label>
            <textarea id="note-field-{{ $result['id'] }}"
                      class="form-control"
                      rows="3"
                      maxlength="2000"
                      data-bind="note"
                      {{ ($canUpdate ?? false) ? '' : 'readonly' }}>{{ $note }}</textarea>
            <small class="text-muted d-block mt-1" data-note-saved>
                @if($result['note_saved_at'])
                    {{ trans('tests.note_saved_at', ['time' => $result['note_saved_at']]) }}
                @endif
            </small>
        </div>
    </section>

        <section class="collapse testing-drawer" id="photos-{{ $result['id'] }}" data-role="drawer-photos">
            <div class="testing-drawer__body">
                <div class="d-flex align-items-center gap-2 flex-nowrap overflow-auto" data-photo-gallery>
                    @forelse($photos as $photo)
                        <div class="testing-photos-thumb" data-photo-node="true" data-photo-id="{{ $photo['id'] }}">
                            <img src="{{ $photo['url'] }}" alt="{{ trans('tests.photo_thumbnail_alt') }}" data-action="open-photo" data-photo-id="{{ $photo['id'] }}">
                            @if(($canUpdate ?? false) && !empty($photo['id']))
                                <button type="button" class="btn btn-link text-danger p-0 small mt-1" data-action="confirm-remove-photo" data-photo-id="{{ $photo['id'] }}">
                                    {{ trans('tests.remove_photo') }}
                                </button>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted small" data-photo-empty>{{ trans('tests.photo_drawer_empty') }}</span>
                    @endforelse
                    @if($canUpdate ?? false)
                        <label class="btn btn-outline-secondary btn-sm mb-0">
                            <i class="fas fa-camera me-1" aria-hidden="true"></i>{{ trans('tests.add_photo_cta') }}
                            <input type="file"
                                   class="d-none"
                                   accept="image/*"
                                   data-action="upload-photo"
                                   multiple>
                        </label>
                    @endif
                </div>
            </div>
        </section>
</article>
