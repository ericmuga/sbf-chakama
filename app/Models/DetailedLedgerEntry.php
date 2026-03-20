<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ledger_entry_no', 'ledger_type', 'entry_type', 'posting_date', 'amount'])]
class DetailedLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ent_detailed_ledger';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
