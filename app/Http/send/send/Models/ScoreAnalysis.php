<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreAnalysis extends Model
{
    protected $fillable = ['q_option_id', 'hierarchy_id', 'response', 'comment'];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function qOption()
    {
        return $this->belongsTo(QOption::class, 'q_option_id');
    }

    public function hierarchy()
    {
        return $this->belongsTo(Hierarchy::class, 'hierarchy_id');
    }
}
