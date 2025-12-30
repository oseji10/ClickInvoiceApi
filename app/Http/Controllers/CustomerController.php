<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Mail;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('tenant')->get();
        return response()->json($customers);

    }

public function getTenantCustomers(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $customers = Customer::where('tenantId', $tenantId)
        ->get();
        return response()->json($customers);

    }


    public function storeTenantCustomer(Request $request)
    {
        // Directly get the data from the request
        $tenantId = $request->header('X-Tenant-ID');
       $validated =  $request->validate([
            'customerName' => 'nullable|string|max:255',
            'customerEmail' => 'nullable|string|max:255',
            'customerAddress' => 'nullable|string|max:255',
            'customerPhone' => 'nullable|string|max:255',
        ]);

        $validated['tenantId'] = $tenantId;
        // Create a new user with the data (ensure that the fields are mass assignable in the model)
        $customers = Customer::create($validated);

        // Return a response, typically JSON
        return response()->json($customers, 201); // HTTP status code 201: Created
    }

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();

        // Create a new user with the data (ensure that the fields are mass assignable in the model)
        $roles = Role::create($data);

        // Return a response, typically JSON
        return response()->json($roles, 201); // HTTP status code 201: Created
    }



    /**
     * GET /api/customers
     * Fetch all customers for the authenticated tenant/user
     */
    // public function getTenantCustomers()
    // {
    //     // Adjust query based on your tenancy setup (e.g., tenant_id)
    //     $customers = Customer::select([
    //             'customerId',
    //             'customerName',
    //             'customerEmail',
    //             'customerPhone',
    //             'customerAddress',
    //             'created_at as createdAt',
    //         ])
    //         ->orderBy('customerName')
    //         ->get();

    //     return response()->json($customers);
    // }

    /**
     * POST /api/customers/send-email
     * Send a custom email to one or more selected customers
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'customerIds' => 'required|array|min:1',
            'customerIds.*' => 'exists:customers,customerId',
            'subject'     => 'required|string|max:255',
            'message'     => 'required|string',
        ]);

        $customerIds = $request->input('customerIds');
        $subject     = $request->input('subject');
        $message     = $request->input('message');

        $customers = Customer::whereIn('customerId', $customerIds)
            ->whereNotNull('customerEmail')
            ->get();

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No customers with valid email addresses found.'
            ], 400);
        }

        $sentCount = 0;

        foreach ($customers as $customer) {
            try {
                Mail::raw($message, function ($mail) use ($customer, $subject) {
                    $mail->to($customer->customerEmail)
                         ->subject($subject)
                         ->from(config('mail.from.address'), config('mail.from.name'));
                });

                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send email to {$customer->customerEmail}: " . $e->getMessage());
                // Continue sending to others even if one fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Email sent successfully to {$sentCount} customer(s)."
        ]);
    }




public function sendSingleEmail(Request $request, $customerId)
{
    $tenantId = $request->header('X-Tenant-ID');
    $request->validate([
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    $customer = Customer::where('customerId', $customerId)
        ->whereNotNull('customerEmail')
        ->where('customerEmail', '!=', '')
        ->where('tenantId', $tenantId)
        ->with('tenant')
        ->firstOrFail();

    try {
        Mail::send('emails.single-customer', [
            'customerName' => $customer->customerName,
            'subject'      => $request->subject,
            'emailMessage'      => $request->message,
            'tenantName' => $customer->tenant->tenantName,
            'tenantEmail' => $customer->tenant->tenantEmail,
        ], function ($mail) use ($customer, $request) {
            $mail->to($customer->customerEmail)
                 ->subject($request->subject)
                 ->from(config('mail.from.address'), config('mail.from.name'));
        });

        return response()->json([
            'success' => true,
            'message' => "Email sent successfully to {$customer->customerName}"
        ]);
    } catch (\Exception $e) {
        \Log::error("Single email failed for {$customer->customerEmail}: " . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to send email.'
        ], 500);
    }
}

public function broadcastEmail(Request $request)
{
    $tenantId = $request->header('X-Tenant-ID');
    $request->validate([
        'customerIds' => 'nullable|array',
        'customerIds.*' => 'integer|exists:customers,customerId',
        'subject'     => 'required|string|max:255',
        'message'     => 'required|string',
    ]);

    $selectedIds = $request->input('customerIds', []);

    $query = Customer::whereNotNull('customerEmail')
    ->where('customerEmail', '!=', '')
    ->where('tenantId', $tenantId)
    ->with('tenant');

    if (!empty($selectedIds)) {
        $query->whereIn('customerId', $selectedIds);
    }

    $customers = $query->get();

    if ($customers->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No customers with email addresses found.'
        ], 400);
    }

    $sentCount = 0;
    $failed = [];

    foreach ($customers as $customer) {
        try {
            Mail::send('emails.broadcast', [
                'subject' => $request->subject,
                'emailMessage' => $request->message,
                'tenantName' => $customer->tenant->tenantName,
            'tenantEmail' => $customer->tenant->tenantEmail,
            ], function ($mail) use ($customer, $request) {
                $mail->to($customer->customerEmail)
                     ->subject($request->subject)
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $sentCount++;
        } catch (\Exception $e) {
            $failed[] = $customer->customerEmail;
            \Log::error("Broadcast failed for {$customer->customerEmail}: " . $e->getMessage());
        }
    }

    return response()->json([
        'success' => true,
        'message' => "Broadcast sent to {$sentCount} out of {$customers->count()} customer(s).",
        'sent'    => $sentCount,
        'failed'  => $failed
    ]);
}


}


