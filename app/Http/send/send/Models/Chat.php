<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends DBModelConnector
{
    protected $fillable = ['task_id', 'sender_id', 'message'];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
