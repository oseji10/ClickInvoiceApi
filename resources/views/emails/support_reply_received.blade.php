<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New reply on ticket #{{ $ticket->ticketId }}</title>
</head>
<body>
    <h1>New reply on ticket #{{ $ticket->ticketId }}</h1>

    <p><strong>From:</strong> {{ $user->name }} ({{ $user->email }})</p>
    <p><strong>Subject:</strong> {{ $ticket->subject }}</p>

    <h2>Reply:</h2>
    <p>{!! nl2br(e($reply->message)) !!}</p>

    <p>
        {{-- <a href="{{ route('admin.support') }}">View ticket in admin panel</a> --}}
    </p>

    <p>Regards,<br>ClickInvoice System</p>
</body>
</html>