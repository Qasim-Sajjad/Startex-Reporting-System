<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\ClientDatabaseManager;
use App\Models\ClientDBUser;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\Comment;
use App\Models\UserTask;
use App\Models\Notification;
use App\Models\Client;
use App\Models\Department;


class TaskController extends Controller
{
   

    public function fetchDepartmentUsers(Request $request)
    {
        $user = auth('sanctum')->user();
      

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Switch to client-specific database
        try {
            ClientDatabaseManager::setConnection($user->database_name);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to switch to client database', 'error' => $e->getMessage()], 500);
        }

        // Find the user's department
        $clientDBUser = ClientDBUser::where('email', $user->email)->first();

        if (!$clientDBUser) {
            return response()->json(['message' => 'User not found in client database'], 404);
        }

        // Fetch all users in the same department except the current user
        $departmentUsers = ClientDBUser::where('department_id', $clientDBUser->department_id)
            ->where('id', '!=', $clientDBUser->id) // Exclude the current user
            ->select('id', 'name', 'email', 'role')
            ->get();

        return response()->json(['users' => $departmentUsers], 200);
    }


    public function createTask(Request $request)
    {
        $user = auth('sanctum')->user();
       
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        // Check if the user is a Client Admin or Client User
        $isClientAdmin = $user->role === 'Client Admin';
        $isClientUser = $user->role === 'User';
       
        if (!$isClientAdmin && !$isClientUser) {
            return response()->json(['message' => 'Permission denied. Only Client Admin or Client Users can create tasks.'], 403);
        }
       
        // Switch to client-specific database if necessary
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
            $clientDBUser = ClientDBUser::find($user->id);
    
            if (!$clientDBUser) {
                return response()->json(['message' => 'User not found in client database'], 404);
            }
        }
    
