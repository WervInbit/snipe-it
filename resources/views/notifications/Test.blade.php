@if ($setupCompleted = \App\Models\Setting::setupCompleted())
@component('mail::message')
@endif

{{ trans('mail.test_mail_text', ['app' => $snipeSettings->site_name ?? config('app.name')]) }}

Thanks,
{{ $snipeSettings->site_name ?? config('app.name') }}
@if ($setupCompleted)
@endcomponent
@endif
