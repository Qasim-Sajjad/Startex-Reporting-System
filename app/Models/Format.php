<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Format extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'process_id']; 
    
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class, 'format_id');
    }

    public function attachmentRules()
    {
        return $this->morphMany(AttachmentRule::class, 'entity');
    }
    public function tasks()
    {
        return $this->hasMany(Task::class, 'format_id');
    }

    protected $connection = 'client';
}
