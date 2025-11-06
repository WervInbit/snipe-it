@php
    $variant = $variant ?? 'primary';
@endphp
<a href="{{ $href }}"
   @isset($dusk)
       dusk="{{ $dusk }}"
   @endisset
   @isset($testid)
       data-testid="{{ $testid }}"
   @endisset
   class="btn btn-{{ $variant }} btn-lg d-flex align-items-center justify-content-center w-100 start-action-button"
   style="min-height:56px; max-width:360px; margin:0 auto 16px;">
    <i class="fas fa-{{ $icon }} me-2" aria-hidden="true"></i>
    <span class="fw-semibold">{{ $label }}</span>
</a>
