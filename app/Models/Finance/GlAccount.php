<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['no', 'name', 'account_type'])]
class GlAccount extends Model
{
    protected $table = 'gl_accounts';

    protected function casts(): array
    {
        return [];
    }
}
