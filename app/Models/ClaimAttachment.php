<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimAttachment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'claim_id',
        'uploaded_by',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
