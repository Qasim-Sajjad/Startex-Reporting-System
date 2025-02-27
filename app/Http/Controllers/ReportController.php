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


class ReportController extends Controller
{
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
    public function getReportAnalytics(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            ClientDatabaseManager::setConnection($user->database_name);

            $validated = $request->validate([
                'process_id' => 'required|integer',
                'hierarchy_id' => 'required|integer'
            ]);

            $process = Process::with(['frequency', 'format'])->findOrFail($validated['process_id']);
            $startDate = max($process->start_date, now()->subMonths(3)); // Only analyze last 3 months
            $endDate = min($process->process_deadline, now());

            // Get all actual submissions
            $submissions = ScoreAnalysis::where('hierarchy_id', $validated['hierarchy_id'])
                ->where('format_id', $process->format->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(function($item) {
                    return $item->created_at->format('Y-m-d');
                });

            // Calculate expected and missing dates
            $expectedDates = $this->calculateExpectedDates($process, $startDate, $endDate);
            
            $submissionAnalysis = $this->analyzeDateSubmissions(
                $expectedDates,
                $submissions,
                $process
            );

            return response()->json([
                'data' => [
                    'total_expected' => count($expectedDates),
                    'total_submitted' => count($submissions),
                    'on_time_submissions' => $submissionAnalysis['on_time'],
                    'late_submissions' => $submissionAnalysis['late'],
                    'very_late_submissions' => $submissionAnalysis['very_late'],
                    'missing_submissions' => $submissionAnalysis['missing'],
                    'missing_dates' => $submissionAnalysis['missing_dates'],
                    'submission_analytics' => $submissionAnalysis['details']
                ],
                'status_code' => 200,
                'message' => 'Report analytics retrieved successfully',
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error in report analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching report analytics.',
                'status_code' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getProcessAnalytics(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            ClientDatabaseManager::setConnection($user->database_name);

            $validated = $request->validate([
                'process_id' => 'required|integer'
            ]);

            // Get process with hierarchy name
            $process = Process::with(['frequency', 'format', 'hierarchy'])
                ->findOrFail($validated['process_id']);

            // Get last level for this hierarchy
            $lastLevel = HierarchyLevel::where('hierarchynames_id', $process->hierarchynames_id)
                ->orderBy('level', 'desc')
                ->first();

            // Get all end locations
            $endLocations = Hierarchy::with('location')
                ->where('hierarchylevels_id', $lastLevel->id)
                ->get();

            $startDate = max($process->start_date, now()->subMonths(3));
            $endDate = min($process->process_deadline, now());
            $expectedDates = $this->calculateExpectedDates($process, $startDate, $endDate);

            $locationsAnalytics = [];
            $totalStats = [
                'total_expected' => 0,
                'total_submitted' => 0,
                'on_time' => 0,
                'late' => 0,
                'very_late' => 0,
                'missing' => 0
            ];

            foreach ($endLocations as $location) {
                // Get submissions for this location
                $submissions = ScoreAnalysis::where('hierarchy_id', $location->id)
                    ->where('format_id', $process->format->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get()
                    ->groupBy(function($item) {
                        return $item->created_at->format('Y-m-d');
                    });

                $locationAnalysis = $this->analyzeDateSubmissions(
                    $expectedDates,
                    $submissions,
                    $process
                );

                // Update total stats
                $totalStats['total_expected'] += count($expectedDates);
                $totalStats['total_submitted'] += count($submissions);
                $totalStats['on_time'] += $locationAnalysis['on_time'];
                $totalStats['late'] += $locationAnalysis['late'];
                $totalStats['very_late'] += $locationAnalysis['very_late'];
                $totalStats['missing'] += $locationAnalysis['missing'];

                $locationsAnalytics[] = [
                    'location_id' => $location->id,
                    'location_name' => $location->location->name,
                    'analytics' => $locationAnalysis
                ];
            }

            return response()->json([
                'data' => [
                    'process_name' => $process->name,
                    'frequency' => $process->frequency->name,
                    'total_locations' => count($endLocations),
                    'summary_stats' => $totalStats,
                    'locations_analytics' => $locationsAnalytics
                ],
                'status_code' => 200,
                'message' => 'Process analytics retrieved successfully',
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error in process analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching process analytics.',
                'status_code' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function calculateExpectedDates($process, $startDate, $endDate)
    {
        $expectedDates = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            if ($this->isValidSubmissionDay($process, $currentDate->format('l'), $currentDate->format('j'))) {
                $expectedDates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        return $expectedDates;
    }
    private function analyzeDateSubmissions($expectedDates, $submissions, $process)
    {
        $analysis = [
            'on_time' => 0,
            'late' => 0,
            'very_late' => 0,
            'missing' => 0,
            'missing_dates' => [],
            'details' => []
        ];

        foreach ($expectedDates as $date) {
            if (!isset($submissions[$date])) {
                $analysis['missing']++;
                $analysis['missing_dates'][] = $date;
                continue;
            }

            $daySubmissions = $submissions[$date];
            $submissionTime = $daySubmissions->first()->created_at->format('H:i:s');
            $deadline = $process->submission_deadline;
            $graceEnd = (new \DateTime($deadline))->modify("+{$process->grace_period_minutes} minutes")->format('H:i:s');

            $status = $this->getSubmissionStatus($submissionTime, $deadline, $graceEnd);
            $analysis[$status]++;

            $analysis['details'][] = [
                'date' => $date,
                'status' => $status,
                'submission_time' => $submissionTime,
                'deadline' => $deadline,
                'grace_end' => $graceEnd
            ];
        }

        return $analysis;
    }

    private function getSubmissionStatus($submissionTime, $deadline, $graceEnd)
    {
        if ($submissionTime <= $deadline) {
            return 'on_time';
        } elseif ($submissionTime <= $graceEnd) {
            return 'late';
        }
        return 'very_late';
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
}