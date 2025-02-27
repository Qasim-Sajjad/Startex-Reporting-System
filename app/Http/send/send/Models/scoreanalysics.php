<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class scoreanalysics extends Model
{
    protected $table = 'scoreanalysics'; // Specify the table name

    use HasFactory;
    protected $fillable = [
        'shop_id',
        'format_id',
        'section_id',
        'question_id',
        'response',
        'achieved',
        'applicable',
        'total',
        'wave_id',
        'section_name',
        'question_name',
    ];
}
