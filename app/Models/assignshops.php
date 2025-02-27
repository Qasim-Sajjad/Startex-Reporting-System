<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class assignshops extends Model
{
    protected $fillable = [
        'shopper_id',
        'location_id',
        'format_id',
        'wave_id',
        'client_id',
        'time_in',
        'time_out',
        'date',
        'timeIn',
        'timeOut',
        'visit_type',
        'status',
        'year',
        'project_manager_name',
        'surveydate',
        'flagforsaved',
    ];
    use HasFactory;
}
