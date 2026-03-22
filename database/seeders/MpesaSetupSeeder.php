<?php

namespace Database\Seeders;

use App\Models\Finance\MpesaSetup;
use Illuminate\Database\Seeder;

class MpesaSetupSeeder extends Seeder
{
    public function run(): void
    {
        MpesaSetup::updateOrCreate(['id' => 1], [
            /*
            |----------------------------------------------------------------
            | Default: LOCAL TEST MODE
            |----------------------------------------------------------------
            | mpesa_env = 'local'  → No real Safaricom calls.
            |   The portal will show a "Simulate Payment (Test)" button
            |   on the pending screen so you can complete payments without
            |   any Safaricom credentials.
            |
            | To switch to Safaricom sandbox:
            |   1. Go to Admin → Finance → M-Pesa Setup
            |   2. Change Environment to "Sandbox"
            |   3. Fill in credentials from developer.safaricom.co.ke
            |   4. Set Callback URL to an HTTPS URL Safaricom can reach
            |      (use ngrok / Expose / Cloudflare Tunnel in dev)
            |
            | Safaricom sandbox test credentials (public):
            |   Shortcode:  174379
            |   Passkey:    bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
            |   Consumer Key / Secret: get yours at developer.safaricom.co.ke
            |----------------------------------------------------------------
            */
            'mpesa_env' => 'local',
            'shortcode' => '174379',
            'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
            'consumer_key' => null,
            'consumer_secret' => null,
            'callback_url' => config('app.url'),
            'transaction_type' => 'CustomerPayBillOnline',
        ]);
    }
}
