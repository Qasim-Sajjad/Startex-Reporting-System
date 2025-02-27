<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $connection = 'client';

    protected $fillable = [
        'filename',
        'filepath',
        'drive_link',
        'filetype',
        'size',
        'uploaded_by',
    ];

  
    // public function entities()
    // {
    //     return $this->morphTo();
    // }
    public function attachmentEntities()
    {
        return $this->hasMany(AttachmentEntity::class);
    }
    public function uploader()
    {
        return $this->belongsTo(ClientDBUser::class, 'uploaded_by');
    }



}
