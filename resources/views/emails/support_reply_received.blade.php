


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New reply on ticket #{{ $ticket->ticketId }}</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header { background: #0A66C2; padding: 30px; text-align: center; }
        .content { padding: 40px; color: #333; }
        .footer { background: #f1f5f9; padding: 30px; text-align: center; font-size: 14px; color: #64748b; }
        .btn { display: inline-block; padding: 14px 28px; background: #0A66C2; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
        .highlight { background: #f0f9ff; padding: 20px; border-left: 4px solid #0A66C2; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.svg') }}" alt="ClickInvoice" width="180">
        </div>

        <div class="content">
            <h1 style="color: #0A66C2;">New reply on ticket #{{ $ticket->ticketId }}<</h1>
            <p><strong>From:</strong> {{ $user->name }} ({{ $user->email }})</p>
    <p><strong>Subject:</strong> {{ $ticket->subject }}</p>

            <div class="highlight">
                <h2>Reply:</h2>
    <p>{!! nl2br(e($reply->message)) !!}</p>
            </div>

                  </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ClickInvoice. All rights reserved.</p>
            {{-- <p>Made with love for businesses in Africa and beyond.</p> --}}
        </div>
    </div>
</body>
</html>