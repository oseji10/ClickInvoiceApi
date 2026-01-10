<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->userGeneratedInvoiceId ?? $invoice->invoiceId }}</title>
    <style>
        /* PDF Styles */
        @page {
            margin: 50px;
            size: A4;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
        }

        .logo {
            width: 150px;
            height: auto;
            object-fit: contain;
        }

        .company-info {
            text-align: right;
            max-width: 200px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #0A66C2;
        }

        .title {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            color: #0A66C2;
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
            margin-bottom: 4px;
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
        }

        .table {
            width: 100%;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-header {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .cell-right {
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

        .signature-image {
            width: 180px;
            height: 80px;
            object-fit: contain;
        }

        .signature-label {
            margin-top: 10px;
            text-align: center;
            font-size: 10px;
        }

        .page-break {
            page-break-before: always;
        }

        /* Utility classes */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mt-8 {
            margin-top: 32px;
        }

        .w-full {
            width: 100%;
        }

        /* Invoice specific */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .invoice-table th {
            background-color: #f8fafc;
            font-weight: 600;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .amount-cell {
            text-align: right;
            font-family: 'Helvetica', sans-serif;
        }

        .total-section {
            margin-top: 20px;
            width: 100%;
        }

        .total-row-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .balance-due {
            background-color: #fffbeb;
            color: #d97706;
            font-weight: bold;
            font-size: 15px;
            padding: 12px 0;
            margin-top: 10px;
        }

        /* Status colors */
        .status-unpaid {
            color: #dc2626;
            font-weight: bold;
        }

        .status-paid {
            color: #059669;
            font-weight: bold;
        }

        .status-overdue {
            color: #ea580c;
            font-weight: bold;
        }

        .status-partial {
            color: #d97706;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" class="logo" alt="Company Logo">
        @else
            <div style="width: 150px;"></div>
        @endif
        
        <div class="company-info">
            <div class="company-name">{{ $companyName }}</div>
            <div>{{ $companyEmail }}</div>
            <div>{{ $companyPhone }}</div>
        </div>
    </div>

    <h1 class="title">INVOICE</h1>
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
                <div class="value">
                    @php
                        $statusClass = 'status-' . strtolower($status);
                    @endphp
                    <span class="{{ $statusClass }}">{{ $status }}</span>
                </div>
            </div>
        </div>
    </div>

    <table class="table">
        <thead class="table-header">
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                <td class="cell-right">
                    {{ $currencySymbol }} {{ number_format($item['amount'], 2) }}
                </td>
            </tr>
            @endforeach
            
            <tr class="total-row">
                <td class="text-right">Subtotal</td>
                <td class="cell-right">
                    {{ $currencySymbol }} {{ number_format($subtotal, 2) }}
                </td>
            </tr>
            
            <tr class="total-row">
                <td class="text-right">Tax ({{ $taxPercentage }}%)</td>
                <td class="cell-right">
                    {{ $currencySymbol }} {{ number_format($taxAmount, 2) }}
                </td>
            </tr>
            
            <tr class="total-row">
                <td class="text-right">Total</td>
                <td class="cell-right">
                    {{ $currencySymbol }} {{ number_format($totalAmount, 2) }}
                </td>
            </tr>
            
            <tr class="balance-row">
                <td class="text-right" style="color: #d97706;">Balance Due</td>
                <td class="cell-right" style="color: #d97706;">
                    {{ $currencySymbol }} {{ number_format($balanceDue, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="payment-section">
        <div style="font-weight: bold; margin-bottom: 10px; font-size: 12px;">
            Payment Details
        </div>
        <div>Account Name: {{ $accountName }}</div>
        <div>Account Number: {{ $accountNumber }}</div>
        <div>Bank: {{ $bank }}</div>
    </div>

    @if($invoice->notes)
    <div class="notes">
        <div style="font-weight: bold; margin-bottom: 8px;">Notes</div>
        <div>{{ $notes }}</div>
    </div>
    @endif

    @if($signatureUrl)
    <div class="signature-section">
        <div>
            <img src="{{ $signatureUrl }}" class="signature-image" alt="Signature">
            <div class="signature-label">Authorized Signature</div>
        </div>
    </div>
    @endif
</body>
</html>