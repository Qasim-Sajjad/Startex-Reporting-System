<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends DBModelConnector
{
    use HasFactory;

    protected $fillable = ['name', 'client_dbusers_id'];

    public function user()
    {
        return $this->belongsTo(ClientDBUser::class);
    }
    public function departmentUsers()
    {
        return $this->hasMany(DepartmentUser::class);
    }
    

    protected $connection = 'client';
}

