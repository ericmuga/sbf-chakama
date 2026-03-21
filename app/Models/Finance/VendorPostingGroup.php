<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'description', 'payables_account_no'])]
class VendorPostingGroup extends Model
{
    use HasFactory;

    protected $table = 'vendor_posting_groups';

    protected function casts(): array
    {
        return [];
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
