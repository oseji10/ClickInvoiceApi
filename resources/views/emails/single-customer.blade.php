{{-- resources/views/emails/single-customer.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: #0A66C2; color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; color: #333; }
        .content p { margin: 0 0 16px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 24px; background: #1e40af; color: white; text-decoration: none; border-radius: 6px; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="container">
    <img src="https://app.clickinvoice.app/images/logo/logo-dark.svg" alt="ClickInvoice Logo" style="max-width: 150px; display: block; margin: 0 auto 10px;">
        <div class="header">
            <h1>Message from {{ $tenantName}} - ({{ $tenantEmail}})</h1>
        </div>
        <div class="content">
            <p>Dear {{ $customerName }},</p>

            {!! nl2br(e($emailMessage)) !!}

            <p>
                Best regards,<br>
                <strong>{{ $tenantName}}</strong>
            </p>
        </div>
        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}. Please do not reply directly.</p>
        </div>
    </div>
</body>
</html>
