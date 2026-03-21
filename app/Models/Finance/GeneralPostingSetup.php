<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_posting_group_id', 'service_posting_group_id', 'sales_account_no'])]
class GeneralPostingSetup extends Model
{
    use HasFactory;

    protected $table = 'general_posting_setups';

    protected function casts(): array
    {
        return [];
    }

    public function customerPostingGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerPostingGroup::class);
    }

    public function servicePostingGroup(): BelongsTo
    {
        return $this->belongsTo(ServicePostingGroup::class);
    }
}
