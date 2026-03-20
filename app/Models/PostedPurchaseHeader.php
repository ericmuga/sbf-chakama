<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['no', 'vendor_no', 'posting_date', 'project_id', 'total_amount'])]
class PostedPurchaseHeader extends Model
{
    use HasFactory;

    protected $table = 'pst_purchase_headers';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_no', 'no');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
