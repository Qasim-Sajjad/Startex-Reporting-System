<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'client_id',
        'client_dbusers_id',
        'description',
        'status',
        'deadline',
        'priority',
        'time_bound',
        'department_id',
        'closed_on',
        'section_id',
        'process_id',
        'question_id',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ClientDBUser::class, 'client_dbusers_id');
    }

    public function userTasks(): HasMany
    {
        return $this->hasMany(UserTask::class, 'task_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'task_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function users()
    {
        return $this->belongsToMany(ClientDbUser::class, 'user_tasks', 'task_id', 'client_dbusers_id')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    protected $connection = 'client';
}
