<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckoutService $stripe)
    {
        try {
            $event = $stripe->eventFromWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Invalid Stripe webhook payload.'], 400);
        }

        if (in_array($event->type, [
            'payment_intent.succeeded',
            'payment_intent.processing',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
            'payment_intent.requires_action',
        ], true)) {
            $stripe->syncOrderFromPaymentIntent($event->data->object);
        }

        return response()->json(['received' => true]);
    }
}
