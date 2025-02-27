<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['format_id', 'name'];

    public function format()
    {
        return $this->belongsTo(Format::class, 'format_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'section_id');
    }
}
