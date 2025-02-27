<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class regionCalculations extends Model
{
    protected $fillable = [
        'format_id',  'wave_id',  'region_id', 'regionName', 'achived', 'applicable'
    ];
    use HasFactory;
}
