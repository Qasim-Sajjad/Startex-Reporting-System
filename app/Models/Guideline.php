<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guideline extends Model
{
    protected $fillable = [
        'title', 'description', 'guideline_type', 'entity_id', 'entity_type', 
        'filepath', 'uploaded_by', 'drive_link'
    ];

    protected $connection = 'client';

    public function entity()
    {
        return $this->morphTo();
    }
}
