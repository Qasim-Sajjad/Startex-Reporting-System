<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $connection = 'client';

    protected $fillable = [
        'name',
        'frequency_id',
        'hierarchynames_id',
        'specific_days',
        'exclude_sundays',
        'exclude_public_holidays',
        'process_deadline',
        'submission_deadline',
        'submission_start_time',
        'grace_period_minutes',
        'start_date',
    ];

    protected $casts = [
        'specific_days' => 'array',
        'process_deadline' => 'date',
        'start_date' => 'date',
        'submission_deadline' => 'datetime',
        'submission_start_time' => 'datetime',
    ];

    // Relationships
    public function frequency()
    {
        return $this->belongsTo(Frequency::class);
    }

    public function format()
    {
        return $this->hasOne(Format::class);
    }

    public function hierarchy()
    {
        return $this->belongsTo(HierarchyName::class);
    }

    public function sections()
    {
        return $this->hasManyThrough(Section::class, Format::class, 'id', 'format_id');
    }
    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'entity', 'attachment_entities', 'entity_id', 'attachment_id');
    }


}
