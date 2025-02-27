<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Client extends Model
{
    use HasApiTokens, HasFactory, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'industry',
        'address',
        'status',
        'database_name',
        'user_id',
    ];

    protected $hidden = [
        'password', // Hide password when returning client data
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
