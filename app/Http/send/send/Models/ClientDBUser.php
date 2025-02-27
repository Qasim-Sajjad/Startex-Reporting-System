<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ClientDBUser extends DBModelConnector
{
    use HasFactory;

    protected $table = 'client_dbusers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
    ];

    public $timestamps = true;

    protected $connection = 'client';

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'user_tasks', 'client_dbusers_id', 'task_id')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }
}
