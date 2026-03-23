<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareNominee extends Model
{
    protected $table = 'share_nominees';

    protected $fillable = [
        'member_id',
        'full_name',
        'national_id',
        'phone',
        'email',
        'relationship',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ShareSubscription::class, 'nominee_id');
    }
}
