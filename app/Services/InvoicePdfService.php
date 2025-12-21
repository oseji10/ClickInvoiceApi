<?php
// app/Services/InvoicePdfService.php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generateInvoicePdf(Invoice $invoice)
    {
        $invoice->load([
            'items',
            'currencyDetail',
            'tenant',
            'customer'
        ]);

        $data = $this->prepareInvoiceData($invoice);

        $pdf = Pdf::loadView('pdf.invoice', $data);

        // Generate filename
        $filename = 'invoice_' . ($invoice->userGeneratedInvoiceId ?? $invoice->invoiceId) . '_' . time() . '.pdf';

        // Save to storage
        $path = 'invoices/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::disk('public')->url($path),
            'pdf_content' => $pdf->output()
        ];
    }

    protected function prepareInvoiceData(Invoice $invoice)
    {
        // Calculate totals
        $subtotal = $invoice->items->sum('amount');
        $taxPercentage = (float) $invoice->taxPercentage;
        $taxAmount = $subtotal * ($taxPercentage / 100);
        $totalAmount = $subtotal + $taxAmount;
        $amountPaid = (float) $invoice->amountPaid;
        $balanceDue = $totalAmount - $amountPaid;

        // Build image URLs
        $baseUrl = config('app.url');
        // $logoUrl = $invoice->tenant->tenantLogo
        //     ? $this->getImageUrl($invoice->tenant->tenantLogo)
        //     : null;
        $logoUrl = "/tenant-logos/bKgsDCxCeRQeO7YfzYZjuozyoFqfMWPo82zGaqJA.png";

        $signatureUrl = $invoice->tenant->authorizedSignature
            ? $this->getImageUrl($invoice->tenant->authorizedSignature)
            : null;

        return [
            'invoice' => $invoice,
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'balanceDue' => $balanceDue,
            'amountPaid' => $amountPaid,
            'currencySymbol' => $invoice->currencyDetail->currencySymbol ?? '₦',
            'currencyCode' => $invoice->currencyDetail->currencyCode ?? 'NGN',
            'logoUrl' => $logoUrl,
            'signatureUrl' => $signatureUrl,
            'companyName' => $invoice->tenant->tenantName,
            'companyEmail' => $invoice->tenant->tenantEmail,
            'companyPhone' => $invoice->tenant->tenantPhone,
            'customerName' => $invoice->customer->customerName ?? $invoice->accountName,
            'customerEmail' => $invoice->customer->customerEmail ?? null,
            'customerPhone' => $invoice->customer->customerPhone ?? null,
            'customerAddress' => $invoice->customer->customerAddress ?? null,
            'projectName' => $invoice->projectName,
            'invoiceDate' => $invoice->invoiceDate,
            'dueDate' => $invoice->dueDate,
            'status' => $invoice->status,
            'invoiceId' => $invoice->invoiceId,
            'userGeneratedInvoiceId' => $invoice->userGeneratedInvoiceId,
            'notes' => $invoice->notes,
            'accountName' => $invoice->accountName,
            'accountNumber' => $invoice->accountNumber,
            'bank' => $invoice->bank,
            'taxPercentage' => $taxPercentage,
            'items' => $invoice->items->map(function ($item) {
                return [
                    'description' => $item->itemDescription,
                    'amount' => (float) $item->amount
                ];
            })
        ];
    }

    protected function getImageUrl($path)
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Check if file exists in storage
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // Return full URL
        return config('app.url') . '/' . ltrim($path, '/');
    }

    public function downloadInvoicePdf(Invoice $invoice)
    {
        $data = $this->prepareInvoiceData($invoice);
        $filename = 'Invoice_' . ($invoice->userGeneratedInvoiceId ?? $invoice->invoiceId) . '.pdf';

        $pdf = Pdf::loadView('pdf.invoice', $data);

        return $pdf->download($filename);
    }

    public function streamInvoicePdf(Invoice $invoice)
    {
        $data = $this->prepareInvoiceData($invoice);
        $pdf = Pdf::loadView('pdf.invoice', $data);

        return $pdf->stream();
    }
}
