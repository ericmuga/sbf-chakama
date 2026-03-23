<?php

namespace App\Models;

use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Finance\GlEntry;
use App\Models\Finance\PurchaseHeader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'no',
        'name',
        'slug',
        'description',
        'module',
        'budget',
        'spent',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'number_series_code',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'module' => ProjectModule::class,
            'budget' => 'decimal:4',
            'spent' => 'decimal:4',
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['role', 'assigned_at', 'assigned_by'])
            ->withTimestamps()
            ->using(ProjectMember::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseHeader::class, 'project_id');
    }

    public function directCosts(): HasMany
    {
        return $this->hasMany(ProjectDirectCost::class, 'project_id');
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(ProjectBudgetLine::class, 'project_id')->orderBy('sort_order');
    }

    public function glEntries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')->orderBy('sort_order');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ProjectStatusHistory::class, 'project_id')->orderByDesc('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class, 'project_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class, 'project_id')->orderByDesc('created_at');
    }

    public function utilisationPercent(): float
    {
        if ($this->budget <= 0) {
            return 0.0;
        }

        return round((float) $this->spent / (float) $this->budget * 100, 1);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value]);
    }

    public function scopeForModule(Builder $query, ProjectModule $module): Builder
    {
        return $query->where('module', $module->value);
    }
}
