<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Notification;
use App\Models\UserTask;
use App\Services\ClientDatabaseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskMonitoringController extends Controller
{
    public function getOverdueTasks(Request $request)
    {
        $user = auth('sanctum')->user();
      

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            ClientDatabaseManager::setConnection($user->database_name);  

            $overdueTasks = Task::with('creator') // Ensure client_dbusers_id is loaded via 'creator' relationship
            ->where('deadline', '<', Carbon::now())
            ->get();
           

       
            // Group tasks by department for better organization
            $groupedTasks = $overdueTasks->groupBy('department_id');
            
            // Process each task and send reminders if needed
            $processedTasks = $groupedTasks->map(function ($departmentTasks) {
                return $departmentTasks->map(function ($task) {
                    // Determine reminder urgency based on days overdue
                    $reminderUrgency = $this->calculateReminderUrgency($task->days_overdue);
                  
                    
                    // Check if we need to send a new reminder
                    // if ($this->shouldSendReminder($task->last_reminder_sent, $reminderUrgency)) {
                     
                        $this->sendReminder($task);
                        
                        // Update last reminder sent timestamp
                        DB::connection('client')
                            ->table('user_tasks')
                            ->where('task_id', $task->id)
                            ->update(['last_reminder_sent' => now()]);
                    // }

                    // Add additional task metadata
                    return [
                        'id' => $task->id,
                        'description' => $task->description,
                        'deadline' => $task->deadline,
                        'days_overdue' => $task->days_overdue,
                        'priority' => $task->priority,
                        'status' => $task->status,
                        'assigned_to' => $task->assigned_to,
                        'email' => $task->email,
                        'urgency_level' => $reminderUrgency,
                    ];
                });
            });

            // Prepare summary statistics
            $summary = [
                'total_overdue' => $overdueTasks->count(),
                'high_priority_overdue' => $overdueTasks->where('priority', 'High')->count(),
                'critical_overdue' => $overdueTasks->where('days_overdue', '>', 7)->count(),
                'departments_affected' => $groupedTasks->count()
            ];

            return response()->json([
                'summary' => $summary,
                'overdue_tasks' => $processedTasks,
                'timestamp' => now()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch overdue tasks',
                'error' => $e->getMessage()
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

    private function shouldSendReminder($lastReminderSent, $urgency)
    {
        if (!$lastReminderSent) {
            return true;
        }

        $lastReminder = Carbon::parse($lastReminderSent);
        $now = Carbon::now();

        // Reminder frequency based on urgency
        switch ($urgency) {
            case 'critical':
                return $lastReminder->addHours(24)->lt($now);
            case 'high':
                return $lastReminder->addDays(2)->lt($now);
            case 'medium':
                return $lastReminder->addDays(3)->lt($now);
            default:
                return $lastReminder->addDays(5)->lt($now);
        }
    }

    private function sendReminder(Task $task)
{
    try {
        // Ensure task has a valid creator
        if (!$task->creator) {
            throw new \Exception('Task has no associated creator');
        }

        Notification::create([
            'client_dbusers_id' => $task->creator->id, // Access via relationship
            'type' => 'task_overdue',
            'message' => sprintf( // Correct function name here
                'Task "%s" is overdue by %d days. Deadline was: %s. Priority: %s',
                $task->description,
                now()->diffInDays(Carbon::parse($task->deadline)), // Calculate days overdue
                Carbon::parse($task->deadline)->format('Y-m-d'),
                $task->priority
            ),
        ]);

        // Optional: Log success
        \Log::info('Reminder sent successfully for task ' . $task->id);
    } catch (\Exception $e) {
        \Log::error('Failed to send reminder for task ' . $task->id . ': ' . $e->getMessage());
    }
}

}