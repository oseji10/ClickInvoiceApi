<x-mail::message>
# {{ $subjectLine ?? 'Notification' }}

Hello {{ $user->firstName }},

{!! nl2br(e($messageBody)) !!}

<x-mail::button :url="config('app.frontend_url') . '/login'">
    Login to Your Account
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>