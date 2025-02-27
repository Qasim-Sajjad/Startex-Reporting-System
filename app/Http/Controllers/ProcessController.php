<?php
namespace App\Http\Controllers;

use App\Models\ProcessUser;
use App\Models\ClientDBUser;
use App\Models\Process;
use App\Models\HierarchyName;
use App\Models\Hierarchy;
use App\Models\HierarchyLevel;
use App\Models\ScoreAnalysis;
use App\Models\QOption;
use Illuminate\Http\Request;
use App\Services\ClientDatabaseManager;
use App\Models\AttachmentRule;
use App\Models\AttachmentEntity;
use App\Models\Attachment;
use Illuminate\Support\Facades\Session; // Add the correct namespace for Session
use App\Models\Format;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

use App\Models\Frequency;

class ProcessController extends Controller
{
      public function createProcess(Request $request)
    {
       // Ensure the connection is set before performing any database actions
        $database_name = session::get('client_database');
        $connection = ClientDatabaseManager::setConnection($database_name);
      $frequencies = Frequency::get();

          return view('admin.processandfrequency', [
       'frequencies'=>$frequencies,
    ]);

    }
    public function processFrequency(Request $request)
    {
       // Ensure the connection is set before performing any database actions
        $database_name = session::get('client_database');
        $connection = ClientDatabaseManager::setConnection($database_name);
//dd($request->all());
//dd(DB::connection()->getDatabaseName());

    Process::create([
        'name' => $request->process_name,
        'frequency_id' => $request->frequency_id,
        'start_date' => $request->start_date,
        'process_deadline' => $request->end_date,
    ]);
    return redirect()->back()->with('success', 'Process saved successfully!');
    }
    
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

    public function getAllProcesses(Request $request)
{
    $user = auth('sanctum')->user();

    // Ensure the client is authenticated
    if (!$user) {
        return response()->json([
            'data' => null,
            'success' => false,
            'status_code' => 401,
            'message' => 'Unauthorized',
        ], 401);
    }

    // Set the connection to the client's database
    ClientDatabaseManager::setConnection($user->database_name);

    // Fetch all processes with their related data
    $processes = Process::with(['frequency', 'format', 'sections', 'attachments'])->get();

    // Check if any processes exist
    if ($processes->isEmpty()) {
        return response()->json([
            'data' => [],
            'success' => true,
            'status_code' => 404,
            'message' => 'No processes found for this client',
        ], 404);
    }

    // Return the processes with their details
    return response()->json([
        'data' => $processes,
        'success' => true,
        'status_code' => 200,
        'message' => 'Processes retrieved successfully',
    ], 200);
}

