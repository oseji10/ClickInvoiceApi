
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine ?? 'Notification' }}</title>
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
            <img src="https://app.clickinvoice.app/images/logo/logo-dark.svg" alt="ClickInvoice" width="180">
        </div>

        <div class="content">
            <h1 style="color: #0A66C2;">{{ $subjectLine ?? 'Notification' }}</h1>
            <p>Hello {{ $user->firstName }},</p>
            

            <div class="highlight">
      
                <p>{!! nl2br(e($messageBody)) !!}</p>
            </div>

     </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ClickInvoice. All rights reserved.</p>
            {{-- <p>Made with love for businesses in Africa and beyond.</p> --}}
        </div>
    </div>
</body>
</html>