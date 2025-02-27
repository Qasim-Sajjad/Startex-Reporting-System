<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepartmentUser extends DBModelConnector
{
    protected $fillable = ['name', 'username', 'password', 'department_id'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
