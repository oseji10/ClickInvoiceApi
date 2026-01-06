<?php
namespace App\Http\Controllers;

use App\Models\Plans;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PlansController extends Controller
{
public function store(Request $request)
{

    $validatedData = request()->validate([
        'planName'      => 'required|string|max:255',
        'price'         => 'required|numeric',
        'currency'      => 'required|integer|exists:currencies,currencyId',
        'features'      => 'required|string',
        'isPopular'     => 'required|boolean',
        'tenantLimit'   => 'required|integer',
        'invoiceLimit'  => 'required|integer',
    ]);

    
    return DB::transaction(function () use ($validatedData) {
        $getCurrency = DB::table('currencies')->where('currencyId', $validatedData['currency'])->first();

        /*
        |--------------------------------------------------------------------------
        | 1. Create Plan on Flutterwave
        |--------------------------------------------------------------------------
        */
         $flutterwaveResponse = Http::withToken(env('FLUTTERWAVE_SECRET_KEY'))
            ->post('https://api.flutterwave.com/v3/payment-plans', [
                'name'     => $validatedData['planName'],
                'amount'   => $validatedData['price'],
                'currency' => $getCurrency->currencyCode, // or map from your currencies table
                'interval' => 'monthly', // adjust if dynamic
            ]);

        if (!$flutterwaveResponse->successful()) {
            throw new \Exception(
                $flutterwaveResponse->json('message') ?? 'Failed to create plan on Flutterwave'
            );
        }

        $flutterwavePlanId = $flutterwaveResponse->json('data.id');

        /*
        |--------------------------------------------------------------------------
        | 2. Store Plan Locally
        |--------------------------------------------------------------------------
        */
        $validatedData['flutterwavePlanId'] = $flutterwavePlanId;
        $plan = Plans::create($validatedData);

        return response()->json([
            'message' => 'Plan created successfully',
            'plan'    => $plan
        ], 201);
    });
}



public function update(Request $request, $planId)
{
    $validatedData = $request->validate([
        'planName'      => 'sometimes|required|string|max:255',
        'price'         => 'sometimes|required|numeric',
        'currency'      => 'sometimes|required|integer|exists:currencies,currencyId',
        'features'      => 'sometimes|required|string',
        'isPopular'     => 'sometimes|required|boolean',
        'tenantLimit'   => 'sometimes|required|integer',
        'invoiceLimit'  => 'sometimes|required|integer',
    ]);

    return DB::transaction(function () use ($validatedData, $planId) {

        $plan = Plans::findOrFail($planId);

        if (!$plan->flutterwavePlanId) {
            throw new \Exception('Flutterwave plan ID not found for this plan.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Update plan on Flutterwave (ONLY allowed fields)
        |--------------------------------------------------------------------------
        */
        $flutterwavePayload = [];

        if (isset($validatedData['planName'])) {
            $flutterwavePayload['name'] = $validatedData['planName'];
        }

        if (isset($validatedData['price'])) {
            $flutterwavePayload['amount'] = $validatedData['price'];
        }

        if (!empty($flutterwavePayload)) {
            $flutterwaveResponse = Http::withToken(env('FLUTTERWAVE_SECRET_KEY'))
                ->put(
                    "https://api.flutterwave.com/v3/payment-plans/{$plan->flutterwavePlanId}",
                    $flutterwavePayload
                );

            if (!$flutterwaveResponse->successful()) {
                throw new \Exception(
                    $flutterwaveResponse->json('message') ?? 'Failed to update Flutterwave plan'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Update local plan
        |--------------------------------------------------------------------------
        */
        $plan->update($validatedData);

        return response()->json([
            'message' => 'Plan updated successfully',
            'plan'    => $plan
        ]);
    });
}


}