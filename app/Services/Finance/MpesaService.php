<?php

namespace App\Services\Finance;

use App\Models\Finance\MpesaSetup;
use App\Models\Finance\MpesaTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MpesaService
{
    // ─── DB-backed configuration (falls back to config/mpesa.php) ───────────

    private function setup(): ?MpesaSetup
    {
        static $setup = null;

        return $setup ??= MpesaSetup::first();
    }

    private function env(): string
    {
        return $this->setup()?->mpesa_env ?? config('mpesa.env', 'local');
    }

    private function shortcode(): string
    {
        return $this->setup()?->shortcode ?? config('mpesa.shortcode', '174379');
    }

    private function passkey(): string
    {
        return $this->setup()?->passkey ?? config('mpesa.passkey', '');
    }

    private function transactionType(): string
    {
        return $this->setup()?->transaction_type ?? config('mpesa.transaction_type', 'CustomerPayBillOnline');
    }

    private function callbackUrl(): string
    {
        $base = $this->setup()?->callback_url ?? config('mpesa.callback_url', config('app.url'));

        return rtrim($base, '/').'/api/mpesa/callback';
    }

    private function baseUrl(): string
    {
        return $this->env() === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function isLocalMode(): bool
    {
        return $this->env() === 'local';
    }

    // ─── OAuth ───────────────────────────────────────────────────────────────

    public function getAccessToken(): string
    {
        $setup = $this->setup();
        $key = $setup?->consumer_key ?? config('mpesa.consumer_key');
        $secret = $setup?->consumer_secret ?? config('mpesa.consumer_secret');
        $credentials = base64_encode("$key:$secret");

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$credentials,
        ])->get($this->baseUrl().'/oauth/v1/generate?grant_type=client_credentials');

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new RuntimeException('Failed to get M-Pesa access token: '.$response->body());
        }

        return $response->json('access_token');
    }

    // ─── STK Push ────────────────────────────────────────────────────────────

    /**
     * Initiate an STK Push.
     * In local mode, returns a fake CheckoutRequestID without hitting Safaricom.
     *
     * @return array<string, mixed>
     */
    public function initiateSTKPush(string $phone, float $amount, string $accountRef): array
    {
        if ($this->isLocalMode()) {
            return $this->fakeSTKPushResponse($phone, $amount);
        }

        $token = $this->getAccessToken();
        $timestamp = $this->getTimestamp();
        $shortCode = $this->shortcode();
        $password = base64_encode($shortCode.$this->passkey().$timestamp);

        $response = Http::withToken($token)
            ->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $this->transactionType(),
                'Amount' => (int) ceil($amount),
                'PartyA' => $this->normalisePhone($phone),
                'PartyB' => $shortCode,
                'PhoneNumber' => $this->normalisePhone($phone),
                'CallBackURL' => $this->callbackUrl(),
                'AccountReference' => substr($accountRef, 0, 12),
                'TransactionDesc' => 'SBF Payment',
            ]);

        return $response->json() ?? [];
    }

    /**
     * Simulate a successful STK push (local/test mode only).
     *
     * @return array<string, mixed>
     */
    private function fakeSTKPushResponse(string $phone, float $amount): array
    {
        $checkoutRequestId = 'ws_CO_TEST_'.strtoupper(Str::random(16));

        return [
            'MerchantRequestID' => 'TEST-'.rand(1000, 9999).'-'.rand(1000, 9999),
            'CheckoutRequestID' => $checkoutRequestId,
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success. Request accepted for processing',
            'CustomerMessage' => 'Success. Request accepted for processing',
            '_local_mode' => true,
            '_phone' => $this->normalisePhone($phone),
            '_amount' => $amount,
        ];
    }

    // ─── Status Query ─────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function querySTKStatus(string $checkoutRequestId): array
    {
        if ($this->isLocalMode()) {
            $tx = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

            return $tx
                ? ['ResultCode' => $tx->result_code ?? 0, 'ResultDesc' => $tx->result_desc ?? 'OK']
                : ['ResultCode' => 1032, 'ResultDesc' => 'Request cancelled by user'];
        }

        $token = $this->getAccessToken();
        $timestamp = $this->getTimestamp();
        $shortCode = $this->shortcode();
        $password = base64_encode($shortCode.$this->passkey().$timestamp);

        $response = Http::withToken($token)
            ->post($this->baseUrl().'/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json() ?? [];
    }

    // ─── Callback ────────────────────────────────────────────────────────────

    public function processCallback(array $payload): MpesaTransaction
    {
        $stkCallback = $payload['Body']['stkCallback'] ?? null;

        if (! $stkCallback) {
            throw new RuntimeException('Invalid callback payload.');
        }

        $resultCode = (int) $stkCallback['ResultCode'];
        $resultDesc = $stkCallback['ResultDesc'] ?? '';

        if ($resultCode !== 0) {
            return MpesaTransaction::create([
                'TransID' => 'FAILED-'.time(),
                'checkout_request_id' => $stkCallback['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $stkCallback['MerchantRequestID'] ?? null,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'is_claimed' => false,
            ]);
        }

        $meta = collect($stkCallback['CallbackMetadata']['Item'] ?? [])
            ->keyBy('Name')
            ->map(fn ($item) => $item['Value'] ?? null);

        return MpesaTransaction::create([
            'TransID' => $meta->get('MpesaReceiptNumber'),
            'TransAmount' => $meta->get('Amount'),
            'MSISDN' => (string) $meta->get('PhoneNumber'),
            'TransTime' => (string) $meta->get('TransactionDate'),
            'BusinessShortCode' => $this->shortcode(),
            'checkout_request_id' => $stkCallback['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $stkCallback['MerchantRequestID'] ?? null,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'is_claimed' => false,
        ]);
    }

    /**
     * Create a fake completed transaction for test/local mode.
     * Called by the test simulate endpoint.
     */
    public function simulateCompletedTransaction(string $checkoutRequestId, string $phone, float $amount): MpesaTransaction
    {
        $receiptNo = 'TEST'.strtoupper(Str::random(7));

        return MpesaTransaction::create([
            'TransID' => $receiptNo,
            'TransAmount' => $amount,
            'MSISDN' => $this->normalisePhone($phone),
            'TransTime' => now('Africa/Nairobi')->format('YmdHis'),
            'BusinessShortCode' => $this->shortcode(),
            'BillRefNumber' => 'SBF-TEST',
            'checkout_request_id' => $checkoutRequestId,
            'result_code' => 0,
            'result_desc' => 'The service request is processed successfully.',
            'is_claimed' => false,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function normalisePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '254'.substr($phone, 1);
        }

        if (str_starts_with($phone, '254') && strlen($phone) === 12) {
            return $phone;
        }

        return $phone;
    }

    private function getTimestamp(): string
    {
        return now('Africa/Nairobi')->format('YmdHis');
    }
}
