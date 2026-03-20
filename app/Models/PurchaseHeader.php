<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'vendor_no', 'doc_type', 'project_id', 'status'])]
class PurchaseHeader extends Model
{
    use HasFactory;

    protected $table = 'doc_purchase_headers';

    public $timestamps = false;

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_no', 'no');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class, 'header_id');
    }
}
