<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HierarchyLevel extends Model
{
    use HasFactory;

    protected $table = 'hierarchylevels';


    protected $fillable = ['name', 'level', 'hierarchynames_id'];

    public function hierarchies()
    {
        return $this->hasMany(Hierarchy::class, 'hierarchylevels_id');
    }

    public function hierarchyName()
    {
        return $this->belongsTo(HierarchyName::class, 'hierarchynames_id');
    }

    public $timestamps = true;

    protected $connection = 'client';
}