    public function fetchUserProcesses(Request $request)
    {

        try {
            $user = $request->user();
          

            // Dynamically switch to the user's database
            ClientDatabaseManager::setConnection($user->database_name);

            $clientDbUser = ClientDBUser::where('email', $user->email)
                ->first();

            // Fetch hierarchy records for the user
            $hierarchies = Hierarchy::where('client_dbusers_id', $clientDbUser->id)->get();
           

            if ($hierarchies->isEmpty()) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'No hierarchy found for the user.',
                    'success' => false,
                ], 404);
            }

            // Retrieve unique hierarchynames IDs
            $hierarchynamesIds = $hierarchies
                ->map(function ($hierarchy) {
                    return $hierarchy->level->hierarchynames_id ?? null;
                })
                ->filter()
                ->unique();
              
            if ($hierarchynamesIds->isEmpty()) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'No hierarchynames found for the user hierarchy.',
                    'success' => false,
                ], 404);
            }

            // Fetch processes linked to the user's hierarchynames
            $processes = Process::whereIn('hierarchynames_id', $hierarchynamesIds)->get();

            if ($processes->isEmpty()) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'No processes found for the user.',
                    'success' => false,
                ], 404);
            }

            return response()->json([
                'data' => $processes,
                'status_code' => 200,
                'message' => 'Processes fetched successfully.',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch processes.',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchProcessDetails(Request $request)
    {
        try {
            // Get the authenticated user
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'data' => null,
                    'status_code' => 401,
                    'message' => 'Unauthorized',
                    'success' => false,
                ], 401);
            }
            ClientDatabaseManager::setConnection($user->database_name);
            

            // Find the corresponding client_dbuser using email
            $clientDbUser = ClientDBUser::where('email', $user->email)
                ->first();
            
            if (!$clientDbUser) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Client user not found',
                    'success' => false,
                ], 404);
            }

            // Now you can use $clientDbUser->id for further operations
            // Ensure the database connection is set
           

            // Get hierarchy ID for the user
            $hierarchy = Hierarchy::where('client_dbusers_id', $clientDbUser->id)->first();
            if (!$hierarchy) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Hierarchy not found for the user.',
                    'success' => false,
                ], 404);
            }

            // Backtrack to get the `hierarchynames_id`
            $hierarchyName = HierarchyName::find($hierarchy->level->hierarchynames_id);
            if (!$hierarchyName) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Hierarchy name not found for the user.',
                    'success' => false,
                ], 404);
            }
          

            // Fetch processes and related data
            $processes = Process::with([
                'format.sections.questions.options',
                'format.sections.questions.attachmentRules',
                'format.sections.attachmentRules',
                'format.attachmentRules',
                'attachments',
            ])->where('hierarchynames_id', $hierarchyName->id)->get();

            return response()->json([
                'data' => $processes,
                'status_code' => 200,
                'message' => 'Processes and related details fetched successfully.',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch process details.',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function submitResponses(Request $request)
    {
        try {
            // Validate and authenticate the user
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Set client's database connection

            ClientDatabaseManager::setConnection($user->database_name);
            // Find the corresponding client_dbuser using email
            $clientDbUser = ClientDBUser::where('email', $user->email)
                ->first();

            // Validate request inputs
            $validated = $request->validate([
                'format_id' => 'required|integer',
                'responses' => 'required|array|min:1',
                'responses.*.question_id' => 'required|integer',
                'responses.*.option_id' => 'required|integer',
                'responses.*.response' => 'required|string',
                'responses.*.comment' => 'nullable|string',
            ]);

            $formatId = $validated['format_id'];
            $responses = $validated['responses'];

            // Get the format and related process
            $format = Format::with(['process', 'sections.questions' => function($query) {
                $query->where('required', true);
            }])->findOrFail($formatId);

            $process = $format->process;
            if (!$process) {
                return response()->json([
                    'message' => 'Process not found for this format.',
                    'status_code' => 404,
                    'success' => false,
                ], 404);
            }
            // Get user's hierarchy
            $hierarchy = Hierarchy::where('client_dbusers_id', $clientDbUser->id)->first();
            

            if (!$hierarchy || $user->role !== 'EndUser') {
                return response()->json([
                    'message' => 'Only last-level users can submit checklists.',
                    'status_code' => 403,
                    'success' => false,
                ], 403);
            }

            // Check if user has already submitted today based on frequency
            $lastSubmission = ScoreAnalysis::where('hierarchy_id', $hierarchy->id)
                ->where('format_id', $formatId)
                ->latest()
                ->first();

            if ($lastSubmission && !$this->canSubmitAgain($process, $lastSubmission->created_at)) {
                return response()->json([
                    'message' => 'You have already submitted this checklist for the current period.',
                    'status_code' => 422,
                    'success' => false,
                ], 422);
            }

            // Verify all required questions are answered
            $requiredQuestionIds = collect();
            foreach ($format->sections as $section) {
                $requiredQuestionIds = $requiredQuestionIds->concat(
                    $section->questions->pluck('id')
                );
            }

            $submittedQuestionIds = collect($responses)->pluck('question_id');
            $missingRequiredQuestions = $requiredQuestionIds->diff($submittedQuestionIds);

            if ($missingRequiredQuestions->isNotEmpty()) {
                return response()->json([
                    'message' => 'All required questions must be answered.',
                    'status_code' => 422,
                    'success' => false,
                    'missing_questions' => $missingRequiredQuestions->values(),
                ], 422);
            }

            // Validate attachments for all levels
            $errors = $this->validateAttachments($responses, $user, $formatId);
            if (!empty($errors)) {
                return response()->json([
                    'data' => null,
                    'status_code' => 422,
                    'message' => 'Attachment validation failed.',
                    'errors' => $errors,
                    'success' => false,
                ], 422);
            }
          

            // Process and store the responses
            $processedResponses = [];
            foreach ($responses as $response) {
                // Validate QOption exists and get the correct q_option_id
                $qOption = QOption::where('question_id', $response['question_id'])
                    ->where('option_id', $response['option_id'])
                    ->first();

                if (!$qOption) {
                    \Log::error('QOption not found', [
                        'question_id' => $response['question_id'],
                        'option_id' => $response['option_id']
                    ]);
                    continue; // Skip invalid responses
                }

              
                // Save the response
                $storedResponse = ScoreAnalysis::create([
                    'format_id' => $formatId,
                    'q_option_id' => $qOption->id, // Use the q_option_id, not option_id
                    'hierarchy_id' => $hierarchy->id,
                    'response' => $response['response'],
                    'comment' => $response['comment'] ?? null,
                ]);

                $processedResponses[] = $storedResponse;
            }

            if (empty($processedResponses)) {
                return response()->json([
                    'message' => 'No valid responses were processed.',
                    'status_code' => 422,
                    'success' => false,
                ], 422);
            }

            // Add debug information in the response
            return response()->json([
                'data' => $processedResponses,
                'debug' => [
                    'total_responses' => count($responses),
                    'processed_responses' => count($processedResponses),
                    'q_options_found' => collect($processedResponses)->pluck('q_option_id'),
                ],
                'status_code' => 201,
                'message' => 'Responses submitted successfully.',
                'success' => true,
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Response submission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while submitting responses.',
                'status_code' => 500,
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateAttachments($responses, $user, $formatId)
    {
        $errors = [];
       
        // 1. Validate Format Level Attachments
        $formatRule = AttachmentRule::where('entity_type', 'App\\Models\\Format')
            ->where('entity_id', $formatId)
            ->first();
          
            
        if ($formatRule) {
            $formatAttachments = Attachment::whereHas('attachmentEntities', function ($query) use ($formatId) {
                $query->where('entity_type', 'App\\Models\\Format')
                    ->where('entity_id', $formatId);
            })
            ->where('uploaded_by', $user->id)
            ->pluck('id');
            
            
            if ($formatAttachments->isEmpty()) {
                $errors[] = "No attachments found for Format ID: {$formatId}";
            } else {
                $this->validateAttachmentCategories($formatRule, $formatAttachments, $errors, "Format");
            }
        }

        // Group responses by section for section-level validation
        $sectionQuestions = Question::whereIn('id', collect($responses)->pluck('question_id'))
            ->with('section')
            ->get()
            ->groupBy('section_id');
          

        // 2. Validate Section Level Attachments
        foreach ($sectionQuestions as $sectionId => $questions) {
            $sectionRule = AttachmentRule::where('entity_type', 'App\\Models\\Section')
                ->where('entity_id', $sectionId)
                ->first();
              
                
            if ($sectionRule) {
                $sectionAttachments = Attachment::whereHas('attachmentEntities', function ($query) use ($sectionId) {
                    $query->where('entity_type', 'App\\Models\\Section')
                        ->where('entity_id', $sectionId);
                })
                ->where('uploaded_by', $user->id)
                ->pluck('id');
                
                if ($sectionAttachments->isEmpty()) {
                    $errors[] = "No attachments found for Section ID: {$sectionId}";
                } else {
                    $this->validateAttachmentCategories($sectionRule, $sectionAttachments, $errors, "Section");
                }
            }
        }

        // 3. Validate Question Level Attachments
        foreach ($responses as $response) {
            $questionId = $response['question_id'];
            $questionRule = AttachmentRule::where('entity_type', 'App\\Models\\Question')
                ->where('entity_id', $questionId)
                ->first();

            if ($questionRule) {
                $questionAttachments = Attachment::whereHas('attachmentEntities', function ($query) use ($questionId) {
                    $query->where('entity_type', 'App\\Models\\Question')
                        ->where('entity_id', $questionId);
                })
                ->where('uploaded_by', $user->id)
                ->pluck('id');
                
                if ($questionAttachments->isEmpty()) {
                    $errors[] = "No attachments found for Question ID: {$questionId}";
                } else {
                    $this->validateAttachmentCategories($questionRule, $questionAttachments, $errors, "Question");
                }
            }
        }

        return $errors;
    }

    private function validateAttachmentCategories($rule, $attachments, &$errors, $entityType)
    {
      
        $requiredCategories = explode(',', $rule->allowed_types);
        $submittedCategories = $this->getAttachmentCategories($attachments);

        foreach ($requiredCategories as $category) {
            $category = trim($category);
            if (!in_array($category, $submittedCategories)) {
                $errors[] = "Missing required attachment of type '{$category}' for {$entityType} ID: {$rule->entity_id}";
            }
        }
    }

    private function getAttachmentCategories($attachmentIds)
    {
     
        $categories = [];
        $categoryExtensions = [
            'image' => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'mkv', 'flv', 'webm'],
            'audio' => ['mp3', 'wav', 'aac', 'ogg', 'flac'],
            'file' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
        ];

        
        foreach ($attachmentIds as $attachmentId) {
            $attachment = Attachment::find($attachmentId);
            if ($attachment) {
                $fileExtension = pathinfo($attachment->filename, PATHINFO_EXTENSION);
                foreach ($categoryExtensions as $category => $extensions) {
                    if (in_array(strtolower($fileExtension), $extensions)) {
                        $categories[] = $category;
                        break;
                    }
                }
            }
        }

        return array_unique($categories);
    }

    public function getUpcomingChecklists(Request $request)
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

            // Set client's database connection
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)
                ->first();

            $now = now();

            // Debug hierarchy chain step by step
            $hierarchy = Hierarchy::where('client_dbusers_id', $clientDbUser->id)->first();
            if (!$hierarchy) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Hierarchy not found for the user.',
                    'success' => false,
                ], 404);
            }
            
            // Debug hierarchy level
            if (!$hierarchy->level) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Hierarchy level not found.',
                    'success' => false,
                ], 404);
            }

            // Debug hierarchynames_id
            if (!$hierarchy->level->hierarchynames_id) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'Hierarchy names ID not found.',
                    'success' => false,
                ], 404);
            }

            // Fetch processes with eager loading
            $processes = Process::with(['frequency', 'format.sections'])
                ->where('hierarchynames_id', $hierarchy->level->hierarchynames_id)
                ->where('start_date', '<=', $now)
                ->where('process_deadline', '>=', $now)
                ->get();
           

            if ($processes->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'status_code' => 200,
                    'message' => 'No upcoming checklists found',
                    'success' => true,
                ], 200);
            }

            $mappedProcesses = $processes->map(function ($process) use ($now) {
                // Debug process relationships
                if (!$process->frequency || !$process->format) {
                    \Log::error('Missing relationship', [
                        'process_id' => $process->id,
                        'has_frequency' => isset($process->frequency),
                        'has_format' => isset($process->format)
                    ]);
                    return null;
                }

                $submissionWindow = $this->getSubmissionWindow($process, $now);
               
                $nextDueDate = $this->calculateNextDueDate($process, $now);
             
                return [
                    'id' => $process->id,
                    'name' => $process->name,
                    'frequency' => [
                        'type' => $process->frequency->name,
                        'specific_days' => $process->specific_days
                    ],
                    'checklist_timing' => [
                        'starts_at' => $process->submission_start_time,
                        'deadline' => $process->submission_deadline,
                        'grace_period_minutes' => $process->grace_period_minutes
                    ],
                    'process_validity' => [
                        'start_date' => $process->start_date,
                        'end_date' => $process->process_deadline,
                        'days_remaining' => $now->diffInDays($process->process_deadline)
                    ],
                    'next_checklist' => [
                        'due_date' => $nextDueDate,
                        'submission_window' => $submissionWindow,
                        'status' => $this->getChecklistStatus($process, $now, $nextDueDate)
                    ],
                    'format' => [
                        'id' => $process->format->id,
                        'name' => $process->format->name,
                        'sections_count' => $process->format->sections->count()
                    ]
                ];
            })
            ->filter()
            ->values();

            return response()->json([
                'data' => $mappedProcesses,
                'status_code' => 200,
                'message' => 'Upcoming checklists fetched successfully',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('GetUpcomingChecklists Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch upcoming checklists',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateNextDueDate($process, $now)
    {
       
        $frequency = $process->frequency->name;
        $currentTime = $now->format('H:i:s');
        $today = $now->format('l');
        $dayOfMonth = $now->format('j');
     
        
        // If today's submission deadline hasn't passed yet
        if ($currentTime < $process->submission_deadline) {
            // Check if today is a valid day for submission
            if ($this->isValidSubmissionDay($process, $today, $dayOfMonth)) {
             
                return $now->format('Y-m-d');
            }
        }

        // Find next valid date based on frequency
        $nextDate = clone $now;
        $nextDate->addDay(); // Start checking from tomorrow

        // Maximum iterations to prevent infinite loop
        $maxIterations = 31;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            if ($this->isValidSubmissionDay($process, $nextDate->format('l'), $nextDate->format('j'))) {
                if ($nextDate <= $process->process_deadline) {
                    return $nextDate->format('Y-m-d');
                }
                return null; // Process has ended
            }
            $nextDate->addDay();
            $iterations++;
        }

        return null;
    }

    private function isValidSubmissionDay($process, $dayName, $dayOfMonth)
    {
      
        if ($process->exclude_sundays && $dayName === 'Sunday') {
            return false;
        }

        // specific_days is already an array from the cast in Process model
        $specificDays = $process->specific_days;
      
        
        switch ($process->frequency->name) {
            case 'Daily':
                
                return empty($specificDays) || in_array($dayName, $specificDays);
                
            case 'Weekly':
                return empty($specificDays) || in_array($dayName, $specificDays);
                
            case 'Monthly':
                return empty($specificDays) || in_array($dayOfMonth, $specificDays);
                
            default:
                return false;
        }
    }

    private function getSubmissionWindow($process, $now)
    {
       
        $dueDate = $this->calculateNextDueDate($process, $now);
      
        if (!$dueDate) return null;

        $startTime = $process->submission_start_time;
        $deadline = $process->submission_deadline;
        $graceEndTime = (new \DateTime($deadline))->modify("+{$process->grace_period_minutes} minutes")->format('H:i:s');

        return [
            'date' => $dueDate,
            'start_time' => $startTime,
            'deadline' => $deadline,
            'grace_period_ends' => $graceEndTime
        ];
    }

    private function getChecklistStatus($process, $now, $dueDate)
    {
        if (!$dueDate) return 'expired';

        $currentTime = $now->format('H:i:s');
        $submissionStart = $process->submission_start_time;
        $submissionEnd = $process->submission_deadline;
        $graceEnd = (new \DateTime($submissionEnd))->modify("+{$process->grace_period_minutes} minutes")->format('H:i:s');
     
        if ($now->format('Y-m-d') < $dueDate) {
          
            return 'upcoming';
        }

        if ($now->format('Y-m-d') == $dueDate) {
         
            if ($currentTime < $submissionStart) {
               
                return 'upcoming';
            }
            if ($currentTime >= $submissionStart && $currentTime <= $submissionEnd) {
                return 'due_today';
            }
            if ($currentTime > $submissionEnd && $currentTime <= $graceEnd) {
                return 'grace_period';
            }
        }

        return 'overdue';
    }

    private function canSubmitAgain($process, $lastSubmissionDate)
    {
        $now = now();
        $lastSubmission = \Carbon\Carbon::parse($lastSubmissionDate);
        
        switch ($process->frequency->name) {
            case 'Daily':
                // Can't submit twice on the same day
                return $lastSubmission->format('Y-m-d') !== $now->format('Y-m-d');
                
            case 'Weekly':
                // Check if it's a new week and the day is allowed
                if ($process->specific_days) {
                    return !$lastSubmission->isSameWeek($now) && 
                           in_array($now->format('l'), $process->specific_days);
                }
                return !$lastSubmission->isSameWeek($now);
                
            case 'Monthly':
                // Check if it's a new month and the day is allowed
                if ($process->specific_days) {
                    return !$lastSubmission->isSameMonth($now) && 
                           in_array($now->format('j'), $process->specific_days);
                }
                return !$lastSubmission->isSameMonth($now);
                
            default:
                return true;
        }
    }
  public function getProcessEndLocations(Request $request)
    {
        
        try {
        

            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
    
            ClientDatabaseManager::setConnection($user->database_name);
    
            // Validate process_id
            $validated = $request->validate([
                'process_id' => 'required|integer'
            ]);
    
            // Get process with hierarchy name
            $process = Process::with('hierarchy.levels')
                ->findOrFail($validated['process_id']);
           
    
            // Get the last level of this hierarchy
            $lastLevel = HierarchyLevel::where('hierarchynames_id', $process->hierarchynames_id)
                ->orderBy('level', 'desc')
                ->first();
          
            if (!$lastLevel) {
                return response()->json([
                    'message' => 'No hierarchy levels found',
                    'status_code' => 404,
                    'success' => false
                ], 404);
            }
    
            // Get all end locations
            $endLocations = Hierarchy::with('location')
                ->where('hierarchylevels_id', $lastLevel->id)
                ->get();
    
            return response()->json([
                'data' => $endLocations,
                'status_code' => 200,
                'message' => 'End locations retrieved successfully',
                'success' => true
            ], 200);
    
        } catch (\Exception $e) {
            \Log::error('Error fetching end locations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'message' => 'An error occurred while fetching end locations.',
                'status_code' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
   public function getLocationReports(Request $request)
    {
     
   
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            ClientDatabaseManager::setConnection($user->database_name);

            $validated = $request->validate([
                'hierarchy_id' => 'required|integer'
            ]);

          
          
        
            // Get unique submissions grouped by date and format
            $reports = ScoreAnalysis::where('hierarchy_id', $validated['hierarchy_id'])
                      ->select([
                          'format_id',
                          'hierarchy_id',
                          DB::raw('DATE(created_at) as submission_date'),
                          DB::raw('TIME(created_at) as submission_time'),
                          DB::raw('COUNT(*) as response_count')
                      ])
                      ->with([
                          'hierarchy.clientUser:id,name,email',
                          'hierarchy.location:id,name'
                      ])
                      ->groupBy(
                          'format_id',
                          'hierarchy_id',
                          DB::raw('DATE(created_at)'),
                          DB::raw('TIME(created_at)')
                      )
                      ->orderBy('submission_date', 'desc')
                      ->orderBy('submission_time', 'desc')
                      ->get();

            // Enhance report data with format details
            $enhancedReports = $reports->map(function ($report) {
                $format = Format::with('process:id,name,frequency_id', 'process.frequency:id,name')
                    ->find($report->format_id);
                    
                return [
                    'submission_date' => $report->submission_date,
                    'submission_time' => $report->submission_time,
                    'response_count' => $report->response_count,
                    'format_name' => $format->name,
                    'process_name' => $format->process->name,
                    'frequency' => $format->process->frequency->name,
                    'submitted_by' => $report->hierarchy->clientUser->name,
                    'location' => $report->hierarchy->location->name,
                    'format_id' => $report->format_id,
                    'hierarchy_id' => $report->hierarchy_id,
                ];
            });

            return response()->json([
                'data' => $enhancedReports,
                'status_code' => 200,
                'message' => 'Location reports retrieved successfully',
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching location reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching reports.',
                'status_code' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getDetailedReport(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            ClientDatabaseManager::setConnection($user->database_name);

            $validated = $request->validate([
                'format_id' => 'required|integer',
                'hierarchy_id' => 'required|integer',
                'submission_date' => 'required|date',
                'submission_time' => 'required'
            ]);

            // Get format with all relations
            $format = Format::with([
                'process:id,name,frequency_id',
                'process.frequency:id,name',
                'sections' => function($q) {
                    $q->orderBy('order_by');
                },
                'sections.questions' => function($q) {
                    $q->orderBy('order_by');
                }
            ])->findOrFail($validated['format_id']);

            // Get submission timestamp
            $submissionDateTime = $validated['submission_date'] . ' ' . $validated['submission_time'];

            // Get all responses for this submission
            $responses = ScoreAnalysis::with(['qOption.question', 'qOption.option'])
                ->where('format_id', $validated['format_id'])
                ->where('hierarchy_id', $validated['hierarchy_id'])
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') = ?", [
                    date('Y-m-d H:i', strtotime($submissionDateTime))
                ])
                ->get();

            $totalScore = 0;
            $reportData = [
                'format_name' => $format->name,
                'process_name' => $format->process->name,
                'frequency' => $format->process->frequency->name,
                'submission_datetime' => $submissionDateTime,
                'sections' => []
            ];

            foreach ($format->sections as $section) {
                $sectionData = [
                    'name' => $section->name,
                    'questions' => []
                ];

                foreach ($section->questions as $question) {
                    $response = $responses->first(function($r) use ($question) {
                        return $r->qOption->question_id === $question->id;
                    });

                    if ($response) {
                        $score = $response->qOption->score;
                        $totalScore += $score;

                        $sectionData['questions'][] = [
                            'question' => $question->text,
                            'selected_option' => $response->qOption->option->name,
                            'response' => $response->response,
                            'comment' => $response->comment,
                            'score' => $score
                        ];
                    }
                }

                $reportData['sections'][] = $sectionData;
            }

            $reportData['total_score'] = $totalScore;

            return response()->json([
                'data' => $reportData,
                'status_code' => 200,
                'message' => 'Detailed report retrieved successfully',
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching detailed report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching detailed report.',
                'status_code' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
 