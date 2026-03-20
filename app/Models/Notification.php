<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_no', 'title', 'body', 'status', 'scheduled_at', 'sent_at', 'error_log'])]
class Notification extends Model
{
    use HasFactory;

    protected $table = 'bus_notifications';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_no', 'no');
    }
}
