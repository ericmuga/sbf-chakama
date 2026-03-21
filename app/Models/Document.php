<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['documentable_type', 'documentable_id', 'document_type', 'file_path', 'disk', 'original_name', 'mime_type', 'file_size'])]
class Document extends Model
{
    protected $table = 'bus_documents';

    protected function casts(): array
    {
        return [
            'document_type' => 'string',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
