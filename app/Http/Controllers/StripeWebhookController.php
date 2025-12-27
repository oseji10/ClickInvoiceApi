<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session as StripeSession;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY'));
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object; // Stripe session object
            $metadata = $session->metadata ?? [];
            $courseId = $metadata->course_id ?? null;
            $userId = $metadata->user_id ?? null;

            // TODO: create Enrollment record in DB for $userId & $courseId, mark active
            // Example:
            // Enrollment::create([...]);

            // Optionally generate certificate on completion later
        }

        return response('OK', 200);
    }
}