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
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false
            ], 401);
        }
    
        // Switch to client-specific database
        try {
            ClientDatabaseManager::setConnection($user->database_name);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to switch to client database',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    
        // Find the user's department
        $clientDBUser = ClientDBUser::where('email', $user->email)->first();
    
        if (!$clientDBUser) {
            return response()->json([
                'data' => null,
                'status_code' => 404,
                'message' => 'User not found in client database',
                'success' => false
            ], 404);
        }
    
        // Fetch all users in the same department except the current user
        $departmentUsers = ClientDBUser::where('department_id', $clientDBUser->department_id)
            ->where('id', '!=', $clientDBUser->id) // Exclude the current user
            ->select('id', 'name', 'email', 'role')
            ->get();
    
        return response()->json([
            'data' => $departmentUsers,
            'status_code' => 200,
            'message' => 'Users fetched successfully',
            'success' => true
        ], 200);
    }
    
    public function createTask(Request $request)
    {
        $user = auth('sanctum')->user();
     

        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false
            ], 401);
        }

        // Check if the user is a Client Admin or Client User
        $isClientAdmin = $user->role === 'Client Admin';
        $isClientUser = $user->role === 'User' || $user->role === 'EndUser';

        if (!$isClientAdmin && !$isClientUser) {
            return response()->json([
                'data' => null,
                'status_code' => 403,
                'message' => 'Permission denied. Only Client Admin or Client Users can create tasks.',
                'success' => false
            ], 403);
        }

        // Switch to client-specific database
        try {
            if ($isClientAdmin) {
                $client = Client::find($user->id);

                if (!$client) {
                    return response()->json([
                        'data' => null,
                        'status_code' => 404,
                        'message' => 'Client not found.',
                        'success' => false
                    ], 404);
                }

                ClientDatabaseManager::setConnection($client->database_name);
            } else {
                ClientDatabaseManager::setConnection($user->database_name);
                // $clientDBUser = ClientDBUser::find($user->email);
                $clientDBUser = ClientDBUser::where('email', $user->email)
                ->first();
                

                if (!$clientDBUser) {
                    return response()->json([
                        'data' => null,
                        'status_code' => 404,
                        'message' => 'User not found in client database',
                        'success' => false
                    ], 404);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to switch to client database',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }

        // Validate input
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'deadline' => 'nullable|date|after:now',
            'priority' => 'required|in:Low,Medium,High',
            'time_bound' => 'boolean',
            'assigned_users' => 'required|array|min:1',
            'assigned_users.*' => 'integer',
            'process_id' => 'nullable',
            'question_id' => 'nullable',
            'section_id' => 'nullable',
            'format_id' => 'nullable',
        ]);

        $entityCount = collect($validated)->only(['process_id', 'question_id', 'section_id','format_id'])->filter()->count();
        if ($entityCount !== 1) {
            return response()->json([
                'data' => null,
                'status_code' => 400,
                'message' => 'A task must be linked to exactly one entity: process, question, or section.',
                'success' => false
            ], 400);
        }

        if (!$isClientAdmin) {
            if (in_array($clientDBUser->id, $validated['assigned_users'])) {
                return response()->json([
                    'data' => null,
                    'status_code' => 400,
                    'message' => 'Users cannot assign tasks to themselves.',
                    'success' => false
                ], 400);
            }
        }

        $assignedUsersDepartments = ClientDBUser::whereIn('id', $validated['assigned_users'])
            ->pluck('department_id')
            ->unique();

        try {
            foreach ($assignedUsersDepartments as $departmentId) {
                $departmentUserIds = collect($validated['assigned_users'])->filter(function ($userId) use ($departmentId) {
                    return ClientDBUser::where('id', $userId)
                        ->where('department_id', $departmentId)
                        ->exists();
                })->toArray();

                if (empty($departmentUserIds)) {
                    continue;
                }

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
                    'format_id' => $validated['format_id'] ?? null,
                    'status' => 'Open',
                ]);

                foreach ($departmentUserIds as $userId) {
                    UserTask::create([
                        'task_id' => $task->id,
                        'client_dbusers_id' => $userId,
                        'assigned_at' => now()
                    ]);

                    Notification::create([
                        'client_dbusers_id' => $userId,
                        'type' => 'task_assigned',
                        'message' => 'You have been assigned a new task: ' . $task->description,
                    ]);
                }
            }

            return response()->json([
                'data' => null,
                'status_code' => 201,
                'message' => 'Tasks created and assigned successfully',
                'success' => true
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to create task',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchAssignedTasks(Request $request)
    {
        $user = auth('sanctum')->user();
       

        // Check if the user is authenticated
        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }

        try {
            // Dynamically switch to the client-specific database
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)
            ->first();

            // Fetch tasks assigned to the user
            $tasks = DB::connection('client')
                ->table('tasks')
                ->join('user_tasks', 'tasks.id', '=', 'user_tasks.task_id')
                ->where('user_tasks.client_dbusers_id', $clientDbUser->id)
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

            // Return the tasks in the desired format
            return response()->json([
                'data' => $tasks,
                'status_code' => 200,
                'message' => 'Tasks fetched successfully',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching assigned tasks', [
                'error' => $e->getMessage(),
                'user_id' => $clientDbUser->id,
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch assigned tasks',
                'success' => false,
            ], 500);
        }
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }

        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Closed',
        ]);

        try {
            // Dynamically switch to the client-specific database
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)
            ->first();

            // Find the task
            $task = DB::connection('client')->table('tasks')->where('id', $id)->first();

            if (!$task) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Task not found',
                    'success' => false,
                ], 404);
            }

            // Check if the user is the creator of the task
            if ($task->client_dbusers_id !== $clientDbUser->id) {
                return response()->json([
                    'data' => null,
                    'status_code' => 403,
                    'message' => 'Only the task creator can update the status',
                    'success' => false,
                ], 403);
            }

            // Update the task status
            DB::connection('client')->table('tasks')->where('id', $id)->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

            // If status is changed to closed, record the completion time
            if ($validated['status'] === 'Closed') {
                DB::connection('client')->table('tasks')
                    ->where('id', $id)
                    ->update([
                        'closed_on' => now()
                    ]);
            }

            return response()->json([
                'data' => null,
                'status_code' => 200,
                'message' => 'Task status updated successfully',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error updating task status', [
                'error' => $e->getMessage(),
                'task_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to update task status',
                'success' => false,
            ], 500);
        }
    }

    public function addComment(Request $request, $taskId)
    {
        try {
            // 1. Authentication check should be first
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json([
                    'data' => null,
                    'status_code' => 401,
                    'message' => 'Unauthorized',
                    'success' => false,
                ], 401);
            }

            // 2. Validate request early
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            // 3. Set client database connection
            ClientDatabaseManager::setConnection($user->database_name);

            // 4. Find client user
            $clientDbUser = ClientDBUser::where('email', $user->email)->first();
            if (!$clientDbUser && $user->role !== 'Client Admin') {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'User not found in client database',
                    'success' => false,
                ], 404);
            }

            // 5. Check if task exists first
            $task = Task::find($taskId);
            if (!$task) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Task not found',
                    'success' => false,
                ], 404);
            }

            // 6. Handle Client Admin case
            if ($user->role === 'Client Admin') {
                
                $comment = Comment::create([
                    'task_id' => $taskId,
                    'client_id' => $user->id,
                    'client_dbusers_id' => null,
                    'content' => $validated['content'],
                ]);

                return response()->json([
                    'data' => $comment,
                    'status_code' => 201,
                    'message' => 'Comment added successfully by Client Admin',
                    'success' => true,
                ], 201);
            }

            // 7. Check user permissions for regular users
            $isAssignedToTask = UserTask::where('task_id', $taskId)
                ->where('client_dbusers_id', $clientDbUser->id)
                ->exists();

            $isTaskAssigner = $task->client_dbusers_id === $clientDbUser->id;

            if (!$isAssignedToTask && !$isTaskAssigner) {
                return response()->json([
                    'data' => null,
                    'status_code' => 403,
                    'message' => 'Permission denied. You are not authorized to comment on this task.',
                    'success' => false,
                ], 403);
            }

            // 8. Create comment for regular user
            $comment = Comment::create([
                'task_id' => $taskId,
                'client_id' => $user->client_id,
                'client_dbusers_id' => $clientDbUser->id,
                'content' => $validated['content'],
            ]);

            return response()->json([
                'data' => $comment,
                'status_code' => 201,
                'message' => 'Comment added successfully',
                'success' => true,
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error adding comment', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'user' => $user ?? null,
                'stack_trace' => $e->getTraceAsString() // Added for better debugging
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to add comment',
                'success' => false,
            ], 500);
        }
    }
 
    public function getComments(Request $request, $taskId)
    {
        $user = auth('sanctum')->user();
        ClientDatabaseManager::setConnection($user->database_name);
        $clientDbUser = ClientDBUser::where('email', $user->email)
        ->first();
       
        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }

        try {
            $isClientAdmin = $user->role === 'Client Admin';

            if ($isClientAdmin) {
               
                $comments = Comment::where('task_id', $taskId)
                    ->with(['user:id,name,email'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                return response()->json([
                    'data' => $comments,
                    'status_code' => 200,
                    'message' => 'Comments fetched successfully',
                    'success' => true,
                ], 200);
            } else {
                

                $clientDBUser = ClientDBUser::find($clientDbUser->id);
                if (!$clientDBUser) {
                    return response()->json([
                        'data' => null,
                        'status_code' => 404,
                        'message' => 'User not found in client database',
                        'success' => false,
                    ], 404);        
                }

                $task = Task::find($taskId);
                if (!$task) {
                    return response()->json([
                        'data' => null,
                        'status_code' => 404,
                        'message' => 'Task not found',
                        'success' => false,
                    ], 404);
                }

                $isAssignedToTask = UserTask::where('task_id', $taskId)
                    ->where('client_dbusers_id', $clientDbUser->id)
                    ->exists();

                $isTaskAssignee = Task::where('id', $taskId)
                    ->where('client_dbusers_id', $clientDbUser->id)
                    ->exists();

                if (!$isAssignedToTask && !$isTaskAssignee) {
                    return response()->json([
                        'data' => null,
                        'status_code' => 403,
                        'message' => 'You do not have permission to view these comments.',
                        'success' => false,
                    ], 403);
                }

                $comments = Comment::where('task_id', $taskId)
                    ->with(['user:id,name,email'])
                    ->orderBy('created_at', 'desc')
                    ->get();
             

                return response()->json([
                    'data' => $comments,
                    'status_code' => 200,
                    'message' => 'Comments fetched successfully',
                    'success' => true,
                ], 200);
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching comments', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'user_id' => $clientDbUser->id,
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch comments',
                'success' => false,
            ], 500);
        }
    }
     
    // public function fetchNotifications()
    // {
    //     $user = auth('sanctum')->user();
      

    //     if (!$user) {
    //         return response()->json([
    //             'data' => null,
    //             'status_code' => 401,
    //             'message' => 'Unauthorized',
    //             'success' => false,
    //         ], 401);
    //     }

    //     try {
    //         ClientDatabaseManager::setConnection($user->database_name);
    //     } catch (\Exception $e) {
    //         \Log::error('Error switching to client database', [
    //             'error' => $e->getMessage(),
    //             'user_id' => $user->id,
    //         ]);

    //         return response()->json([
    //             'data' => null,
    //             'status_code' => 500,
    //             'message' => 'Failed to switch to client database',
    //             'success' => false,
    //         ], 500);
    //     }

    //     $clientDBUser = ClientDBUser::where('email', $user->email)->first();
    //     if (!$clientDBUser) {
    //         return response()->json([
    //             'data' => null,
    //             'status_code' => 404,
    //             'message' => 'User not found in client database',
    //             'success' => false,
    //         ], 404);
    //     }

    //     $notifications = Notification::where('client_dbusers_id', $clientDBUser->id)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return response()->json([
    //         'data' => $notifications,
    //         'status_code' => 200,
    //         'message' => 'Notifications fetched successfully',
    //         'success' => true,
    //     ], 200);
    // }

    public function getFilteredTasks(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json([
                    'data' => null,
                    'status_code' => 401,
                    'message' => 'Unauthorized',
                    'success' => false,
                ], 401);
            }

            // Validate filters
            $validated = $request->validate([
                'process_id' => 'nullable|integer',
                'format_id' => 'nullable|integer',
                'section_id' => 'nullable|integer',
                'question_id' => 'nullable|integer',
                'status' => 'nullable|in:Open,In Progress,Closed',
                'priority' => 'nullable|in:Low,Medium,High',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            \Log::info('Filters applied:', $validated);

            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)
            ->first();

            // Start building the query
            $query = Task::with(['process', 'format', 'section', 'question', 'creator'])
                ->join('user_tasks', 'tasks.id', '=', 'user_tasks.task_id')
                ->where('user_tasks.client_dbusers_id', $clientDbUser->id);

            // Use the most specific filter available
            if (!empty($validated['question_id'])) {
                \Log::info('Filtering by question_id:', ['question_id' => $validated['question_id']]);
                $query->where('tasks.question_id', $validated['question_id']);
            } 
            elseif (!empty($validated['section_id'])) {
                \Log::info('Filtering by section_id:', ['section_id' => $validated['section_id']]);
                $query->where(function($q) use ($validated) {
                    $q->where('tasks.section_id', $validated['section_id'])
                      ->orWhereExists(function($subQ) use ($validated) {
                          $subQ->select(DB::raw(1))
                               ->from('questions')
                               ->whereColumn('tasks.question_id', 'questions.id')
                               ->where('questions.section_id', $validated['section_id']);
                      });
                });
            }
            elseif (!empty($validated['format_id'])) {
                \Log::info('Filtering by format_id:', ['format_id' => $validated['format_id']]);
                $query->where(function($q) use ($validated) {
                    $q->where('tasks.format_id', $validated['format_id'])
                      ->orWhereExists(function($subQ) use ($validated) {
                          $subQ->select(DB::raw(1))
                               ->from('sections')
                               ->whereColumn('tasks.section_id', 'sections.id')
                               ->where('sections.format_id', $validated['format_id']);
                      })
                      ->orWhereExists(function($subQ) use ($validated) {
                          $subQ->select(DB::raw(1))
                               ->from('questions')
                               ->join('sections', 'questions.section_id', '=', 'sections.id')
                               ->whereColumn('tasks.question_id', 'questions.id')
                               ->where('sections.format_id', $validated['format_id']);
                      });
                });
            }
            elseif (!empty($validated['process_id'])) {
                \Log::info('Filtering by process_id:', ['process_id' => $validated['process_id']]);
                $query->where(function($q) use ($validated) {
                    $q->where('tasks.process_id', $validated['process_id'])
                      ->orWhereExists(function($subQ) use ($validated) {
                          $subQ->select(DB::raw(1))
                               ->from('formats')
                               ->whereColumn('tasks.format_id', 'formats.id')
                               ->where('formats.process_id', $validated['process_id']);
                      })
                      ->orWhereExists(function($subQ) use ($validated) {
                          $subQ->select(DB::raw(1))
                               ->from('sections')
                               ->join('formats', 'sections.format_id', '=', 'formats.id')
                               ->whereColumn('tasks.section_id', 'sections.id')
                               ->where('formats.process_id', $validated['process_id']);
                      })
                      ->orWhereExists(function($subQ) use ($validated) {
                          $subQ->select(DB::raw(1))
                               ->from('questions')
                               ->join('sections', 'questions.section_id', '=', 'sections.id')
                               ->join('formats', 'sections.format_id', '=', 'formats.id')
                               ->whereColumn('tasks.question_id', 'questions.id')
                               ->where('formats.process_id', $validated['process_id']);
                      });
                });
            }

            // Log the final SQL query
            \Log::info('Final SQL:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Get the results
            $tasks = $query->select('tasks.*')
                ->distinct()
                ->get();

            \Log::info('Tasks found:', [
                'count' => $tasks->count(),
                'task_ids' => $tasks->pluck('id')->toArray()
            ]);

            $mappedTasks = $tasks->map(function ($task) {
                $taskType = $this->determineTaskType($task);
                \Log::info('Mapping task:', [
                    'task_id' => $task->id,
                    'type' => $taskType,
                    'process_id' => $task->process_id,
                    'format_id' => $task->format_id,
                    'section_id' => $task->section_id,
                    'question_id' => $task->question_id
                ]);

                return [
                    'id' => $task->id,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'deadline' => $task->deadline,
                    'created_at' => $task->created_at,
                    'task_type' => $taskType,
                    'assigned_by' => $task->creator ? [
                        'id' => $task->creator->id,
                        'name' => $task->creator->name,
                        'email' => $task->creator->email,
                    ] : null,
                    'hierarchy' => [
                        'process' => $task->process ? [
                            'id' => $task->process->id,
                            'name' => $task->process->name,
                        ] : null,
                        'format' => $task->format ? [
                            'id' => $task->format->id,
                            'name' => $task->format->name,
                        ] : null,
                        'section' => $task->section ? [
                            'id' => $task->section->id,
                            'name' => $task->section->name,
                        ] : null,
                        'question' => $task->question ? [
                            'id' => $task->question->id,
                            'text' => $task->question->text,
                        ] : null,
                    ],
                ];
            });

            return response()->json([
                'data' => [
                    'total_count' => $mappedTasks->count(),
                    'tasks' => $mappedTasks,
                    'filters_applied' => array_filter($validated),
                ],
                'status_code' => 200,
                'message' => 'Tasks fetched successfully',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching filtered tasks', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => $user->id ?? null,
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch tasks',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function determineTaskType($task)
    {
        if ($task->question_id) {
            return 'question';
        } elseif ($task->section_id) {
            return 'section';
        } elseif ($task->format_id) {
            return 'format';
        } elseif ($task->process_id) {
            return 'process';
        }
        return 'unknown';
    }

}
