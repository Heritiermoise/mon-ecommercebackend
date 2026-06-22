<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function mpesaWebhook(Request $request)
    {
        return $this->paymentService->handleMpesaWebhook($request->all());
    }

    public function orangeMoneyWebhook(Request $request)
    {
        return $this->paymentService->handleOrangeMoneyWebhook($request->all());
    }

    public function airtelMoneyWebhook(Request $request)
    {
        // Similaire à Orange Money
        return response()->json(['success' => true]);
    }

    public function stripeWebhook(Request $request)
    {
        return $this->paymentService->handleStripeWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature')
        );
    }
}