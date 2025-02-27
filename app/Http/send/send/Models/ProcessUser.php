<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessUser extends Model
{
    use HasFactory;

    protected $table = 'process_user'; // Table name

    protected $fillable = [
        'process_id',
        'client_dbuser_id',
        'assigned_at',
    ];

    public $timestamps = true; // Enable timestamps

     protected $connection = 'client';
}
