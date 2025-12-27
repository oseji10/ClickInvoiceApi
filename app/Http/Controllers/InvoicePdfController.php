<?php
// app/Http/Controllers/InvoicePdfController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;

class InvoicePdfController extends Controller
{
    protected $pdfService;

    public function __construct(InvoicePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function download(Request $request, $id)
    {
        // return $id;
        // return $invoice = Invoice::with(['creator'])
         $invoice = Invoice::with(['items', 'currencyDetail', 'tenant', 'customer', 'creator'])
            ->where('invoiceId', $id)
            ->first();

        return $this->pdfService->downloadInvoicePdf($invoice);
    }


    public function downloadReceipt(Request $request, $id)
    {
        // return $id;
        $receipt = Invoice::with(['items', 'currencyDetail', 'tenant', 'customer'])
            ->where('receiptId', $id)
            ->first();

        return $this->pdfService->downloadReceiptPdf($receipt);
    }

    public function stream($id)
    {
        $invoice = Invoice::with(['items', 'currencyDetail', 'tenant', 'customer'])
            ->findOrFail($id);

        return $this->pdfService->streamInvoicePdf($invoice);
    }

    public function generate($id)
    {
        $invoice = Invoice::with(['items', 'currencyDetail', 'tenant', 'customer'])
            ->findOrFail($id);

        $result = $this->pdfService->generateInvoicePdf($invoice);

        return response()->json([
            'success' => true,
            'data' => [
                'pdf_url' => $result['full_path'],
                'pdf_path' => $result['path'],
                'filename' => $result['filename'],
                'invoice' => $invoice
            ]
        ]);
    }

    public function sendEmail(Request $request, $id)
    {
        $invoice = Invoice::with(['items', 'currencyDetail', 'tenant', 'customer'])
        // ->findOrFail($id);
        ->where('invoiceId', $id)
        ->first();

        $customer = Customer::where('customerId', $invoice->customerId)->first();
        $customerEmail = $customer->customerEmail;
        // $request->validate([
        //     'customer_email' => 'required|email'
        // ]);

        // Generate PDF
        $result = $this->pdfService->generateInvoicePdf($invoice);

        // Send email with PDF attachment
        // $customerEmail = $request->customer_email ?? $invoice->customer->customerEmail;

        if (!$customerEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Customer email not found'
            ], 400);
        }

        try {
            \Mail::send('emails.invoice', [
                'invoice' => $invoice,
                'customerName' => $invoice->customer->customerName ?? $invoice->accountName
            ], function ($message) use ($invoice, $customerEmail, $result) {
                $message->to($customerEmail)
                    ->subject('Invoice: ' . ($invoice->userGeneratedInvoiceId ?? $invoice->invoiceId))
                    ->attachData($result['pdf_content'], $result['filename'], [
                        'mime' => 'application/pdf',
                    ]);
            });

            // Update invoice status if needed
            $invoice->update([
                'sent_at' => now(),
                'sent_to' => $customerEmail
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent successfully to ' . $customerEmail
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send invoice email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }





    public function sendReceiptEmail(Request $request, $id)
    {
        $receipt = Invoice::with(['items', 'currencyDetail', 'tenant', 'customer'])
        // ->findOrFail($id);
        ->where('receiptId', $id)
        ->first();

        $customer = Customer::where('customerId', $receipt->customerId)->first();
        $customerEmail = $customer->customerEmail;
        // $request->validate([
        //     'customer_email' => 'required|email'
        // ]);

        // Generate PDF
        $result = $this->pdfService->generateReceiptPdf($receipt);

        // Send email with PDF attachment
        // $customerEmail = $request->customer_email ?? $invoice->customer->customerEmail;

        if (!$customerEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Customer email not found'
            ], 400);
        }

        try {
            \Mail::send('emails.receipt', [
                'receipt' => $receipt,
                'customerName' => $receipt->customer->customerName ?? $receipt->accountName
            ], function ($message) use ($receipt, $customerEmail, $result) {
                $message->to($customerEmail)
                    ->subject('Receipt: ' . ($receipt->receiptId))
                    ->attachData($result['pdf_content'], $result['filename'], [
                        'mime' => 'application/pdf',
                    ]);
            });

            // Update invoice status if needed
            $receipt->update([
                'sent_at' => now(),
                'sent_to' => $customerEmail
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent successfully to ' . $customerEmail
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send invoice email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
}
