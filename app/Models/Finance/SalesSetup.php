<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_nos', 'posted_invoice_nos', 'customer_nos', 'member_nos', 'receipt_nos'])]
class SalesSetup extends Model
{
    protected $table = 'sales_setups';

    protected function casts(): array
    {
        return [];
    }

    public function invoiceNumberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'invoice_nos', 'code');
    }

    public function postedInvoiceNumberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'posted_invoice_nos', 'code');
    }

    public function customerNumberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'customer_nos', 'code');
    }

    public function memberNumberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'member_nos', 'code');
    }

    public function receiptNumberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'receipt_nos', 'code');
    }
}
