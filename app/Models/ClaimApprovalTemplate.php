<?php

namespace App\Models;

use App\Enums\ClaimType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClaimApprovalTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'claim_type',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'claim_type' => ClaimType::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ClaimApprovalTemplateStep::class, 'template_id')->orderBy('step_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, ClaimType $type): Builder
    {
        return $query->where(function (Builder $q) use ($type) {
            $q->where('claim_type', $type->value)->orWhereNull('claim_type');
        });
    }
}
