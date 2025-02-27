<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HierarchyName extends Model
{
    use HasFactory;

    protected $table = 'hierarchynames';

    protected $fillable = ['name'];

    public function levels()
    {
        return $this->hasMany(HierarchyLevel::class, 'hierarchynames_id');
    }

    public $timestamps = true;

    protected $connection = 'client';
}
