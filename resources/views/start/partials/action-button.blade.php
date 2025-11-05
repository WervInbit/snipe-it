<a href="{{ $href }}"
   @isset($dusk)
       dusk="{{ $dusk }}"
   @endisset
   class="btn btn-primary btn-lg btn-block"
   style="max-width:300px;margin:15px auto;">
    <i class="fas fa-{{ $icon }}" aria-hidden="true"></i> {{ $label }}
</a>
