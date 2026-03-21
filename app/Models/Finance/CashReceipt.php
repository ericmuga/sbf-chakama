<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['no', 'number_series_code', 'customer_id', 'bank_account_id', 'payment_method_id', 'posting_date', 'amount', 'status'])]
class CashReceipt extends Model
{
    use HasFactory;

    protected $table = 'cash_receipts';

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        static::creating(function (CashReceipt $receipt): void {
            if (empty($receipt->no)) {
                $setup = SalesSetup::first();
                if ($setup?->receipt_nos) {
                    $receipt->no = NumberSeries::generate($setup->receipt_nos);
                    $receipt->number_series_code = $setup->receipt_nos;
                }
            }
        });
    }
}
