<?php
namespace App\Http\Controllers;

use App\Models\ProcessUser;
use Illuminate\Http\Request;
use App\Services\ClientDatabaseManager;
use App\Models\HierarchyName;

class HeirarachyController extends Controller
{
  
    public function getHierarchies()

    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        ClientDatabaseManager::setConnection($user->database_name);

        $hierarchies = HierarchyName::with(['levels.hierarchies.location'])
            ->get();

        return response()->json([
            'message' => 'Hierarchies retrieved successfully.',
            'data' => $hierarchies,
        ], 200);
    }

    public function getHierarchyById($id)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        ClientDatabaseManager::setConnection($user->database_name);

        $hierarchy = HierarchyName::with(['levels.hierarchies.location'])
            ->find($id);

        if (!$hierarchy) {
            return response()->json(['message' => 'Hierarchy not found.'], 404);
        }

        return response()->json([
            'message' => 'Hierarchy retrieved successfully.',
            'data' => $hierarchy,
        ], 200);
    }


}
