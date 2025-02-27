<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class branchCalculations extends Model
{
    protected $fillable = [
        'format_id', 'shop_id', 'wave_id', 'branchName', 'overAllScore', 'region_id'
    ];
    use HasFactory;
}
