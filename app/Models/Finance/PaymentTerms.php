<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'description', 'due_days'])]
class PaymentTerms extends Model
{
    use HasFactory;

    protected $table = 'payment_terms';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'due_days' => 'integer',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'payment_terms_code', 'code');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'payment_terms_code', 'code');
    }
}
