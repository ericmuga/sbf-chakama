<?php

namespace App\Models;

use App\Enums\ClaimPaymentMethod;
use App\Models\Finance\Customer as FinanceCustomer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\Vendor as FinanceVendor;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['no', 'user_id', 'identity_no', 'identity_type', 'phone', 'member_status', 'is_chakama', 'is_sbf', 'customer_no', 'vendor_no', 'name', 'type', 'member_id', 'email', 'date_of_birth', 'relationship', 'contact_preference', 'bank_name', 'bank_account_name', 'bank_account_no', 'bank_branch', 'mpesa_phone', 'preferred_payment_method', 'exclude_from_billing'])]
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
            'date_of_birth' => 'date',
            'preferred_payment_method' => ClaimPaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->no)) {
                $setup = SalesSetup::first();
                if ($setup?->member_nos) {
                    $member->no = NumberSeries::generate($setup->member_nos);
                }
            }
        });

        static::created(function (Member $member) {
            $salesSetup = SalesSetup::first();
            $purchaseSetup = PurchaseSetup::first();
            $cpg = CustomerPostingGroup::where('code', 'MEMBER')->first();
            $vpg = VendorPostingGroup::where('code', 'MEMBER')->first();
            $displayName = $member->name ?? $member->user?->name ?? $member->no;

            if ($salesSetup?->customer_nos && $cpg) {
                $customerNo = NumberSeries::generate($salesSetup->customer_nos);
                $member->updateQuietly(['customer_no' => $customerNo]);
                FinanceCustomer::create([
                    'no' => $customerNo,
                    'name' => $displayName,
                    'customer_posting_group_id' => $cpg->id,
                ]);
            }

            if ($purchaseSetup?->vendor_nos && $vpg) {
                $vendorNo = NumberSeries::generate($purchaseSetup->vendor_nos);
                $member->updateQuietly(['vendor_no' => $vendorNo]);
                FinanceVendor::create([
                    'no' => $vendorNo,
                    'name' => $displayName,
                    'vendor_posting_group_id' => $vpg->id,
                ]);
            }
        });
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->where('type', 'member');
    }

    public function scopeSbfMembers(Builder $query): Builder
    {
        return $query->where('is_sbf', true);
    }

    public function getHasPaymentDetailsAttribute(): bool
    {
        return filled($this->bank_account_no) || filled($this->mpesa_phone);
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

    public function dependants(): HasMany
    {
        return $this->hasMany(Dependant::class, 'member_id');
    }

    public function nextOfKin(): HasMany
    {
        return $this->hasMany(NextOfKin::class, 'member_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function financeCustomer(): HasOne
    {
        return $this->hasOne(FinanceCustomer::class, 'no', 'customer_no');
    }

    public function financeVendor(): HasOne
    {
        return $this->hasOne(FinanceVendor::class, 'no', 'vendor_no');
    }

    public function shareSubscriptions(): HasMany
    {
        return $this->hasMany(ShareSubscription::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(ShareNominee::class);
    }

    public function scopeChakamMembers(Builder $query): Builder
    {
        return $query->where('is_chakama', true);
    }
}
