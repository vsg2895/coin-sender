@component('mail::message')

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Buttons --}}
@isset($actionText)
@component('mail::button', ['url' => $actionUrl, 'color' => 'success'])
{{ $actionText }}
@endcomponent
@endisset

@isset($action2Text)
@component('mail::button', ['url' => $action2Url, 'color' => 'red'])
{{ $action2Text }}
@endcomponent
@endisset

Regards,<br>{{ config('app.name') }}

{{-- Subcopy --}}
@isset($actionText)
@component('mail::subcopy')
If you’re having trouble clicking the "{{ $actionText }}" button, copy and paste the URL below
into your web browser: [{{ $actionUrl }}]({!! $actionUrl !!})
@endcomponent
@endisset

@isset($action2Text)
@component('mail::subcopy')
If you’re having trouble clicking the "{{ $action2Text }}" button, copy and paste the URL below
into your web browser: [{{ $action2Url }}]({!! $action2Url !!})
@endcomponent
@endisset
@endcomponent
