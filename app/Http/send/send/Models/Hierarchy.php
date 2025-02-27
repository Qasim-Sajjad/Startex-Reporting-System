<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Hierarchy extends Model
{

    protected $table = 'hierarchies';

    protected $fillable = ['location_id', 'hierarchylevels_id', 'parent_id', 'client_dbusers_id', 'branch_code', 'address'];

    public function level()
    {
        return $this->belongsTo(HierarchyLevel::class, 'hierarchylevels_id');
    }
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function children()
    {
        return $this->hasMany(Hierarchy::class, 'parent_id');
    }

    public function clientUser()
    {
        return $this->belongsTo(ClientDBUser::class, 'client_dbusers_id');
    }

    public $timestamps = true;

    protected $connection = 'client';

}
