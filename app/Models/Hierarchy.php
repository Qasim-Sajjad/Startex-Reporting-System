<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Hierarchy extends Model
{
    protected $connection = 'client';

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
  public static function getHierarchy($parentId) // Start from ID 19
    {
        $hierarchy = self::with(['level', 'location'])
            ->where('id', $parentId)
            ->first();

        if (!$hierarchy) {
            return [];
        }

        return self::buildHierarchy([$hierarchy]);
    }

    /**
     * Recursively build the hierarchy tree and filter out nodes with children.
     *
     * @param array $nodes
     * @return array
     */
    private static function buildHierarchy($nodes)
    {
        $result = [];
        foreach ($nodes as $node) {
            $children = self::where('parent_id', $node->id)
                ->with(['level', 'location'])
                ->get();

            if ($children->isEmpty()) { // Only include leaf nodes
                $result[] = [
                    'hierarchy_id' => $node->id,
                    'level_id' => $node->hierarchylevels_id,
                    'level_name' => $node->level->name ?? null,
                    'level' => $node->level->level ?? null,
                    'hid' => $node->level->hierarchynames_id ?? null,
                    'location_name' => $node->location->name ?? null,
                    'root_id' => $node->id
                ];
            } else {
                $result = array_merge($result, self::buildHierarchy($children));
            }
        }
        return $result;
    }

}
