<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'description', 'prefix', 'last_no_used', 'increment_by'])]
class NoSeries extends Model
{
    use HasFactory;

    protected $table = 'bus_no_series';

    public $timestamps = false;
}
