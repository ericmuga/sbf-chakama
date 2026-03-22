<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mpesa_env', 'consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'callback_url', 'transaction_type'])]
class MpesaSetup extends Model
{
    public function isLocal(): bool
    {
        return $this->mpesa_env === 'local';
    }

    public function isSandbox(): bool
    {
        return $this->mpesa_env === 'sandbox';
    }

    public function isProduction(): bool
    {
        return $this->mpesa_env === 'production';
    }
}
