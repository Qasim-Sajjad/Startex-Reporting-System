<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    protected $fillable = ['section_id', 'text', 'guidelines', 'comment','tscore', 'required', 'order_by'];

    use HasFactory;
  
    protected $connection = 'client';


    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // public function options()
    // {
    //     return $this->hasMany(QOption::class, 'question_id');
    // }
    public function options()
    {
        return $this->belongsToMany(Option::class, 'q_options')
            ->withPivot('score')
            ->withTimestamps();
    }

    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'entity', 'attachment_entities', 'entity_id', 'attachment_id');
    }

    public function attachmentRules()
    {
        return $this->morphMany(AttachmentRule::class, 'entity');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'question_id');
    }

}
