<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'title', 'budget_lcy', 'total_actual_cost', 'status'])]
class Project extends Model
{
    use HasFactory;

    protected $table = 'bus_projects';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'budget_lcy' => 'decimal:2',
            'total_actual_cost' => 'decimal:2',
        ];
    }

    public function purchaseHeaders(): HasMany
    {
        return $this->hasMany(PurchaseHeader::class);
    }

    public function purchaseLines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function postedPurchaseHeaders(): HasMany
    {
        return $this->hasMany(PostedPurchaseHeader::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(ProjectLedgerEntry::class, 'project_no', 'no');
    }
}
