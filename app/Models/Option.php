<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Option extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'q_options')
            ->withPivot(['score'])
            ->withTimestamps();
    }

    protected $connection = 'client';
}
