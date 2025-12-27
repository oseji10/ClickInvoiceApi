<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User; // Assuming auth

class SubscriptionController extends Controller
{
    public function create(Request $request, $planId)
    {
        $user = auth()->user(); // Or however you get authenticated user
        $plan = Plans::findOrFail($planId);

        if (!$plan->flutterwavePlanId) {
            return response()->json(['error' => 'Invalid plan'], 400);
        }

        // Check if user already has active subscription
        $existingSub = $user->subscription;
        if ($existingSub && $existingSub->status === 'active') {
            return response()->json(['error' => 'You already have an active subscription'], 400);
        }

        // Create pending subscription record
        $subscription = Subscription::create([
            'userId' => $user->id,
            'planId' => $plan->planId,
            'flutterwaveSubscriptionId' => $plan->flutterwaveSubscriptionId,
            'status' => 'pending',
        ]);

        $txRef = 'sub-' . $subscription->id . '-' . time();

        // Initiate payment on Flutterwave
        // Hardcode for testing; remove in production
$ngrokUrl = 'https://otiosely-chronological-cari.ngrok-free.dev'; // Your ngrok URL
// 'redirect_url' => $ngrokUrl . '/subscription/redirect',
        $secretKey = env('FLUTTERWAVE_SECRET_KEY');
        $response = Http::withHeaders(['Authorization' => "Bearer $secretKey"])
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $plan->price, // Overridden by plan, but include for initial charge
                'currency' => 'NGN',
                'payment_plan' => $plan->flutterwave_plan_id, // Enables subscription
                // 'redirect_url' => url('/subscription/redirect'), // Your frontend or backend redirect handler
                'redirect_url' => $ngrokUrl . '/subscription/redirect',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'customizations' => [
                    'title' => $plan->name . ' Subscription',
                    'description' => 'Subscribe to ' . $plan->name,
                ],
                'meta' => [
                    'subscriptionId' => $subscription->subscriptionId, // For webhook
                ],
            ]);

        if ($response->successful()) {
            $link = $response->json()['data']['link'];
            return response()->json(['payment_link' => $link]);
        } else {
            $subscription->delete(); // Cleanup
            return response()->json(['error' => 'Failed to initiate payment'], 500);
        }
    }

    // Handle redirect after payment (optional: can be frontend page that polls backend or shows success)
    public function redirect(Request $request)
    {
        $status = $request->query('status');
        $txRef = $request->query('tx_ref');
        $txId = $request->query('transaction_id');

        if ($status === 'successful') {
            // Verify immediately or let webhook handle
            // Redirect to frontend success page
            return redirect('http://your-frontend.com/plans?success=1');
        } else {
            return redirect('http://your-frontend.com/plans?error=1');
        }
    }


    public function cancel(Request $request)
{
    $user = auth()->user();
    $sub = $user->subscription;

    if (!$sub || $sub->status !== 'active') {
        return response()->json(['error' => 'No active subscription'], 400);
    }

    $secretKey = env('FLUTTERWAVE_SECRET_KEY');
    $response = Http::withHeaders(['Authorization' => "Bearer $secretKey"])
        ->put("https://api.flutterwave.com/v3/subscriptions/{$sub->flutterwave_subscription_id}/cancel");

    if ($response->successful()) {
        $sub->update(['status' => 'cancelled', 'endDate' => now()]);
        $user->update(['planId' => 1]); // Downgrade
        return response()->json(['success' => true]);
    } else {
        return response()->json(['error' => 'Failed to cancel'], 500);
    }
}
}