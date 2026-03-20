<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['no', 'user_id', 'national_id', 'phone', 'member_status', 'is_chakama', 'is_sbf', 'customer_no', 'vendor_no'])]
class Member extends Model
{
    use HasFactory;

    protected $table = 'bus_members';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_chakama' => 'boolean',
            'is_sbf' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function salesHeaders(): HasMany
    {
        return $this->hasMany(SalesHeader::class, 'member_no', 'no');
    }

    public function postedSalesHeaders(): HasMany
    {
        return $this->hasMany(PostedSalesHeader::class, 'member_no', 'no');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(MemberLedgerEntry::class, 'member_no', 'no');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'member_no', 'no');
    }
}
