<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sectionCalculations extends Model
{
    protected $fillable = [
        'format_id', 'shop_id', 'wave_id',  'section_id', 'sectionName', 'achived', 'applicable'
    ];
    use HasFactory;
}
