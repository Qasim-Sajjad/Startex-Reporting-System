<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HierarchyName extends Model
{
      protected $connection = 'client';

    use HasFactory;

    protected $table = 'hierarchynames';

    protected $fillable = ['name'];

    public function levels()
    {
        return $this->hasMany(HierarchyLevel::class, 'hierarchynames_id');
    }

    public $timestamps = true;

}
