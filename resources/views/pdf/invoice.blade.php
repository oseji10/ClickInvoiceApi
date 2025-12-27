{{-- resources/views/pdf/invoice.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->userGeneratedInvoiceId ?? $invoice->invoiceId }}</title>
    <style>
        /* Base styles */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
            position: relative; /* For watermark positioning */
        }

        .container {
            padding: 50px;
        }

        /* Watermark Logo */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.15;
            pointer-events: none;
            z-index: 10;
            text-align: center;
        }

        .watermark img {
            width: 400px;
            height: auto;
        }

        .watermark-note {
            margin-top: 15px;
            font-size: 18px;
            color: #666;
            font-weight: bold;
        }

        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
        }

        .logo-container {
            width: 150px;
        }

        .logo {
            max-width: 100%;
            height: auto;
        }

        .company-info {
            flex: 1;
            text-align: right;
            padding-left: 20px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #1e40af;
            white-space: normal;
            word-wrap: break-word;
        }

        .title {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            color: #1e40af;
        }

        .invoice-number {
            text-align: center;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .section {
            margin-bottom: 25px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .label {
            color: #666;
            font-size: 10px;
        }

        .value {
            font-size: 11px;
            font-weight: bold;
        }

        .customer-address {
            font-size: 10px;
            margin-top: 4px;
            color: #666;
            max-width: 200px;
            white-space: pre-line;
        }

        .table {
            width: 100%;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
            border-collapse: collapse;
        }

        .table th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table .text-right {
            text-align: right;
        }

        .total-row {
            background-color: #eff6ff;
            font-weight: bold;
            font-size: 12px;
        }

        .balance-row {
            background-color: #fffbeb;
            font-weight: bold;
            font-size: 15px;
        }

        .payment-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0fdf4;
            border-radius: 6px;
        }

        .notes {
            margin-top: 40px;
            font-size: 11px;
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
        }

        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-container {
            text-align: center;
        }

        .signature-image {
            max-width: 180px;
            height: 60px;
            object-fit: contain;
        }

        .signature-label {
            margin-top: 10px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .logo-placeholder {
            width: 150px;
            height: 60px;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 10px;
        }

        .signature-placeholder {
            width: 180px;
            height: 60px;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-bottom: 3px solid #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 10px;
        }

        .amount {
            font-family: 'DejaVu Sans', monospace;
        }

        /* Footer note */
        .footer-note {
            margin-top: 60px;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding: 15px;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <!-- Watermark Logo -->
    <!-- <div class="watermark">
        <img src="https://clickinvoice.app/logo.png" alt="ClickInvoice Logo">
        <div class="watermark-note">Remove this logo for just $2</div>
    </div> -->

            @if((int) $current_plan === 1)
    <div class="watermark">
         <img src="https://app.clickinvoice.clickbase.tech/images/logo/logo.svg" alt="ClickInvoice Logo">
        <div class="watermark-note">Remove this logo for just $2</div>
    </div>
@endif

    <div class="container">
        <!-- Company Header -->
        <div class="header">
            <div class="logo-container">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Company Logo" class="logo">
                @else
                    <div class="logo-placeholder">Company Logo</div>
                @endif
            </div>
            <div class="company-info">
                <div class="company-name">{{ $companyName }}</div>
                <div>{{ $companyEmail }}</div>
                <div>{{ $companyPhone }}</div>
            </div>
        </div>

        <div class="title">INVOICE</div>
        <div class="invoice-number">
            {{ $userGeneratedInvoiceId ?? $invoiceId }}
        </div>

        <div class="section">
            <div class="row">
                <div>
                    <div class="label">Bill To</div>
                    <div class="value">{{ $customerName }}</div>
                    @if($customerAddress)
                        <div class="customer-address">{{ $customerAddress }}</div>
                    @endif
                    @if($customerEmail)
                        <div>{{ $customerEmail }}</div>
                    @endif
                    @if($customerPhone)
                        <div>{{ $customerPhone }}</div>
                    @endif
                </div>
                <div>
                    <div class="label">Invoice Date</div>
                    <div>{{ \Carbon\Carbon::parse($invoiceDate)->format('d/m/Y') }}</div>
                    @if($dueDate)
                        <div class="label">Due Date</div>
                        <div>{{ \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }}</div>
                    @endif
                    <div class="label">Status</div>
                    <div class="value">{{ strtoupper($status) }}</div>

                    <div class="label">Amount Paid</div>
                    <div class="value">{{ $currencySymbol }} {{ number_format($amountPaid, 2) }}</div>

                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-right amount">
                        {{ $currencySymbol }} {{ number_format($item['amount'], 2) }}
                    </td>
                </tr>
                @endforeach

                <tr class="total-row">
                    <td class="text-right">Subtotal</td>
                    <td class="text-right amount">
                        {{ $currencySymbol }} {{ number_format($subtotal, 2) }}
                    </td>
                </tr>

                <tr class="total-row">
                    <td class="text-right">Tax ({{ $taxPercentage }}%)</td>
                    <td class="text-right amount">
                        {{ $currencySymbol }} {{ number_format($taxAmount, 2) }}
                    </td>
                </tr>

                <tr class="total-row">
                    <td class="text-right">Total</td>
                    <td class="text-right amount">
                        {{ $currencySymbol }} {{ number_format($totalAmount, 2) }}
                    </td>
                </tr>

                <tr class="balance-row">
                    <td class="text-right" style="color: #d97706;">Balance Due</td>
                    <td class="text-right amount" style="color: #d97706;">
                        {{ $currencySymbol }} {{ number_format($balanceDue, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Payment Details -->
        <div class="payment-section">
            <div style="font-weight: bold; margin-bottom: 10px; font-size: 12px;">
                Payment Details
            </div>
            <div>Account Name: {{ $accountName }}</div>
            <div>Account Number: {{ $accountNumber }}</div>
            <div>Bank: {{ $bank }}</div>
        </div>

        <!-- Notes -->
        @if($notes)
        <div class="notes">
            <div style="font-weight: bold; margin-bottom: 8px;">Notes</div>
            <div>{{ $notes }}</div>
        </div>
        @endif

        <!-- Authorized Signature -->
        <div class="signature-section">
            <div class="signature-container">
                @if($signatureUrl)
                    <img src="{{ $signatureUrl }}" alt="Authorized Signature" class="signature-image">
                @else
                    <div class="signature-placeholder">Authorized Signature</div>
                @endif
                <div class="signature-label">Authorized Signature</div>
            </div>
        </div>

        <!-- Footer Note -->
         @if((int) $current_plan === 1)
    <div class="footer-note">
        This invoice was generated at
        <strong>
            <a href="https://clickinvoice.app" target="_blank">
                clickinvoice.app
            </a>
        </strong>.
        Visit ClickInvoice to begin generating yours.
    </div>
@endif

    </div>
</body>
</html>