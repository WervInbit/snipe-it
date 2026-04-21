@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="list-unstyled" style="margin:0;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $class)
    @if (session($key))
        <div class="alert alert-{{ $class }}">
            {{ session($key) }}
        </div>
    @endif
@endforeach
