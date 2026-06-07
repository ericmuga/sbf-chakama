<?php

namespace App\Models;

use App\Enums\MemberGroupMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MemberGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'mode',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'mode' => MemberGroupMode::class,
            'is_active' => 'boolean',
        ];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_group_member')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function billingRuns(): HasMany
    {
        return $this->hasMany(ShareBillingRun::class, 'member_group_id');
    }

    /**
     * Resolve the effective member IDs for this group based on its mode.
     * - include: members directly attached to the group.
     * - all_except: all active Chakama members minus the attached ones.
     *
     * @return Collection<int, int>
     */
    public function resolveMemberIds(): Collection
    {
        $attached = $this->members()->pluck((new Member)->getTable().'.id');

        return match ($this->mode) {
            MemberGroupMode::Include => $attached,
            MemberGroupMode::AllExcept => Member::query()
                ->where('is_chakama', true)
                ->where('member_status', 'active')
                ->whereNotIn('id', $attached)
                ->pluck('id'),
        };
    }
}
