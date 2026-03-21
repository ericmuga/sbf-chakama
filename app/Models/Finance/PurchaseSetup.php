<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_nos', 'posted_invoice_nos'])]
class PurchaseSetup extends Model
{
    protected $table = 'purchase_setups';

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
}
