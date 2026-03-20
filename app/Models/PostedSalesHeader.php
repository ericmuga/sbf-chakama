<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'member_no', 'posting_date', 'external_doc_no', 'total_amount'])]
class PostedSalesHeader extends Model
{
    use HasFactory;

    protected $table = 'pst_sales_headers';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_no', 'no');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PostedSalesLine::class, 'header_no', 'no');
    }
}
