<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Format extends DBModelConnector
{
    protected $fillable = ['name'];

    public function sections()
    {
        return $this->hasMany(Section::class, 'format_id');
    }
}
