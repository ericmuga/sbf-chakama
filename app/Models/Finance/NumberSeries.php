<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'description', 'prefix', 'last_no', 'last_date_used', 'length', 'is_manual_allowed', 'prevent_repeats', 'is_active'])]
class NumberSeries extends Model
{
    use HasFactory;

    protected $table = 'number_series';

    protected function casts(): array
    {
        return [
            'last_no' => 'integer',
            'length' => 'integer',
            'is_manual_allowed' => 'boolean',
            'prevent_repeats' => 'boolean',
            'is_active' => 'boolean',
            'last_date_used' => 'date',
        ];
    }

    public function salesHeaders(): HasMany
    {
        return $this->hasMany(SalesHeader::class, 'number_series_code', 'code');
    }

    public function purchaseHeaders(): HasMany
    {
        return $this->hasMany(PurchaseHeader::class, 'number_series_code', 'code');
    }

    /**
     * Generate the next number in this series (thread-safe via lock).
     */
    public static function generate(string $code): string
    {
        $series = static::where('code', $code)->lockForUpdate()->first();

        if (! $series || ! $series->is_active) {
            return '';
        }

        $series->increment('last_no');
        $series->update(['last_date_used' => now()->toDateString()]);

        return ($series->prefix ?? '').str_pad((string) $series->last_no, $series->length, '0', STR_PAD_LEFT);
    }
}
