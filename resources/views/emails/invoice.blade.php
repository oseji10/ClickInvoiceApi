{{-- resources/views/emails/invoice.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Invoice Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1e40af; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9fafb; }
        .footer { margin-top: 20px; padding: 10px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
    <img src="{{ asset('images/logo.svg') }}" alt="ClickInvoice Logo" style="max-width: 150px; display: block; margin: 0 auto 10px;">
        <div class="header">
            <h1>Invoice Notification</h1>
        </div>

        <div class="content">
            <p>Dear {{ $customerName }},</p>

            <p>Please find attached your invoice <strong>{{ $invoice->userGeneratedInvoiceId ?? $invoice->invoiceId }}</strong>.</p>

            <p><strong>Invoice Details:</strong></p>
            <ul>
                <li>Invoice Number: {{ $invoice->userGeneratedInvoiceId ?? $invoice->invoiceId }}</li>
                <li>Project: {{ $invoice->projectName }}</li>
                <li>Date: {{ \Carbon\Carbon::parse($invoice->invoiceDate)->format('F d, Y') }}</li>
                <li>Status: {{ strtoupper($invoice->status) }}</li>
            </ul>

            <p>The invoice is attached as a PDF file. You can also view it online if a link was provided.</p>

            <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>

            <p>Best regards,<br>
            {{ $invoice->tenant->tenantName }}</p>
        </div>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
