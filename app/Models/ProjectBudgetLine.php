<?php

namespace App\Models;

use App\Models\Finance\GlAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudgetLine extends Model
{
    protected $table = 'project_budget_lines';

    protected $fillable = [
        'project_id',
        'gl_account_no',
        'description',
        'budgeted_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_amount' => 'decimal:4',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_no', 'no');
    }
}
