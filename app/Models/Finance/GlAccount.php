<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'name', 'account_type'])]
class GlAccount extends Model
{
    protected $table = 'gl_accounts';

    protected function casts(): array
    {
        return [];
    }

    public function glEntries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'account_no', 'no');
    }
}
