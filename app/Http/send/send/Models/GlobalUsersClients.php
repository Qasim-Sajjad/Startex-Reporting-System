<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class GlobalUsersClients extends Model
{
    use HasFactory, HasApiTokens, HasRoles;
   
    protected $table = 'globalusersclients';

    protected $fillable = [
        'client_id',
        'email',
        'password',
        'database_name',
        'role'
    ];
}
