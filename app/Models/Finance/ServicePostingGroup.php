<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'description', 'revenue_account_no'])]
class ServicePostingGroup extends Model
{
    use HasFactory;

    protected $table = 'service_posting_groups';

    protected function casts(): array
    {
        return [];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
