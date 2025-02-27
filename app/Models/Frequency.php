<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frequency extends Model
{
    use HasFactory;

    protected $connection = 'client'; // Ensure it uses the client's database connection

    protected $fillable = ['name']; // Define the fillable fields

    // Relationships
    public function processes()
    {
        return $this->hasMany(Process::class);
    }
}
