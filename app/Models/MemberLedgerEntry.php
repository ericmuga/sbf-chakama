<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['entry_no', 'member_no', 'posting_date', 'document_type', 'document_no', 'amount', 'remaining_amount', 'open'])]
class MemberLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ent_member_ledger';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'open' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_no', 'no');
    }
}
