<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\ClientDatabaseManager;
use App\Models\GlobalUsersClients;
use Illuminate\Support\Facades\DB; 
use App\Models\ClientDBUser;
use App\Models\Department;
use App\Models\Hierarchy;
use App\Models\Task;
use App\Models\UserTask;
use App\Models\Notification;

class ClientController extends Controller
{
    /**
     * Store a new client.
     */
    public function store(Request $request)
    {
       
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients,email',
            'industry' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'address' => 'required|string|max:500',
        ]);
       

        $client = Client::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'industry' => $validated['industry'],
            'password' => bcrypt($validated['password']),
            'address' => $validated['address'],
            'status' => 'Active',
            'user_id' => auth()->id(), // Assuming a Super Admin creates the client
        ]);

        $databaseName = 'client_' . $client->id;
        $client->update(['database_name' => $databaseName]);

        ClientDatabaseManager::createDatabase($databaseName);
        ClientDatabaseManager::setConnection($databaseName);
        ClientDatabaseManager::runMigrations();
        ClientDatabaseManager::seedClientDatabase($databaseName);

        return response()->json([
            'message' => 'Client created successfully',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'database_name' => $client->database_name,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);
    
        
        $client = Client::where('email', $credentials['email'])->first();
    
        // Check if the client exists and verify the password
        if (!$client || !Hash::check($credentials['password'], $client->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        // Generate a Sanctum token manually
        $token = $client->createToken('client_auth_token')->plainTextToken;

        session(['client_database' => $client->database_name]);
    
        // Return response with client data and token
        return response()->json([
            'message' => 'Login successful',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'database_name' => $client->database_name,
            ],
            'token' => $token,
        ], 200);
    }

    /**
     * Client logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful'], 200);
    }

    /**
     * Create a department user for the client.
     */
    public function createDepartmentUser(Request $request)
    {
        $client = $request->user();
    
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'required',
            'hierarchy_id' => 'required',
        ]);
    
        session(['client_database' => $client->database_name]);
        ClientDatabaseManager::setConnection($client->database_name);
    
      
        $user = ClientDBUser::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'department_id' => $validated['department_id'],
        ]);
    
       
        $hierarchy = Hierarchy::find($validated['hierarchy_id']);
        $hierarchy->update(['client_dbusers_id' => $user->id]);
    
        GlobalUsersClients::create([
            'client_id' => $client->id,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'database_name' => $client->database_name,
        ]);
    
        return response()->json([
            'message' => 'Department user created successfully.',
            'user' => $user,
        ], 201);
    }
    

    public function getClientDepartmentUsers(Request $request)
    {
        // Authenticate the client (Client Admin)
        $user = auth('sanctum')->user();

        ClientDatabaseManager::setConnection($user->database_name);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ensure that the user is a Client Admin
        if ($user->role !== 'Client Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Retrieve all departments for the client
        $departments = Department::all();

        // If no departments foundp
        if ($departments->isEmpty()) {
            return response()->json(['message' => 'No departments found for this client'], 404);
        }

        // Fetch users for each department
        $departmentUsers = [];
        foreach ($departments as $department) {
            $users = ClientDBUser::where('department_id', $department->id)->get();
            $departmentUsers[] = [
                'department' => $department->name, // Assuming the department has a 'name' attribute
                'users' => $users, // All users belonging to this department
            ];
        }

        // Return the department users data
        return response()->json([
            'message' => 'Department users retrieved successfully',
            'data' => $departmentUsers,
        ], 200);
    }

    public function getDepartments(Request $request)
    {
       
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        ClientDatabaseManager::setConnection($user->database_name);

        $departments = Department::all();

        if ($departments->isEmpty()) {
            return response()->json(['message' => 'No departments found for this client'], 404);
        }

        return response()->json([
            'message' => 'Departments retrieved successfully',
            'departments' => $departments,
        ], 200);
    }

    public function createTask(Request $request)
    {
     
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check user roles
        $isClientAdmin = $user->role === 'Client Admin';
        $isClientUser = $user->role === 'User';

        if (!$isClientAdmin && !$isClientUser) {
            return response()->json(['message' => 'Permission denied.'], 403);
        }

        // Switch to client-specific database
        if ($isClientAdmin) {
            $client = Client::find($user->id);

            if (!$client) {
                return response()->json(['message' => 'Client not found.'], 404);
            }

            try {
                ClientDatabaseManager::setConnection($client->database_name);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to switch to client database', 'error' => $e->getMessage()], 500);
            }
        } else {
            ClientDatabaseManager::setConnection($user->database_name);
        }

        // Validate input
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'deadline' => 'required|date|after:now',
            'priority' => 'required|in:Low,Medium,High',
            'time_bound' => 'boolean',
            'assigned_users' => 'required|array|min:1',
            'assigned_users.*.user_id' => 'required|integer',
            'assigned_users.*.department_id' => 'required|integer',
            'process_id' => 'nullable',
            'question_id' => 'nullable',
            'section_id' => 'nullable',
        ]);

        // Ensure only one entity (process, question, section) is linked
        $entityCount = collect($validated)->only(['process_id', 'question_id', 'section_id'])->filter()->count();
        if ($entityCount !== 1) {
            return response()->json(['message' => 'A task must be linked to exactly one entity: process, question, or section.'], 400);
        }

        try {
            // Create task and assign to specified users
            foreach ($validated['assigned_users'] as $assignment) {
                $userId = $assignment['user_id'];
                $departmentId = $assignment['department_id'];

                // Ensure user belongs to the specified department
                $userExistsInDepartment = ClientDBUser::where([
                    ['id', '=', $userId],
                    ['department_id', '=', $departmentId]
                ])->exists();

                if (!$userExistsInDepartment) {
                    return response()->json([
                        'message' => "User $userId does not belong to department $departmentId."
                    ], 400);
                }

                // Create task
                $task = Task::create([
                    'client_id' => $isClientAdmin ? $user->id : null,
                    'client_dbusers_id' => $isClientAdmin ? null : $user->id,
                    'description' => $validated['description'],
                    'deadline' => $validated['deadline'],
                    'priority' => $validated['priority'],
                    'department_id' => $departmentId,
                    'time_bound' => $validated['time_bound'] ?? false,
                    'process_id' => $validated['process_id'] ?? null,
                    'question_id' => $validated['question_id'] ?? null,
                    'section_id' => $validated['section_id'] ?? null,
                    'status' => 'Open',
                ]);

                // Assign task to user
                UserTask::create([
                    'task_id' => $task->id,
                    'client_dbusers_id' => $userId,
                    'assigned_at' => now(),
                ]);

                // Send notifications
                Notification::create([
                    'client_dbusers_id' => $userId,
                    'type' => 'task_assigned',
                    'message' => 'You have been assigned a new task: ' . $task->description,
                ]);
            }

            return response()->json(['message' => 'Tasks created and assigned successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create task', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $user = auth('sanctum')->user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Closed',
        ]); 

        try {
            // Dynamically switch to the client-specific database
            ClientDatabaseManager::setConnection($user->database_name);

            // Find the task
            $task = DB::connection('client')->table('tasks')->where('id', $id)->first();

            if (!$task) {
                return response()->json(['message' => 'Task not found'], 404);
            }

            // Check if the user is the creator of the task
            if ($task->client_id !== $user->id) {
                return response()->json(['message' => 'Only the task creator can update the status'], 403);
            }

            // Update the task status
            DB::connection('client')->table('tasks')->where('id', $id)->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

            // If status is changed to closed, you might want to record the completion time
            if ($validated['status'] === 'Closed') {
                DB::connection('client')->table('tasks')
                    ->where('id', $id)
                    ->update([
                        'closed_on' => now()
                    ]);
            }

            return response()->json(['message' => 'Task status updated successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update task status', 'error' => $e->getMessage()], 500);
        }
    }


}
