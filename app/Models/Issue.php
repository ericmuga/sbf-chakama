<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\IssuePortal;
use App\Enums\IssueStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title', 'portal_type', 'details', 'issue_owner', 'category', 'resource',
    'date_assigned', 'date_actioned', 'status', 'closure_date', 'comments',
    'reviewed_date', 'qa_test_result', 'release_id',
])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    protected $table = 'app_issues';

    protected function casts(): array
    {
        return [
            'portal_type' => IssuePortal::class,
            'category' => IssueCategory::class,
            'status' => IssueStatus::class,
            'date_assigned' => 'date',
            'date_actioned' => 'date',
            'closure_date' => 'date',
            'reviewed_date' => 'date',
        ];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
