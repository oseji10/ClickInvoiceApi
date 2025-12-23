<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Store a new invoice with items, optional tax, and amountPaid.
     */
    public function store(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $request->validate([
            'invoiceId' => 'required|unique:invoices,invoiceId',
            'projectName' => 'nullable|string|max:255',
            'invoiceDate' => 'nullable|date',
            'dueDate' => 'nullable|date',
            'currency' => 'nullable|exists:currencies,currencyId',
            'tenantId' => 'nullable|exists:tenants,tenantId',
            'createdBy' => 'nullable|exists:users,id',
            'taxPercentage' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.itemDescription' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'amountPaid' => 'nullable|numeric|min:0',
        ]);

        // Calculate total of items
        $totalAmount = collect($request->items)->sum(fn($item) => $item['amount']);

        // Apply tax if provided
        $tax = $request->input('taxPercentage', 0);
        $totalAmountWithTax = $tax > 0 ? $totalAmount * (1 + $tax / 100) : $totalAmount;

        // Determine amountPaid and balanceDue
        $amountPaid = $request->input('amountPaid', 0);
        $balanceDue = $totalAmountWithTax - $amountPaid;

        $getCurrency = Tenant::where('tenantId', $tenantId)->first();
        // Create invoice
        $invoice = Invoice::create(array_merge(
            $request->only([
                'invoiceId',
                'userGeneratedInvoiceId',
                'projectName',
                'invoiceDate',
                'dueDate',
                'invoicePassword',
                'notes',
                'currency',
                'accountName',
                'accountNumber',
                'bank',
                'taxPercentage',
                'customerId'
            ]),
            [
                'amountPaid' => $amountPaid,
                'balanceDue' => $balanceDue,
                'tenantId' => $tenantId,
                'createdBy' => auth()->id() ,
                'currency' => $getCurrency->currency,
            ]
        ));

        // Create invoice items
        foreach ($request->items as $item) {
            $invoice->items()->create([
                'itemDescription' => $item['itemDescription'],
                'amount' => $item['amount'],
            ]);
        }

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice->load('items')
        ], 201);
    }

    /**
     * Get all invoices for the authenticated user.
     */
    public function getUserInvoices(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $userId = Auth::id();

        $invoices = Invoice::with('items', 'currencyDetail', 'customer')
            ->where('createdBy', $userId)
            ->where('tenantId', $tenantId)
            ->get();

        return response()->json($invoices);
    }


    public function getUserReceipts(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $userId = Auth::id();

        $invoices = Invoice::with('items', 'currencyDetail', 'customer')
            ->where('createdBy', $userId)
            ->where('tenantId', $tenantId)
            ->where('status', 'PAID')
            ->orWhere('status', 'PARTIAL_PAYMENT')
            ->get();

        return response()->json($invoices);
    }

     public function getLast5UserInvoices(Request $request)
{
    $tenantId = $request->header('X-Tenant-ID');
    $userId = Auth::id();

    if (!$tenantId) {
        return response()->json([
            'message' => 'Tenant ID is missing'
        ], 400);
    }

    $invoices = Invoice::with([
            'items',
            'currencyDetail',
            'customer',
        ])
        ->where('createdBy', $userId)
        ->where('tenantId', $tenantId)
        ->latest()   // orders by created_at desc
        ->limit(5)
        ->get();

    return response()->json($invoices, 200);
}


public function invoiceSummary(Request $request)
{
    $tenantId = $request->header('X-Tenant-ID');
    $userId = Auth::id();

    if (!$tenantId) {
        return response()->json([
            'message' => 'Tenant ID is missing'
        ], 400);
    }

    $summary = Invoice::where('tenantId', $tenantId)
        ->where('createdBy', $userId)
        ->selectRaw('
            COALESCE(SUM(amountPaid), 0) as collected,
            COALESCE(SUM(balanceDue), 0) as outstanding
        ')
        ->first();

    return response()->json([
        'collected' => (float) $summary->collected,
        'outstanding' => (float) $summary->outstanding,
    ]);
}



    /**
     * Get a single invoice by tenant ID.
     */
    public function getInvoiceByTenant($tenantId)
    {
        $invoice = Invoice::with('items', 'currencyDetail',)
            ->where('tenantId', $tenantId)
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found for this tenant'], 404);
        }

        return response()->json($invoice);
    }

