<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QOption extends Model
{
    protected $fillable = ['question_id', 'option_id', 'score'];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }
}
