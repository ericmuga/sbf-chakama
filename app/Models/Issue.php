<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\IssuePortal;
use App\Enums\IssueStatus;
use App\Enums\UserRole;
use App\Notifications\IssueClosedNotification;
use App\Notifications\IssueLoggedNotification;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

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

    protected static function booted(): void
    {
        static::created(function (Issue $issue): void {
            $issue->notifyUsersWithRole(UserRole::Developer, new IssueLoggedNotification($issue));
        });

        static::updated(function (Issue $issue): void {
            if ($issue->wasChanged('status') && $issue->status === IssueStatus::Closed) {
                $issue->notifyUsersWithRole(UserRole::BusinessAnalyst, new IssueClosedNotification($issue));
            }
        });
    }

    /**
     * Notify every user holding the given role, without failing the save if mail breaks.
     */
    protected function notifyUsersWithRole(UserRole $role, mixed $notification): void
    {
        $recipients = User::where('role', $role)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, $notification);
        } catch (\Throwable) {
            // Do not block issue changes if notification delivery fails.
        }
    }

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
