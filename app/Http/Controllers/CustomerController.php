<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::all();
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

}
