<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Finance\MpesaTransaction;
use App\Services\Finance\MpesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(public MpesaService $mpesa) {}

    /**
     * Safaricom posts the STK push result here after the member enters their PIN.
     * This endpoint must be publicly reachable (HTTPS) and CSRF-exempt.
     */
    public function callback(Request $request): JsonResponse
    {
        Log::channel('mpesa')->info('M-Pesa callback received', $request->all());

        try {
            $this->mpesa->processCallback($request->all());
        } catch (\Throwable $e) {
            Log::channel('mpesa')->error('M-Pesa callback error: '.$e->getMessage(), $request->all());
        }

        // Safaricom expects a 200 response
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Query the status of a pending STK push (used for polling from the portal).
     */
    public function queryStatus(Request $request, string $checkoutRequestId): JsonResponse
    {
        // First check if we already have the transaction locally (faster)
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if ($transaction) {
            return response()->json([
                'found' => true,
                'result_code' => $transaction->result_code,
                'TransID' => $transaction->TransID,
                'TransAmount' => $transaction->TransAmount,
                'MSISDN' => $transaction->MSISDN,
                'TransTime' => $transaction->TransTime,
            ]);
        }

        // Fall back to querying Safaricom directly
        $result = $this->mpesa->querySTKStatus($checkoutRequestId);

        return response()->json(array_merge(['found' => false], $result));
    }

    /**
     * TEST ONLY — simulate a completed M-Pesa payment.
     * Creates a fake MpesaTransaction so the portal polling finds it immediately.
     * Only available when mpesa_env = 'local'.
     */
    public function simulatePayment(Request $request): JsonResponse
    {
        if (! $this->mpesa->isLocalMode()) {
            return response()->json(['error' => 'Simulation only available in local/test mode.'], 403);
        }

        $validated = $request->validate([
            'checkout_request_id' => 'required|string',
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $transaction = $this->mpesa->simulateCompletedTransaction(
            $validated['checkout_request_id'],
            $validated['phone'],
            (float) $validated['amount']
        );

        return response()->json([
            'success' => true,
            'TransID' => $transaction->TransID,
            'TransAmount' => $transaction->TransAmount,
            'MSISDN' => $transaction->MSISDN,
            'message' => 'Simulated payment created. The portal will detect it on the next poll.',
        ]);
    }
}
