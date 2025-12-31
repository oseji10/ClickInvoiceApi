<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ $appName }}</title>
      <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; border-radius: 8px; margin: 20px 0; }

        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 20px; }
    </style>
</head>
<body>
  <div class="container">
    <img src="https://app.clickinvoice.app/images/logo/logo-dark.png" alt="ClickInvoice Logo" style="max-width: 150px; display: block; margin: 0 auto 10px;">

  <div class="content">
    <h2>Welcome to {{ $appName }}, {{ $user->name }}!</h2>

    <p>Your account has been successfully activated and you can now log in with your credentials.</p>

    <p>Here are your account details:</p>
    <ul>
        <li>Email: {{ $user->email }}</li>
        <li>Account Status: Active</li>
    </ul>

    <p>If you have any questions, please don't hesitate to contact our support team.</p>

    <p>Best regards,<br>The {{ $appName }} Team</p>

    <div class="footer">
            <p>&copy; {{ date('Y') }} ClickInvoice. A product of ClickBase Technologies Limited. All rights reserved.</p>
            {{-- <p>Powered by ClickBase Technologies Ltd.</p> --}}
        </div>
  </div>
  </div>
</body>
</html>
