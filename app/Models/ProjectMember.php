<?php

namespace App\Models;

use App\Enums\ProjectMemberRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectMember extends Pivot
{
    protected $table = 'project_members';

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'assigned_at',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProjectMemberRole::class,
            'assigned_at' => 'datetime',
        ];
    }
}
