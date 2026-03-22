<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimLineAttachment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'claim_line_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    public function claimLine(): BelongsTo
    {
        return $this->belongsTo(ClaimLine::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
