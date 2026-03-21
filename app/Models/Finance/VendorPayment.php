<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['no', 'number_series_code', 'vendor_id', 'bank_account_id', 'payment_method_id', 'posting_date', 'amount', 'status'])]
class VendorPayment extends Model
{
    use HasFactory;

    protected $table = 'vendor_payments';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'amount' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    protected static function booted(): void
    {
        static::creating(function (VendorPayment $payment): void {
            if (empty($payment->no)) {
                $setup = PurchaseSetup::first();
                if ($setup?->payment_nos) {
                    $payment->no = NumberSeries::generate($setup->payment_nos);
                    $payment->number_series_code = $setup->payment_nos;
                }
            }
        });
    }
}