        // Validate input
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'deadline' => 'required|date|after:now',
            'priority' => 'required|in:Low,Medium,High',
            'time_bound' => 'boolean',
            'assigned_users' => 'required|array|min:1',
            'assigned_users.*' => 'integer', 
            'process_id' => 'nullable', 
            'question_id' => 'nullable',
            'section_id' => 'nullable',
        ]);
        
        // Ensure only one entity (process, question, section) is linked
        $entityCount = collect($validated)->only(['process_id', 'question_id', 'section_id'])->filter()->count();
        if ($entityCount !== 1) {
            return response()->json(['message' => 'A task must be linked to exactly one entity: process, question, or section.'], 400);
        }
    
        // Check self-assignment based on user role
        if (!$isClientAdmin) {
            // For regular users, check if they're trying to assign to themselves
            if (in_array($clientDBUser->id, $validated['assigned_users'])) {
                return response()->json(['message' => 'Users cannot assign tasks to themselves.'], 400);
            }
        }
        // No self-assignment check for Client Admin - they can assign to anyone
    
        // Get assigned users' departments
        $assignedUsersDepartments = ClientDBUser::whereIn('id', $validated['assigned_users'])
            ->pluck('department_id')
            ->unique();
    
        try {
            // Create task for each department
            foreach ($assignedUsersDepartments as $departmentId) {
                // Get users for this department
                $departmentUserIds = collect($validated['assigned_users'])->filter(function($userId) use ($departmentId) {
                    return ClientDBUser::where('id', $userId)
                        ->where('department_id', $departmentId)
                        ->exists();
                })->toArray();
    
                if (empty($departmentUserIds)) {
                    continue;
                }
    
                // Create task
                $task = Task::create([
                    'client_id' => $isClientAdmin ? $user->id : null,
                    'client_dbusers_id' => $isClientAdmin ? null : $clientDBUser->id,
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
    
                // Assign task to users in this department
                foreach ($departmentUserIds as $userId) {
                    UserTask::create([
                        'task_id' => $task->id,
                        'client_dbusers_id' => $userId,
                        'assigned_at' =>now()
                    ]);
    
                    // Send notifications
                    Notification::create([
                        'client_dbusers_id' => $userId,
                        'type' => 'task_assigned',
                        'message' => 'You have been assigned a new task: ' . $task->description,
                    ]);
                }
            }
    
            return response()->json(['message' => 'Tasks created and assigned successfully'], 201);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create task', 'error' => $e->getMessage()], 500);
        }
    }

    public function fetchAssignedTasks(Request $request)
    {
        $user = auth('sanctum')->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            // Dynamically switch to the client-specific database
            ClientDatabaseManager::setConnection($user->database_name);

            // Fetch tasks assigned to the user
            $tasks = DB::connection('client')
                ->table('tasks')
                ->join('user_tasks', 'tasks.id', '=', 'user_tasks.task_id')
                ->where('user_tasks.client_dbusers_id', $user->id)
                ->select(
                    'tasks.id',
                    'tasks.description as name',
                    'tasks.deadline',
                    'tasks.status',
                    'tasks.client_dbusers_id as assigned_by',
                    'tasks.priority',
                    'tasks.time_bound',
                    'tasks.department_id',
                    'tasks.process_id',
                    'tasks.question_id',
                    'tasks.section_id'
                )
                ->orderBy('tasks.deadline', 'asc') // Optional: Order tasks by deadline
                ->get();

            // Map the tasks to include the type of task (process, question, or section)
            $tasks = $tasks->map(function ($task) {
                if ($task->process_id) {
                    $task->type = 'Process';
                    $task->type_id = $task->process_id;
                } elseif ($task->question_id) {
                    $task->type = 'Question';
                    $task->type_id = $task->question_id;
                } elseif ($task->section_id) {
                    $task->type = 'Section';
                    $task->type_id = $task->section_id;
                } else {
                    $task->type = 'Unknown';
                    $task->type_id = null;
                }

                // Remove redundant IDs from the response for clarity
                unset($task->process_id, $task->question_id, $task->section_id);

                return $task;
            });

            // Return the tasks
            return response()->json(['tasks' => $tasks], 200);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching assigned tasks', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Failed to fetch assigned tasks',
                'error' => $e->getMessage()
            ], 500);
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
            if ($task->client_dbusers_id !== $user->id) {
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

    public function addComment(Request $request, $taskId)
    {
        $user = auth('sanctum')->user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        try {
            // Validate the request input
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
            ]);
    
            // Check if the user is a Client Admin
            $isClientAdmin = $user->role === 'Client Admin';
    
            if ($isClientAdmin) {
                ClientDatabaseManager::setConnection($user->database_name);
                // Client Admin can comment on any task
                $comment = Comment::create([
                    'task_id' => $taskId,
                    'client_id' => $user->id,
                    'client_dbusers_id' => null, // Client Admin does not have a client_dbusers_id
                    'content' => $validated['content'],
                ]);
    
                return response()->json([
                    'message' => 'Comment added successfully by Client Admin',
                    'comment' => $comment,
                ], 201);
            }
    
            // Switch to client-specific database for client_dbusers
            ClientDatabaseManager::setConnection($user->database_name);
    
            // Check if the task exists
            $task = Task::find($taskId);
            if (!$task) {
                return response()->json(['message' => 'Task not found'], 404);
            }
    
            // Find the client user in the client-specific database
            
            $clientDBUser = ClientDBUser::find($user->id);
         
            if (!$clientDBUser) {
                return response()->json(['message' => 'User not found in client database'], 404);
            }
          
    
            // Verify if the user is allowed to comment on the task
            $isAssignedToTask = UserTask::where('task_id', $taskId)
                ->where('client_dbusers_id', $clientDBUser->id)
                ->exists();
    
            $isTaskAssigner = $task->client_dbusers_id === $clientDBUser->id;
    
            if (!$isAssignedToTask && !$isTaskAssigner) {
                return response()->json(['message' => 'Permission denied. You are not authorized to comment on this task.'], 403);
            }
    
            // Create the comment
            $comment = Comment::create([
                'task_id' => $taskId,
                'client_id' => $user->client_id,
                'client_dbusers_id' => $clientDBUser->id,
                'content' => $validated['content'],
            ]);
    
            return response()->json([
                'message' => 'Comment added successfully',
                'comment' => $comment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function getComments(Request $request, $taskId)
    {
        $user = auth('sanctum')->user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        try {
            // Check if the user is a Main Client Admin
            $isClientAdmin = $user->role === 'Client Admin';
    
            if ($isClientAdmin) {
                // Main Client can fetch all comments
                ClientDatabaseManager::setConnection($user->database_name);
                $comments = Comment::where('task_id', $taskId)
                    ->with(['user:id,name,email'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    
                return response()->json([
                    'comments' => $comments,
                ], 200);
            } else {
                // For client users, switch to the client-specific database
                ClientDatabaseManager::setConnection($user->database_name);
    
                // Check if the user exists in the client database
                $clientDBUser = ClientDBUser::find($user->id);
                if (!$clientDBUser) {
                    return response()->json(['message' => 'User not found in client database'], 404);
                }
    
                // Check if the task exists
                $task = Task::find($taskId);
                if (!$task) {
                    return response()->json(['message' => 'Task not found'], 404);
                }
    
                // Ensure the user is assigned to the task or assigned it to others
                $isAssignedToTask = UserTask::where('task_id', $taskId)
                    ->where('client_dbusers_id', $clientDBUser->id)
                    ->exists();
    
                $isTaskAssignee = UserTask::where('task_id', $taskId)
                    ->whereIn('client_dbusers_id', [$clientDBUser->id])
                    ->exists();
    
                if (!$isAssignedToTask && !$isTaskAssignee) {
                    return response()->json(['message' => 'You do not have permission to view these comments.'], 403);
                }
    
                // Fetch comments for the task
                $comments = Comment::where('task_id', $taskId)
                    ->with(['user:id,name,email'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    
                return response()->json([
                    'comments' => $comments,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function fetchNotifications()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            ClientDatabaseManager::setConnection($user->database_name);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to switch to client database', 'error' => $e->getMessage()], 500);
        }

        $clientDBUser = ClientDBUser::where('email', $user->email)->first();
        if (!$clientDBUser) {
            return response()->json(['message' => 'User not found in client database'], 404);
        }

        $notifications = Notification::where('client_dbusers_id', $clientDBUser->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['notifications' => $notifications], 200);
    }
   


}
