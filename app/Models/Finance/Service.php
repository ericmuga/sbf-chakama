<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'description', 'unit_price', 'is_sellable', 'is_purchasable', 'service_posting_group_id'])]
class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'is_sellable' => 'boolean',
            'is_purchasable' => 'boolean',
        ];
    }

    public function servicePostingGroup(): BelongsTo
    {
        return $this->belongsTo(ServicePostingGroup::class);
    }
}
