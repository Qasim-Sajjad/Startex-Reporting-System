<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Comment extends DBModelConnector
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
        'task_id',
        'client_dbusers_id',
        'content',
        'client_id'
    ];
    public function user()
    {
        return $this->belongsTo(ClientDBUser::class, 'client_dbusers_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public $timestamps = true;

    protected $connection = 'client';
}