public function getInvoiceByInvoiceId(Request $request, $invoiceId)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $userId = Auth::id();

        $invoice = Invoice::with('items', 'currencyDetail', 'tenant', 'customer')
            ->where('createdBy', $userId)
            ->where('invoiceId', $invoiceId)
            ->where('tenantId', $tenantId)
            ->get();

        return response()->json($invoice);
    }


    public function getReceiptByReceiptId(Request $request, $receiptId)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $userId = Auth::id();

        $invoice = Invoice::with('items', 'currencyDetail', 'tenant', 'customer')
            ->where('createdBy', $userId)
            ->where('receiptId', $receiptId)
            ->where('tenantId', $tenantId)
            ->get();

        return response()->json($invoice);
    }

public function getInvoiceAndReceiptsByCustomerId(Request $request, $customerId)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $userId = Auth::id();

        $invoice = Invoice::with('items', 'currencyDetail', 'tenant', 'customer')
            ->where('createdBy', $userId)
            ->where('customerId', $customerId)
            ->where('tenantId', $tenantId)
            ->get();

        return response()->json($invoice);
    }







//     public function updateInvoiceStatus(Request $request, $invoiceId)
// {
//     $invoice = Invoice::where('invoiceId', $invoiceId)->first();

//     if (!$invoice) {
//         return response()->json([
//             'message' => 'Invoice not found'
//         ], 404);
//     }

//     $validated = $request->validate([
//         'status' => 'required|string',
//         'amountPaid' => 'nullable|numeric|min:0'
//     ]);

//     $status = strtoupper($validated['status']);

//     // 🔹 PAID: clear balance, move everything to amountPaid
//     if ($status === 'PAID') {
//         $invoice->amountPaid = $invoice->amountPaid + $invoice->balanceDue;
//         $invoice->balanceDue = 0;
//         $invoice->status = 'PAID';
//     }

//     // 🔹 PARTIAL PAYMENT: use amount sent from frontend
//     elseif ($status === 'PARTIAL_PAYMENT') {
//         if (!isset($validated['amountPaid'])) {
//             return response()->json([
//                 'message' => 'amountPaid is required for partial payment'
//             ], 422);
//         }

//         $partialAmount = (float) $validated['amountPaid'];

//         if ($partialAmount > $invoice->balanceDue) {
//             return response()->json([
//                 'message' => 'Amount paid cannot exceed balance due'
//             ], 422);
//         }

//         $invoice->amountPaid += $partialAmount;
//         $invoice->balanceDue -= $partialAmount;
//         $invoice->status = 'PARTIAL_PAYMENT';
//     }

//     // 🔹 Other statuses (optional handling)
//     else {
//         $invoice->status = $status;
//     }

//     $invoice->save();

//     return response()->json([
//         'message' => 'Invoice updated successfully',
//         'invoice' => $invoice
//     ]);
// }

public function updateInvoiceStatus(Request $request, $invoiceId)
{
    $invoice = Invoice::where('invoiceId', $invoiceId)->first();

    if (!$invoice) {
        return response()->json([
            'message' => 'Invoice not found'
        ], 404);
    }

    $validated = $request->validate([
        'status' => 'required|string',
        'amountPaid' => 'nullable|numeric|min:0'
    ]);

    $status = strtoupper($validated['status']);

    /**
     * Generate receipt ID ONLY if:
     * - Status is PAID or PARTIAL_PAYMENT
     * - AND receiptId does not already exist
     */
    $receiptId = strtoupper(Str::random(2)) . mt_rand(1000000000, 9999999999);
    $shouldGenerateReceipt =
        in_array($status, ['PAID', 'PARTIAL_PAYMENT']) &&
        empty($invoice->receiptId);

    if ($shouldGenerateReceipt) {
        $invoice->receiptId = 'RCPT-' . $receiptId;
    }

    // 🔹 PAID: clear balance, move everything to amountPaid
    if ($status === 'PAID') {
        $invoice->amountPaid += $invoice->balanceDue;
        $invoice->balanceDue = 0;
        $invoice->status = 'PAID';
    }

    // 🔹 PARTIAL PAYMENT
    elseif ($status === 'PARTIAL_PAYMENT') {
        if (!isset($validated['amountPaid'])) {
            return response()->json([
                'message' => 'amountPaid is required for partial payment'
            ], 422);
        }

        $partialAmount = (float) $validated['amountPaid'];

        if ($partialAmount > $invoice->balanceDue) {
            return response()->json([
                'message' => 'Amount paid cannot exceed balance due'
            ], 422);
        }

        $invoice->amountPaid += $partialAmount;
        $invoice->balanceDue -= $partialAmount;
        $invoice->status = 'PARTIAL_PAYMENT';
    }

    // 🔹 Other statuses
    else {
        $invoice->status = $status;
    }

    $invoice->save();

    return response()->json([
        'message' => 'Invoice updated successfully',
        'invoice' => $invoice
    ]);
}


}
