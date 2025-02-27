<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttachmentRule extends Model
{

    protected $fillable = ['entity_type', 'entity_id', 'allowed_types'];

    protected $connection = 'client';


    public function entity()
    {
        return $this->morphTo();
    }
}
