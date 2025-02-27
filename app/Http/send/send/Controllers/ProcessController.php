<?php
namespace App\Http\Controllers;

use App\Models\ProcessUser;
use App\Models\Process;
use Illuminate\Http\Request;
use App\Services\ClientDatabaseManager;

class ProcessController extends Controller
{
    public function assignProcess(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'process_id' => 'required',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ]);

        try {
            $connection = ClientDatabaseManager::setConnection($user->database_name);
          
            foreach ($validated['user_ids'] as $userId) {
             
                $exists = ProcessUser::where([
                    ['process_id', '=', $validated['process_id']],
                    ['client_dbuser_id', '=', $userId],
                ])->exists();

                if (!$exists) {
                  
                    ProcessUser::create([
                        'process_id' => $validated['process_id'],
                        'client_dbuser_id' => $userId,
                        'assigned_at' => now(),
                    ]);
                }
            }

            return response()->json(['message' => 'Process assigned successfully'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign process',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
 * Fetch all processes for the authenticated client.
 */
public function getAllProcesses(Request $request)
{
    $user = auth('sanctum')->user();

    // Ensure the client is authenticated
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Set the connection to the client's database
    ClientDatabaseManager::setConnection($user->database_name);

    // Fetch all processes
    $processes = Process::all();

    // Check if any processes exist
    if ($processes->isEmpty()) {
        return response()->json(['message' => 'No processes found for this client'], 404);
    }

    // Return the processes
    return response()->json([
        'message' => 'Processes retrieved successfully',
        'processes' => $processes,
    ], 200);
}

}
