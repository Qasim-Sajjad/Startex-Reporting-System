<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['section_id', 'text', 'comment', 'required'];

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function options()
    {
        return $this->hasMany(QOption::class, 'question_id');
    }
}
