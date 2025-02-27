<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    
    protected $fillable = ['format_id', 'name', 'order_by'];


    public function format()
    {
        return $this->belongsTo(Format::class, 'format_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'section_id');
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
        return $this->hasMany(Task::class, 'section_id');
    }

    protected $connection = 'client';
}
