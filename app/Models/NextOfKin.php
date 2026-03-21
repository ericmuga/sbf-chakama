<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['member_id', 'name', 'national_id', 'phone', 'email', 'date_of_birth', 'relationship', 'contact_preference'])]
class NextOfKin extends Model
{
    use HasFactory;

    protected $table = 'bus_members';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $query) {
            $query->where('type', 'next_of_kin');
        });

        static::saving(function (NextOfKin $nextOfKin) {
            $nextOfKin->type = 'next_of_kin';
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
