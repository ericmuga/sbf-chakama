<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['posting_date', 'document_no', 'account_no', 'debit_amount', 'credit_amount', 'source_type', 'source_id', 'created_by'])]
class GlEntry extends Model
{
    protected $table = 'gl_entries';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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
