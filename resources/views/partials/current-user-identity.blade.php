<li class="dropdown-header" data-testid="current-user-identity">
    <div>{{ trans('general.signed_in_as', ['name' => $currentUser->getFullNameAttribute()]) }}</div>
    <small>{{ trans('general.username') }}: {{ $currentUser->username }}</small>
</li>
<li class="divider"></li>
