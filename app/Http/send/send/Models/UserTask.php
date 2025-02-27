<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    protected $fillable = ['task_id', 'client_dbusers_id', 'assigned_at', 'last_reminder_sent'];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(ClientDBUser::class, 'client_dbusers_id');
    }
    protected $connection = 'client';
}
