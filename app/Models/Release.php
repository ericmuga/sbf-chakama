<?php

namespace App\Models;

use App\Enums\ReleaseStatus;
use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['version', 'name', 'status', 'released_on', 'notes'])]
class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use HasFactory;

    protected $table = 'app_releases';

    protected function casts(): array
    {
        return [
            'status' => ReleaseStatus::class,
            'released_on' => 'date',
        ];
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
