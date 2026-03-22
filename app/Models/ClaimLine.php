<?php

namespace App\Models;

use App\Models\Finance\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ClaimLine extends Model
{
    protected $fillable = [
        'claim_id',
        'line_no',
        'description',
        'quantity',
        'unit_amount',
        'line_amount',
        'service_id',
        'attachment_paths',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_amount' => 'decimal:4',
            'line_amount' => 'decimal:4',
            'attachment_paths' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (ClaimLine $line): void {
            if (! $line->isDirty('attachment_paths')) {
                return;
            }

            $existingPaths = $line->lineAttachments()->pluck('file_path')->all();
            $newPaths = $line->attachment_paths ?? [];

            // Create records for newly added paths
            foreach (array_diff($newPaths, $existingPaths) as $path) {
                $line->lineAttachments()->create([
                    'uploaded_by' => auth()->id() ?? 1,
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'mime_type' => Storage::mimeType($path) ?: null,
                    'file_size' => Storage::size($path) ?: null,
                ]);
            }

            // Delete records for removed paths
            $removed = array_diff($existingPaths, $newPaths);
            if ($removed) {
                $line->lineAttachments()->whereIn('file_path', $removed)->delete();
            }
        });
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function lineAttachments(): HasMany
    {
        return $this->hasMany(ClaimLineAttachment::class);
    }
}
