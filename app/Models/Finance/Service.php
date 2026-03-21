<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'description', 'unit_price', 'service_posting_group_id'])]
class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
        ];
    }

    public function servicePostingGroup(): BelongsTo
    {
        return $this->belongsTo(ServicePostingGroup::class);
    }
}
