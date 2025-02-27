<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QOption extends Model
{
    protected $table = 'q_options';
    protected $fillable = ['question_id', 'option_id', 'score'];
    protected $casts = [
        'score' => 'integer',
    ];

    protected $connection = 'client';

    public $timestamps = true;

    // Relationships
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }

    // Mutator for ensuring non-negative scores
    public function setScoreAttribute($value)
    {
        $this->attributes['score'] = max(0, $value);
    }
}
