<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundWithdrawalAttachment extends Model
{
    protected $table = 'fund_withdrawal_attachments';

    const UPDATED_AT = null;

    protected $fillable = [
        'fund_withdrawal_id',
        'uploaded_by',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function fundWithdrawal(): BelongsTo
    {
        return $this->belongsTo(FundWithdrawal::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function fileSizeHuman(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = (int) $this->file_size;

                if ($bytes < 1024) {
                    return $bytes.' B';
                }

                if ($bytes < 1048576) {
                    return round($bytes / 1024, 1).' KB';
                }

                if ($bytes < 1073741824) {
                    return round($bytes / 1048576, 1).' MB';
                }

                return round($bytes / 1073741824, 1).' GB';
            },
        );
    }
}
