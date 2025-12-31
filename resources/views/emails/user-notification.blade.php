<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine ?? 'Notification' }}</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        table {
            border-spacing: 0;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .main {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #0A66C2;
            padding: 40px 20px;
            text-align: center;
        }
        .content {
            padding: 40px 30px;
            color: #334155;
            font-size: 16px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #0A66C2;
            margin: 0 0 20px;
        }
        .subject {
            font-size: 28px;
            color: #1e293b;
            margin: 0 0 30px;
            font-weight: 600;
        }
        .message {
            background-color: #f0f9ff;
            border-left: 4px solid #0A66C2;
            padding: 24px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .btn-container {
            text-align: center;
            margin: 40px 0;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background-color: #0A66C2;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(10,102,194,0.25);
        }
        .footer {
            background-color: #1e293b;
            color: #94a3b8;
            padding: 40px 30px;
            text-align: center;
            font-size: 14px;
        }
        .footer-logo {
            margin-bottom: 20px;
        }
        .tagline {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin: 20px 0;
        }
        .social {
            margin: 25px 0;
        }
        .social a {
            margin: 0 12px;
            opacity: 0.8;
        }
        .social a:hover {
            opacity: 1;
        }
        .links a {
            color: #0A66C2;
            text-decoration: none;
            margin: 0 10px;
        }
        @media screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <center class="wrapper">
        <table class="main" width="100%">
            <!-- Header -->
            <tr>
                <td class="header">
                    <img src="https://app.clickinvoice.app/images/logo/logo-dark.png" alt="ClickInvoice" width="180" height="auto">
                </td>
            </tr>

            <!-- Content -->
            <tr>
                <td class="content">
                    <h1 class="greeting">Hello {{ $user->firstName }},</h1>
                    <h2 class="subject">{{ $subjectLine ?? 'Notification' }}</h2>

                    <div class="message">
                        {!! nl2br(e($messageBody)) !!}
                    </div>

                    <!-- Optional CTA Button -->
                    <!--
                    <div class="btn-container">
                        <a href="#" class="btn">Take Action Now</a>
                    </div>
                    -->
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer">
                    <div class="footer-logo">
                        <img src="https://app.clickinvoice.app/images/logo/logo-dark.png" alt="ClickInvoice" width="150" height="auto">
                    </div>

                    <p class="tagline">Simplifying Invoicing. Powering African Growth. 🚀</p>

                    <div class="social">
                        <!-- Replace with your links -->
                        <a href="#"><img src="https://img.icons8.com/color/48/twitter.png" alt="Twitter" width="32" height="32"></a>
                        <a href="#"><img src="https://img.icons8.com/color/48/linkedin.png" alt="LinkedIn" width="32" height="32"></a>
                        <a href="#"><img src="https://img.icons8.com/color/48/facebook.png" alt="Facebook" width="32" height="32"></a>
                    </div>

                    <p>© {{ date('Y') }} ClickInvoice. All rights reserved.</p>

                    <div class="links">
                        <a href="#">Unsubscribe</a> |
                        <a href="#">Privacy Policy</a> |
                        <a href="#">Contact Us</a>
                    </div>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>