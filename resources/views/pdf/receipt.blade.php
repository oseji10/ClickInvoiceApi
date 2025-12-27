{{-- resources/views/pdf/receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $userGeneratedReceiptId ?? $receiptId }}</title>
    <style>
        /* Base styles */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 50px;
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
            color: #16a34a; /* Green theme for receipt */
            white-space: normal;
            word-wrap: break-word;
        }

        .title {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            color: #16a34a;
        }

        .receipt-number {
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
            background-color: #f0fdf4; /* Light green header */
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
            background-color: #dcfce7; /* Softer green */
            font-weight: bold;
            font-size: 12px;
        }

        .paid-row {
            background-color: #bbf7d0; /* Highlight paid amount */
            font-weight: bold;
            font-size: 15px;
            color: #166534;
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
    </style>
</head>
<body>
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

        <div class="title">RECEIPT</div>
        <div class="receipt-number">
            {{ $userGeneratedReceiptId ?? $receiptId }}
        </div>

        <div class="section">
            <div class="row">
                <div>
                    <div class="label">Issued To</div>
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
                    <div class="label">Receipt Date</div>
                    <div>{{ \Carbon\Carbon::parse($receiptDate)->format('d/m/Y') }}</div>

                    <div class="label">Status</div>
                    <div class="value">{{ strtoupper($status) }}</div>

                    <div class="label">Amount Paid</div>
                    <div class="value" style="color: #16a34a;">
                        {{ $currencySymbol }} {{ number_format($amountPaid, 2) }}
                    </div>
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

                <tr class="paid-row">
                    <td class="text-right" style="color: #166534;">Amount Paid</td>
                    <td class="text-right amount" style="color: #166534;">
                        {{ $currencySymbol }} {{ number_format($amountPaid, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Payment Details -->
        <div class="payment-section">
            <div style="font-weight: bold; margin-bottom: 10px; font-size: 12px;">
                Payment Received Via
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
    </div>
</body>
</html>
