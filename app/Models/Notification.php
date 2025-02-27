<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_dbusers_id',
        'type',
        'message',
        'is_read',
    ];
    public $timestamps = true;

    protected $connection = 'client';
}
