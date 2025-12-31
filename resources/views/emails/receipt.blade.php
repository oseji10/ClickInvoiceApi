{{-- resources/views/emails/receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Receipt Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header {
            background-color: #0A66C2; /* Blue theme for receipts */
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content { padding: 20px; background-color: #f9fafb; }
        .footer { margin-top: 20px; padding: 10px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://app.clickinvoice.app/images/logo/logo-dark.svg" alt="Company Logo" style="max-width: 150px; display: block; margin: 0 auto 10px;">

        <div class="header">
            <h1>Payment Receipt</h1>
        </div>

        <div class="content">
            <p>Dear {{ $customerName }},</p>

            <p>Thank you for your payment! Please find attached your official receipt <strong>{{ $receipt->userGeneratedReceiptId ?? $receipt->receiptId }}</strong>.</p>

            <p><strong>Receipt Details:</strong></p>
            <ul>
                <li>Receipt Number: {{ $receipt->receiptId }}</li>
                <li>Project: {{ $receipt->projectName }}</li>
                <li>Date: {{ \Carbon\Carbon::parse($receipt->receiptDate)->format('F d, Y') }}</li>
                <li>Status: {{ strtoupper($receipt->status) }}</li>
            </ul>

            <p>The receipt is attached as a PDF file for your records.</p>

            <p>If you have any questions about this receipt or your payment, please don't hesitate to contact us.</p>

            <p>Best regards,<br>
            {{ $receipt->tenant->tenantName }}</p>
        </div>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
