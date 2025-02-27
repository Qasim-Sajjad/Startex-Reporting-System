<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Notification;
use App\Models\UserTask;
use App\Services\ClientDatabaseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClientDBUser;

class TaskMonitoringController extends Controller
{
    public function getOverdueTasks(Request $request)
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

        try {
            // Switch to client-specific database
            ClientDatabaseManager::setConnection($user->database_name);

            // Fetch overdue tasks
            $overdueTasks = Task::with('creator')
                ->where('deadline', '<', Carbon::now())
                ->get();

            // Group tasks by department
            $groupedTasks = $overdueTasks->groupBy('department_id');

            // Process each task and send reminders if needed
            $processedTasks = $groupedTasks->map(function ($departmentTasks) {
                return $departmentTasks->map(function ($task) {
                    $reminderUrgency = $this->calculateReminderUrgency(
                        now()->diffInDays(Carbon::parse($task->deadline))
                    );

                    // Send reminder
                    $this->sendReminder($task);

                    // Update last reminder sent timestamp
                    DB::connection('client')
                        ->table('user_tasks')
                        ->where('task_id', $task->id)
                        ->update(['last_reminder_sent' => now()]);

                    // Add task metadata
                    return [
                        'id' => $task->id,
                        'description' => $task->description,
                        'deadline' => $task->deadline,
                        'days_overdue' => abs(now()->diffInDays(Carbon::parse($task->deadline))),
                        'priority' => $task->priority,
                        'status' => $task->status,
                        'assigned_to' => $task->creator->name ?? 'N/A',
                        'email' => $task->creator->email ?? 'N/A',
                        'urgency_level' => $reminderUrgency,
                    ];
                });
            });

            // Prepare summary statistics
            $summary = [
                'total_overdue' => $overdueTasks->count(),
                'high_priority_overdue' => $overdueTasks->where('priority', 'High')->count(),
                'critical_overdue' => $overdueTasks->where(
                    fn ($task) => now()->diffInDays(Carbon::parse($task->deadline)) > 7
                )->count(),
                'departments_affected' => $groupedTasks->count(),
            ];

            return response()->json([
                'data' => [
                    'summary' => $summary,
                    'overdue_tasks' => $processedTasks,
                ],
                'status_code' => 200,
                'message' => 'Overdue tasks fetched successfully',
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch overdue tasks',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    private function calculateReminderUrgency($daysOverdue)
    {
        if ($daysOverdue > 7) {
            return 'critical';
        } elseif ($daysOverdue > 3) {
            return 'high';
        } elseif ($daysOverdue > 1) {
            return 'medium';
        }
        return 'low';
    }

    private function sendReminder(Task $task)
    {
        try {
            if (!$task->creator) {
                throw new \Exception('Task has no associated creator');
            }

            Notification::create([
                'client_dbusers_id' => $task->creator->id,
                'type' => 'task_overdue',
                'message' => sprintf(
                    'Task "%s" is overdue by %d days. Deadline was: %s. Priority: %s',
                    $task->description,
                    now()->diffInDays(Carbon::parse($task->deadline)),
                    Carbon::parse($task->deadline)->format('Y-m-d'),
                    $task->priority
                ),
            ]);

            \Log::info('Reminder sent successfully for task ' . $task->id);
        } catch (\Exception $e) {
            \Log::error('Failed to send reminder for task ' . $task->id . ': ' . $e->getMessage());
        }
    }
    public function getUserOverdueTasks(Request $request)
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

        try {
            // Switch to client-specific database
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)
            ->first();

            
            // Fetch overdue tasks assigned to the authenticated user
            $overdueTasks = Task::with('creator')
                ->join('user_tasks', 'tasks.id', '=', 'user_tasks.task_id')
                ->where('user_tasks.client_dbusers_id', $clientDbUser->id)
                ->where('tasks.deadline', '<', Carbon::now())
                ->select('tasks.*') // Select task details
                ->get();

            // Process tasks
            $processedTasks = $overdueTasks->map(function ($task) {
                $daysOverdue = abs(now()->diffInDays(Carbon::parse($task->deadline)));

                return [
                    'id' => $task->id,
                    'description' => $task->description,
                    'deadline' => $task->deadline,
                    'days_overdue' => $daysOverdue,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'assigned_by' => $task->creator->name ?? 'N/A',
                    'email' => $task->creator->email ?? 'N/A',
                    'urgency_level' => $this->calculateReminderUrgency($daysOverdue),
                ];
            });

            return response()->json([
                'data' => $processedTasks,
                'status_code' => 200,
                'message' => 'Your overdue tasks fetched successfully',
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch your overdue tasks',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

}
