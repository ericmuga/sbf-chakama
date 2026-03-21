<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['posting_date', 'document_no', 'account_no', 'debit_amount', 'credit_amount', 'source_type', 'source_id'])]
class GlEntry extends Model
{
    protected $table = 'gl_entries';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'debit_amount' => 'decimal:4',
            'credit_amount' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }
}
