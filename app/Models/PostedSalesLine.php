<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['header_no', 'description', 'amount'])]
class PostedSalesLine extends Model
{
    use HasFactory;

    protected $table = 'pst_sales_lines';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(PostedSalesHeader::class, 'header_no', 'no');
    }
}
