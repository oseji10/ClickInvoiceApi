<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Models\Payment;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature
        $signature = $request->header('verif-hash');
        if ($signature !== env('FLUTTERWAVE_WEBHOOK_SECRET')) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Flutterwave Webhook', ['event' => $event, 'data' => $data]);

        if ($event === 'charge.completed' && $data['status'] === 'successful') {
            $subscriptionId = $data['meta']['subscription_id'] ?? null;
            $sub = Subscription::find($subscriptionId);

            if ($sub) {
                // Update subscription if it's the initial charge
                if ($sub->status === 'pending') {
                    $sub->update([
                        'flutterwaveSubscriptionId' => $data['customer']['id'], // Or subscription ID if available
                        'status' => 'active',
                        'startDate' => now(),
                        'nextBillingDate' => now()->addMonth(), // Based on interval
                    ]);

                    // Update user plan
                    $sub->user->update(['plan_id' => $sub->plan_id]);
                }

                // Log payment (for initial or recurring)
                Payment::create([
                    'subscriptionId' => $sub->id,
                    'flutterwaveTxRef' => $data['tx_ref'],
                    'flutterwaveTxId' => $data['id'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'status' => 'successful',
                    'responseData' => json_encode($data),
                ]);
            }
        } elseif ($event === 'subscription.disable' || $event === 'subscription.cancel') {
            // Handle cancellation
            $flutterwaveSubId = $data['id'];
            $sub = Subscription::where('flutterwaveSubscriptionSd', $flutterwaveSubId)->first();
            if ($sub) {
                $sub->update(['status' => 'cancelled', 'endDate' => now()]);
                $sub->user->update(['planId' => 1]); // Downgrade to free
            }
        }

        // Add handlers for other events (e.g., charge.failed)

        return response()->json(['status' => 'success'], 200);
    }
}