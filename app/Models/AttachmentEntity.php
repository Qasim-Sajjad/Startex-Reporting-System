<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentEntity extends Model
{
    use HasFactory;

    protected $connection = 'client';
    
    protected $fillable = [
        'attachment_id',
        'entity_id',
        'entity_type',
    ];

    // Define relationships
    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }
}
