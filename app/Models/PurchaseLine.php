<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['header_id', 'description', 'amount', 'project_id'])]
class PurchaseLine extends Model
{
    use HasFactory;

    protected $table = 'doc_purchase_lines';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeader::class, 'header_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
