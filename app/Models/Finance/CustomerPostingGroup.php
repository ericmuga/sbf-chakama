<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'description', 'receivables_account_no', 'service_charge_account_no'])]
class CustomerPostingGroup extends Model
{
    use HasFactory;

    protected $table = 'customer_posting_groups';

    protected function casts(): array
    {
        return [];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
