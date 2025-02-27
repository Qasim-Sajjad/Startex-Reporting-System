<?php

namespace App\Http\Controllers;

use App\Models\Format;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\hierarchynames;
use App\Models\Hierarchylevels;
use App\Models\hierarchies;
use App\Models\locations;
use Illuminate\Support\Facades\DB;
use App\Models\assignprojects;
use App\Models\waves;
use App\Models\assignshops;
use Illuminate\Support\Facades\Session;
use App\Models\Section;
use App\Models\Question;
use App\Models\Option;
use App\Models\scores;
use App\Models\scoreanalysics;
use App\Models\comments;
use App\Models\VisitAudioRecord;
use Illuminate\Support\Facades\Crypt; // Import the Crypt facade
use App\Models\branchCalculations;
use App\Models\sectionCalculations;
use App\Models\regionCalculations;
use App\Models\Criteria;
use App\Models\Frequency;
use App\Models\HierarchyLevel;
use App\Models\Process;
use App\Services\ClientDatabaseManager;
use App\Models\ScoreAnalysis;
use App\Models\QOption;
use Carbon\Carbon;
use App\Models\Hierarchy;
use App\Models\ClientDBUser;
 class clientDashboardController extends Controller
{

public function show($id,$waveData)
{
    // dd($waveData);
    // Retrieve the client's database connection
     $user_id = session::get('user_id');
    $user_role = session::get('user_role');
    $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
    $processes = Process::select('processes.id', 'processes.name')
    ->join('formats', 'processes.id', '=', 'formats.process_id')
    ->join('score_analysis', 'formats.id', '=', 'score_analysis.format_id')
    ->groupBy('processes.id', 'processes.name')
    ->orderByDesc('processes.id')
    ->get();

    // dd($processes);
    // Fetch all frequencies and processes
    $frequencies = Frequency::get();
  // $processes = Process::orderBy('created_at', 'desc')->get();
    // Fetch the specific process based on the ID
    $process = Process::find($id);
    if (!$process) {
        return redirect()->route('dashboard')->with('error', 'Process not found');
    }

    // Get the frequency ID from the selected process
    $frequencyId = $process->frequency_id;
     $HID=$process->hierarchynames_id;
    //dd( $HID);
    // Determine the grouping logic based on the frequency ID
    switch ($frequencyId) {
        case 1: // Daily
            $groupBy = 'DATE(score_analysis.created_at)';
            break;
        case 2: // Weekly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%u")';
            break;
        case 3: // Monthly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%m")';
            break;
        default:
            $groupBy = null;
    }

    // Fetch the wave data based on the process and frequency
    $wave = [];
    if ($groupBy) {
        $wave = ScoreAnalysis::join('formats', 'score_analysis.format_id', '=', 'formats.id')
            ->join('processes', 'formats.process_id', '=', 'processes.id')
            ->selectRaw("$groupBy AS period, COUNT(*) AS total_records")
            ->where('processes.id', $id) // Use the current process ID
            ->groupByRaw($groupBy)
            ->orderByRaw($groupBy)
            ->get();
    }

   // Default grouping logic
    $groupBy = null;
    $whereCondition = null;
   // Check if waveData is present and handle accordingly
    if ($waveData) {
        // For Daily: If $waveData is a specific date (e.g., "2025-01-29")
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $waveData)) {
            // Apply daily grouping for the exact date
            $groupBy = "DATE(score_analysis.created_at)";
            $whereCondition = ['score_analysis.created_at' => "$waveData"]; // Filter by exact date
        }

       // For Weekly: If $waveData is in week format (e.g., "2025-05" for "Week 1")
if (preg_match('/\d{4}-\d{2}/', $waveData) && strlen($waveData) == 7) {
    // Apply weekly grouping
    $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%u')";
    $whereCondition = ['DATE_FORMAT(score_analysis.created_at, \'%Y-%u\')' => "$waveData"]; // Corrected: Escaped %
}

// For Monthly: If $waveData is in month format (e.g., "2025-01" for "January")
if (preg_match('/\d{4}-\d{2}/', $waveData) && strlen($waveData) == 7) {
    // Apply monthly grouping
    $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%m')";
    $whereCondition = ['DATE_FORMAT(score_analysis.created_at, \'%Y-%m\')' => "$waveData"]; // Corrected: Escaped %
}

    }
    // If frequencyId is set (assuming this comes from your process)
    switch ($frequencyId) {
        case 1: // Daily
            $groupBy = "DATE(score_analysis.created_at)";
         $wavePrefix = 'Day'; // Daily prefix
            break;
        case 2: // Weekly
            $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%u')";
                $wavePrefix = 'Week'; // Weekly prefix
            break;
        case 3: // Monthly
            $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%m')";
                $wavePrefix = 'Month'; // Monthly prefix
            break;
        default:
            $groupBy = null;
    }
   switch ($frequencyId) {
        case 1: // Daily
            $groupBy1 = "DATE(hierarchies.created_at)";
            break;
        case 2: // Weekly
            $groupBy1 = "DATE_FORMAT(hierarchies.created_at, '%Y-%u')";
            break;
        case 3: // Monthly
            $groupBy1 = "DATE_FORMAT(hierarchies.created_at, '%Y-%m')";
            break;
        default:
            $groupBy1 = null;
    }

      $hierarchyData = Hierarchy::getHierarchy($user_id); // Start from id = 19
$hierarchyIds = array_column($hierarchyData, 'hierarchy_id');

if($user_role="Client Admin"){
            if ($waveData == "YTD") {
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                // Fetch trend scores with grouping logic
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }

                //dd($regionScores);

                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) /SUM(questions.tscore) * 100) as overAllScore')
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });
                //  dd($TrendScores);
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();
                $hierarchyIdss = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->pluck('hierarchies.id'); // Get the hierarchy ids

                $MissingShop = Hierarchy::whereIn('id', $hierarchyIdss)
                    ->whereNotIn('id', function ($query) {
                        $query->select('hierarchy_id')
                            ->from('score_analysis');
                    })
                    ->count(); // Get the count of the grouped records


                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->get();

                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                // dd($QuestionWithScore);

                $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('sections', 'questions.section_id', '=', 'sections.id')
                    ->join('formats', 'sections.format_id', '=', 'formats.id')
                    ->select(
                        'sections.name as sectionName',
                        DB::raw('SUM(q_options.score) as achivedScore'),
                        DB::raw('SUM(questions.tscore) as totalScore'),
                        DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                    )
                    ->where('formats.process_id', $id)
                    ->groupBy('sections.id')
                    ->get();
            
$questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();

$recurringQuestions = []; // Array to store recurring questions

foreach ($questions as $question) {

$scoreData = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
    ->join('questions', 'q_options.question_id', '=', 'questions.id')
    ->where('questions.id',$question->questionID)
    ->selectRaw('
        COUNT(*) AS total_records, 
        COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records, 
        ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
    ')
    ->first(); // LIMIT 1 ka alternative


    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question; // Store the question
    }
}
              
              
            } else {
                // dd(1);
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscorE) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }

                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });
                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->get();
                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();
            }

            $hierarchyIdss = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                ->where('processes.id', 1)
                ->where('hierarchylevels.level', function ($query) {
                    $query->selectRaw('MAX(level)')
                        ->from('hierarchylevels')
                        ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                })
                ->pluck('hierarchies.id'); // Get the hierarchy ids

            $MissingShop = Hierarchy::whereIn('id', $hierarchyIdss)
                ->whereNotIn('id', function ($query) {
                    $query->select('hierarchy_id')
                        ->from('score_analysis');
                })
                ->count(); // Get the count of the grouped records


            $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                ->join('questions', 'q_options.question_id', '=', 'questions.id')
                ->join('sections', 'questions.section_id', '=', 'sections.id')
                ->join('formats', 'sections.format_id', '=', 'formats.id')
                ->select(
                    'sections.name as sectionName',
                    DB::raw('SUM(q_options.score) as achivedScore'),
                    DB::raw('SUM(questions.tscore) as totalScore'),
                    DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                )

                ->where('formats.process_id', $id)
                ->groupBy('sections.id')
                ->get();
  
  $questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();


$recurringQuestions = []; // Array to store recurring questions

foreach ($questions as $question) {
    $query = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
        ->join('questions', 'q_options.question_id', '=', 'questions.id')
        ->where('questions.id', $question->questionID);

    // Check if $whereCondition is an array and not empty
    if (!empty($whereCondition) && is_array($whereCondition)) {
        foreach ($whereCondition as $condition) {
            $query->whereRaw($condition);
        }
    }

    $scoreData = $query->selectRaw('
            COUNT(*) AS total_records,
            COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records,
            ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
        ')
        ->first(); // LIMIT 1 ka alternative

    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question->questionID;
    }
}

      
  
}

    // Pass data to the view

    // dd($processes);
  
    return view('admin.processDashboard', [
        'processes' => $processes,
        'selectedProcess' => $process, // Selected process details
        'waves' => $wave, // Wave data for the selected process
      'totalSubmittedShop' => $totalSubmittedShop,
      'totalShop' => $totalShop,
      'MissingShop' => $MissingShop,
      'strengths' => $strengths,
      'weaknesses' => $weaknesses,
      'TrendScores'  => $TrendScores,
     'regionScores'=> $regionScores,
     'SectionScore'=> $SectionScore,
      'OverallScore' => $OverAllScore,
     'recurringQuestions'=> $recurringQuestions
    ]);

}

public function dashboard($id,$waveData)
{
 // dD($wave)
 // echo $wave;
    
       $user = auth('sanctum')->user();
    $connection = ClientDatabaseManager::setConnection($user->database_name);
  
    if($user->role != 'Client Admin')   
    {
		
        $clientDBUser = ClientDBUser::where('email', $user->email)
        ->first();
        $user_id = $clientDBUser->id;
        $user_role = $clientDBUser->role;
    }
    else{
     $user_id = $user->id;
      $user_role = $user->role;
    }
   //dd($user_role);
//dd($user_id);
    // Retrieve theclient's database connection
   //  $user_id = session::get('user_id');
  //  $user_role = session::get('user_role');
   // $database_name = session::get('client_database');
  //  $connection = ClientDatabaseManager::setConnection($database_name);
        $processes = Hierarchy::select('processes.id')
            ->join('hierarchylevels', 'hierarchies.hierarchylevels_id', '=', 'hierarchylevels.id')
            ->join('processes', 'hierarchylevels.hierarchynames_id', '=', 'processes.hierarchynames_id')
            ->where('hierarchies.client_dbusers_id', $user_id)
            ->orderBy('processes.id', 'DESC')
            ->get();
    // Fetch all frequencies and processes
    $frequencies = Frequency::get();
  // $processes = Process::orderBy('created_at', 'desc')->get();
    // Fetch the specific process based on the ID
    $process = Process::find($id);
    if (!$process) {
        return redirect()->route('dashboard')->with('error', 'Process not found');
    }

    // Get the frequency ID from the selected process
    $frequencyId = $process->frequency_id;
     $HID=$process->hierarchynames_id;
    //dd( $HID);
    // Determine the grouping logic based on the frequency ID
    switch ($frequencyId) {
        case 1: // Daily
            $groupBy = 'DATE(score_analysis.created_at)';
            break;
        case 2: // Weekly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%u")';
            break;
        case 3: // Monthly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%m")';
            break;
        default:
            $groupBy = null;
    }

    // Fetch the wave data based on the process and frequency
    $wave = [];
    if ($groupBy) {
        $wave = ScoreAnalysis::join('formats', 'score_analysis.format_id', '=', 'formats.id')
            ->join('processes', 'formats.process_id', '=', 'processes.id')
            ->selectRaw("$groupBy AS period, COUNT(*) AS total_records")
            ->where('processes.id', $id) // Use the current process ID
            ->groupByRaw($groupBy)
            ->orderByRaw($groupBy)
            ->get();
    }

   // Default grouping logic
    $groupBy = null;
    $whereCondition = null;
   // Check if waveData is present and handle accordingly
    if ($waveData) {
        // For Daily: If $waveData is a specific date (e.g., "2025-01-29")
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $waveData)) {
            // Apply daily grouping for the exact date
            $groupBy = "DATE(score_analysis.created_at)";
            $whereCondition = ['score_analysis.created_at' => "$waveData"]; // Filter by exact date
        }

       // For Weekly: If $waveData is in week format (e.g., "2025-05" for "Week 1")
if (preg_match('/\d{4}-\d{2}/', $waveData) && strlen($waveData) == 7) {
    // Apply weekly grouping
    $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%u')";
    $whereCondition = ['DATE_FORMAT(score_analysis.created_at, \'%Y-%u\')' => "$waveData"]; // Corrected: Escaped %
}

// For Monthly: If $waveData is in month format (e.g., "2025-01" for "January")
if (preg_match('/\d{4}-\d{2}/', $waveData) && strlen($waveData) == 7) {
    // Apply monthly grouping
    $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%m')";
    $whereCondition = ['DATE_FORMAT(score_analysis.created_at, \'%Y-%m\')' => "$waveData"]; // Corrected: Escaped %
}

    }
    // If frequencyId is set (assuming this comes from your process)
    switch ($frequencyId) {
        case 1: // Daily
            $groupBy = "DATE(score_analysis.created_at)";
         $wavePrefix = 'Day'; // Daily prefix
            break;
        case 2: // Weekly
            $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%u')";
                $wavePrefix = 'Week'; // Weekly prefix
            break;
        case 3: // Monthly
            $groupBy = "DATE_FORMAT(score_analysis.created_at, '%Y-%m')";
                $wavePrefix = 'Month'; // Monthly prefix
            break;
        default:
            $groupBy = null;
    }

      switch ($frequencyId) {
        case 1: // Daily
            $groupBy1 = "DATE(hierarchies.created_at)";
            break;
        case 2: // Weekly
            $groupBy1 = "DATE_FORMAT(hierarchies.created_at, '%Y-%u')";
            break;
        case 3: // Monthly
            $groupBy1 = "DATE_FORMAT(hierarchies.created_at, '%Y-%m')";
            break;
        default:
            $groupBy1 = null;
    }



//dd($user_role);

     //dd($user_role);
        if ($user_role == "Client Admin") {
            if ($waveData == "YTD") {
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                // Fetch trend scores with grouping logic
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }

                //dd($regionScores);

                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) /SUM(questions.tscore) * 100) as overAllScore')
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });
                //  dd($TrendScores);
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();
                $hierarchyIdss = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->pluck('hierarchies.id'); // Get the hierarchy ids

                $MissingShop = Hierarchy::whereIn('id', $hierarchyIdss)
                    ->whereNotIn('id', function ($query) {
                        $query->select('hierarchy_id')
                            ->from('score_analysis');
                    })
                    ->count(); // Get the count of the grouped records


                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->get();

                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                // dd($QuestionWithScore);

               $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                ->join('questions', 'q_options.question_id', '=', 'questions.id')
                ->join('sections', 'questions.section_id', '=', 'sections.id')
                ->join('formats', 'sections.format_id', '=', 'formats.id')
                ->select(
                    'sections.name as sectionName',
                    DB::raw('SUM(q_options.score) as achivedScore'),
                    DB::raw('SUM(questions.tscore) as totalScore'),
                    DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                )
                ->where('formats.process_id', $id)
                ->groupBy('sections.id')
                ->get();
              
              
              
              $questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();
				$recurringQuestions = []; // Array to store recurring questions
            	  
             
              foreach ($questions as $question) {
               

              $scoreData = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                  ->join('questions', 'q_options.question_id', '=', 'questions.id')
                  ->where('questions.id',$question->questionID)
                  ->selectRaw('
                      COUNT(*) AS total_records, 
                      COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records, 
                      ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
                  ')
                  ->first(); // LIMIT 1 ka alternative
               
					
                  // Check if percentage_lower is less than 50%
                  if ($scoreData && $scoreData->percentage_lower > 50) {
                      $recurringQuestions[] = $question->questionsName; // Store the question
                  }
              }
              
		
         

                //  dd(2);
            } else {
                // dd(1);
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscorE) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }

                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });
                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->get();
                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();

            $hierarchyIdss = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                ->where('processes.id', 1)
                ->where('hierarchylevels.level', function ($query) {
                    $query->selectRaw('MAX(level)')
                        ->from('hierarchylevels')
                        ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                })
                ->pluck('hierarchies.id'); // Get the hierarchy ids

            $MissingShop = Hierarchy::whereIn('id', $hierarchyIdss)
                ->whereNotIn('id', function ($query) {
                    $query->select('hierarchy_id')
                        ->from('score_analysis');
                })
                ->count(); // Get the count of the grouped records


            $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                ->join('questions', 'q_options.question_id', '=', 'questions.id')
                ->join('sections', 'questions.section_id', '=', 'sections.id')
                ->join('formats', 'sections.format_id', '=', 'formats.id')
                ->select(
                    'sections.name as sectionName',
                    DB::raw('SUM(q_options.score) as achivedScore'),
                    DB::raw('SUM(questions.tscore) as totalScore'),
                    DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                )

                ->where('formats.process_id', $id)
                ->groupBy('sections.id');
        
           $questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();    
          
          $recurringQuestions = []; // Array to store recurring questions

foreach ($questions as $question) {
    $query = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
        ->join('questions', 'q_options.question_id', '=', 'questions.id')
        ->where('questions.id', $question->questionID);

    // Check if $whereCondition is an array and not empty
    if (!empty($whereCondition) && is_array($whereCondition)) {
        foreach ($whereCondition as $condition) {
            $query->whereRaw($condition);
        }
    }

    $scoreData = $query->selectRaw('
            COUNT(*) AS total_records,
            COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records,
            ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
        ')
        ->first(); // LIMIT 1 ka alternative

    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question->questionID;
    }
}
              
              
                          }

        }
    elseif ($user_role == "EndUser") 
    {
            // dd(1);
            $hierarchyIds = Hierarchy::where('client_dbusers_id', $user_id)
                ->pluck('id'); // Sirf IDs retrieve karega
        //  dd($hierarchyIds);
            if ($waveData == "YTD") {
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
               // dd($OverAllScore);
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds1 = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds1) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }
                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });

                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->get();
                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id',$id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->whereIn('hierarchies.id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();

                $MissingShop = Hierarchy::whereIn('id', $hierarchyIds)
                    ->whereNotIn('id', function ($query) {
                        $query->select('hierarchy_id')
                            ->from('score_analysis');
                    })
                    ->count(); // Get the count of the grouped records
                $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('sections', 'questions.section_id', '=', 'sections.id')
                    ->join('formats', 'sections.format_id', '=', 'formats.id')
                    ->select(
                        'sections.name as sectionName',
                        DB::raw('SUM(q_options.score) as achivedScore'),
                        DB::raw('SUM(questions.tscore) as totalScore'),
                        DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                    )
                    ->where('formats.process_id', $id)
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupBy('sections.id')
                    ->get();
              $questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();
    $recurringQuestions = []; // Array to store recurring questions

foreach ($questions as $question) {

$scoreData = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
    ->join('questions', 'q_options.question_id', '=', 'questions.id')
    ->where('questions.id',$question->questionID)
      ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)
    ->selectRaw('
        COUNT(*) AS total_records, 
        COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records, 
        ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
    ')
    ->first(); // LIMIT 1 ka alternative


    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question; // Store the question
    }
}
             // dd($SectionScore);
            } else {
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds1 = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds1) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }
                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });
                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition)
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->get();
                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->whereIn('hierarchies.id', $hierarchyIds) // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Apply additional where conditions
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();
                $MissingShop = Hierarchy::whereIn('id', $hierarchyIds)
                    ->whereNotIn('id', function ($query) {
                        $query->select('hierarchy_id')
                            ->from('score_analysis');
                    })
                    ->count(); // Get the count of the grouped records

                $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('sections', 'questions.section_id', '=', 'sections.id')
                    ->join('formats', 'sections.format_id', '=', 'formats.id')
                    ->select(
                        'sections.name as sectionName',
                        DB::raw('SUM(q_options.score) as achivedScore'),
                        DB::raw('SUM(questions.tscore) as totalScore'),
                        DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                    )
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->where('formats.process_id', $id)
                    ->groupBy('sections.id')
                    ->get();
            }
          $questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();
          
          $recurringQuestions = []; // Array to store recurring questions
foreach ($questions as $question) {
    $query = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
        ->join('questions', 'q_options.question_id', '=', 'questions.id')
        ->where('questions.id', $question->questionID)  // Corrected: No semicolon here
        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds);  // Now it's part of the query

    // Check if $whereCondition is an array and not empty
    if (!empty($whereCondition) && is_array($whereCondition)) {
        foreach ($whereCondition as $condition) {
            $query->whereRaw($condition);
        }
    }

    $scoreData = $query->selectRaw('
            COUNT(*) AS total_records,
            COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records,
            ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
        ')
        ->first(); // LIMIT 1 alternative

    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question->questionID;
    }
}

        }
    
    else {
          //dd($user_id);
            $hierarchiesID = Hierarchy::select('hierarchies.id as HID')
            ->join('hierarchylevels', 'hierarchies.hierarchylevels_id', '=', 'hierarchylevels.id')
            ->join('processes', 'hierarchylevels.hierarchynames_id', '=', 'processes.hierarchynames_id')
            ->where('hierarchies.client_dbusers_id', $user_id)
            ->orderBy('processes.id', 'DESC')
            ->first();
         $hID= $hierarchiesID->HID;
       // dd($hID);
            $hierarchyData = Hierarchy::getHierarchy($hID); // Start from id = 19
            $hierarchyIds = array_column($hierarchyData, 'hierarchy_id');
         // dd($hierarchyIds);
         // dd($hierarchyIds);
            if ($waveData == "YTD") {
                // dd($hierarchyIds);
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                //   dd($OverAllScore);
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds1 = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds1) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }
                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });

                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->get();
                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', 1)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->whereIn('hierarchies.id', $hierarchyIds)  // Apply the hierarchy filter
               ->groupByRaw($groupBy1)
        ->orderByRaw($groupBy1)
                    ->count();

                $MissingShop = Hierarchy::whereIn('id', $hierarchyIds)
                    ->whereNotIn('id', function ($query) {
                        $query->select('hierarchy_id')
                            ->from('score_analysis');
                    })
                    ->count(); // Get the count of the grouped records
                $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('sections', 'questions.section_id', '=', 'sections.id')
                    ->join('formats', 'sections.format_id', '=', 'formats.id')
                    ->select(
                        'sections.name as sectionName',
                        DB::raw('SUM(q_options.score) as achivedScore'),
                        DB::raw('SUM(questions.tscore) as totalScore'),
                        DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                    )
                    ->where('formats.process_id', $id)
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->groupBy('sections.id')
                    ->get();
 $questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();
      $recurringQuestions = []; // Array to store recurring questions

foreach ($questions as $question) {
    $query = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
        ->join('questions', 'q_options.question_id', '=', 'questions.id')
        ->where('questions.id', $question->questionID)
        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds);  // No semicolon here

    // Check if $whereCondition is an array and not empty
    if (!empty($whereCondition) && is_array($whereCondition)) {
        foreach ($whereCondition as $condition) {
            $query->whereRaw($condition);
        }
    }

    $scoreData = $query->selectRaw('
            COUNT(*) AS total_records,
            COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records,
            ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
        ')
        ->first(); // LIMIT 1 ka alternative

    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question->questionID;
    }
}


            } else {
                $OverAllScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                //region
                $regions = HierarchyLevel::join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->join('locations', 'hierarchies.location_id', '=', 'locations.id')
                    ->where('hierarchylevels.hierarchynames_id', $HID)
                    ->where('hierarchylevels.level', 2)
                    ->select('hierarchies.id as regionID', 'locations.name as regionName')
                    ->get();
                $regionScores = [];

                foreach ($regions as $region) {
                    $hierarchyData = Hierarchy::getHierarchy($region->regionID); // Get hierarchy data for each region
                    $hierarchyIds1 = array_column($hierarchyData, 'hierarchy_id'); // Extract hierarchy IDs

                    $totalScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                        ->join('questions', 'q_options.question_id', '=', 'questions.id')
                        ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                        ->join('processes', 'formats.process_id', '=', 'processes.id')
                        ->where('processes.id', $id)
                        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds1) // Filter by hierarchy IDs
                        ->selectRaw('SUM(questions.tscore) as totalScore')
                        ->selectRaw('SUM(q_options.score) as achievedScore')
                        ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overallScore')
                        ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                        ->groupByRaw($groupBy)
                        ->orderByRaw($groupBy)
                        ->first();

                    // Store results in an array
                    $regionScores[] = [
                        'regionID' => $region->regionID,
                        'regionName' => $region->regionName,
                        'totalScore' => $totalScore->totalScore ?? 0,
                        'achievedScore' => $totalScore->achievedScore ?? 0,
                        'overallScore' => $totalScore->overallScore ?? 0,
                    ];
                }
                // Fetch trend scores with grouping logic
                $trendScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->selectRaw("$groupBy AS period") // Add the period based on grouping
                    ->groupByRaw($groupBy) // Group by the selected period (Day, Week, or Month)
                    ->orderByRaw($groupBy) // Order the results by period
                    ->get();

                // Format the wave names and scores based on frequency (Day, Week, Month)
                $TrendScores = $trendScore->map(function ($score, $index) use ($wavePrefix) {
                    $date = $score->period;

                    // Format wave name based on frequency
                    if ($wavePrefix === 'Day') {
                        // Use the date directly and create a wave name like "Day 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Week') {
                        // Use the date to calculate weeks, and create a wave name like "Week 1"
                        $score->name = $wavePrefix . ' ' . ($index + 1);
                    } elseif ($wavePrefix === 'Month') {
                        // For monthly, format it as a Month name
                        $score->name = Carbon::parse($date)->format('F'); // Example: January, February, ...
                    }

                    // Ensure 'period' is returned in a readable format
                    if ($wavePrefix === 'Day') {
                        $score->period = Carbon::parse($date)->format('Y-m-d'); // Format for daily
                    } elseif ($wavePrefix === 'Week') {
                        $score->period = Carbon::parse($date)->format('Y-W'); // Format for week
                    } elseif ($wavePrefix === 'Month') {
                        $score->period = Carbon::parse($date)->format('Y-m'); // Format for month
                    }

                    // Ensure scores are floats for accurate display
                    $score->totalScore = (float) $score->totalScore;
                    $score->acheivedScore = (float) $score->acheivedScore;
                    $score->overAllScore = (float) $score->overAllScore;

                    return $score;
                });
                $QuestionWithScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('questions.text')
                    ->selectRaw('SUM(questions.tscore) as totalScore')
                    ->selectRaw('SUM(q_options.score) as acheivedScore')
                    ->selectRaw('ROUND(SUM(q_options.score) / SUM(questions.tscore) * 100) as overAllScore')
                    ->groupByRaw('questions.id')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition)
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->get();
                $greaterthan = Criteria::where('process_id', $id)->max('range1');
                $lessthan = Criteria::where('process_id', $id)->min('range1');

                // Initialize arrays for Strengths and Weaknesses
                $strengths = [];
                $weaknesses = [];

                // Loop through the results to categorize the questions as Strength or Weakness
                foreach ($QuestionWithScore as $question) {
                    if ($question->overAllScore > $greaterthan) {
                        // If the overall score is greater than the "greaterthan" value, it's a Strength
                        $strengths[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    } elseif ($question->overAllScore < $lessthan) {
                        // If the overall score is less than the "lessthan" value, it's a Weakness
                        $weaknesses[] = [
                            'question' => $question->text,
                            'overallScore' => $question->overAllScore,
                        ];
                    }
                }
                $totalSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as totalsubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $lateSubmittedShop = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
                    ->join('processes', 'formats.process_id', '=', 'processes.id')
                    ->where('processes.id', $id)
                    ->selectRaw('COUNT(DISTINCT hierarchy_id) as latesubmittedShop')
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->whereRaw('DATE(score_analysis.created_at) > DATE(processes.process_deadline)')
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Convert array to string
                    ->groupByRaw($groupBy)
                    ->orderByRaw($groupBy)
                    ->first();
                $totalShop = Process::join('hierarchylevels', 'processes.hierarchynames_id', '=', 'hierarchylevels.hierarchynames_id')
                    ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.hierarchylevels_id')
                    ->where('processes.id', $id)
                    ->where('hierarchylevels.level', function ($query) {
                        $query->selectRaw('MAX(level)')
                            ->from('hierarchylevels')
                            ->whereColumn('hierarchynames_id', 'processes.hierarchynames_id');
                    })
                    ->whereIn('hierarchies.id', $hierarchyIds) // Apply the hierarchy filter
                    ->whereRaw(is_array($whereCondition) ? implode(' AND ', $whereCondition) : $whereCondition) // Apply additional where conditions
                    ->groupByRaw($groupBy1)
                    ->orderByRaw($groupBy1)
                    ->count();
                $MissingShop = Hierarchy::whereIn('id', $hierarchyIds)
                    ->whereNotIn('id', function ($query) {
                        $query->select('hierarchy_id')
                            ->from('score_analysis');
                    })
                    ->count(); // Get the count of the grouped records

                $SectionScore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
                    ->join('questions', 'q_options.question_id', '=', 'questions.id')
                    ->join('sections', 'questions.section_id', '=', 'sections.id')
                    ->join('formats', 'sections.format_id', '=', 'formats.id')
                    ->select(
                        'sections.name as sectionName',
                        DB::raw('SUM(q_options.score) as achivedScore'),
                        DB::raw('SUM(questions.tscore) as totalScore'),
                        DB::raw('ROUND((SUM(q_options.score) / SUM(questions.tscore)) * 100) as sectionPercentage')
                    )
                    ->whereIn('score_analysis.hierarchy_id', $hierarchyIds)  // Apply the hierarchy filter
                    ->where('formats.process_id', $id)
                    ->groupBy('sections.id')
                    ->get();
$questions = Question::join('sections', 'questions.section_id', '=', 'sections.id')
    ->join('formats', 'sections.format_id', '=', 'formats.id')
    ->where('formats.process_id', $id)
    ->select('questions.id as questionID', 'questions.text as questionsName')
    ->get();
              $recurringQuestions = []; // Array to store recurring questions

foreach ($questions as $question) {
    $query = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
        ->join('questions', 'q_options.question_id', '=', 'questions.id')
        ->where('questions.id', $question->questionID)
        ->whereIn('score_analysis.hierarchy_id', $hierarchyIds);  // No semicolon here

    // Check if $whereCondition is an array and not empty
    if (!empty($whereCondition) && is_array($whereCondition)) {
        foreach ($whereCondition as $condition) {
            $query->whereRaw($condition);
        }
    }

    $scoreData = $query->selectRaw('
            COUNT(*) AS total_records,
            COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) AS lower_score_records,
            ROUND((COUNT(CASE WHEN q_options.score < questions.tscore THEN 1 END) / COUNT(*)) * 100, 2) AS percentage_lower
        ')
        ->first(); // LIMIT 1 ka alternative

    // Check if percentage_lower is less than 50%
    if ($scoreData && $scoreData->percentage_lower < 50) {
        $recurringQuestions[] = $question->questionID;
    }
}
            }
        }
    
    // Pass data to the view
return response()->json([
    'statusCode' => 200, // HTTP status code
    'message' => 'Data retrieved successfully', // Message indicating success
    'success' => true, // Indicates if the request was successful
    'result' => [
        'processes' => $processes,
        'selectedProcess' => $process,
        'waves' => $wave,
        'totalSubmittedShop' => $totalSubmittedShop,
        'totalShop' => $totalShop,
        'MissingShop' => $MissingShop,
        'strengths' => $strengths,
        'weaknesses' => $weaknesses,
        'TrendScores' => $TrendScores,
        'regionScores' => $regionScores,
         'SectionScore'=> $SectionScore,
        'recurringQuestions'=> $recurringQuestions


    ]
], 200); // 200 is the HTTP status code for success


}
  
public function getWaves($processId)
{

    $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
    $process = Process::find($processId);

    if (!$process) {
        return response()->json([], 404);
    }

    $frequencyId = $process->frequency_id;
    $groupBy = '';

    // Determine grouping logic and wave names
    switch ($frequencyId) {
        case 1: // Daily
            $groupBy = 'DATE(score_analysis.created_at)';
            $wavePrefix = 'Day'; // Daily prefix
            break;
        case 2: // Weekly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%u")';
            $wavePrefix = 'Week'; // Weekly prefix
            break;
        case 3: // Monthly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%m")';
            $wavePrefix = 'Month'; // Monthly prefix
            break;
        default:
            return response()->json([], 400);
    }
  session::put('wavePrefix',$wavePrefix);
    // Fetch waves
    $waves = ScoreAnalysis::join('formats', 'score_analysis.format_id', '=', 'formats.id')
        ->join('processes', 'formats.process_id', '=', 'processes.id')
        ->selectRaw("$groupBy AS period, COUNT(*) AS total_records")
        ->where('processes.id', $processId)
        ->groupByRaw($groupBy)
        ->orderByRaw($groupBy)
        ->get();

    // Modify the result to format wave names
    $formattedWaves = $waves->map(function ($wave, $index) use ($wavePrefix) {
        $date = $wave->period;

        // Format wave name based on frequency
        if ($wavePrefix === 'Day') {
            // For daily: 1, 2, 3, 4, etc.
            $wave->name = $wavePrefix . ' ' . ($index + 1);
        } elseif ($wavePrefix === 'Week') {
            // For weekly: Week 1, Week 2, Week 3, etc.
            $wave->name = $wavePrefix . ' ' . ($index + 1);
        } elseif ($wavePrefix === 'Month') {
            // For monthly: January, February, March, etc.
            $wave->name = Carbon::parse($date)->format('F');
        }

        // You can also keep the period for reference if you need
        $wave->period = $date;

        return $wave;
    });

    return response()->json($formattedWaves);
}
  
public function getWavesAPI($processId)
{

    
    $user = auth('sanctum')->user();
  
 
    $connection = ClientDatabaseManager::setConnection($user->database_name);
    $process = Process::find($processId);

    if (!$process) {
        return response()->json([], 404);
    }

    $frequencyId = $process->frequency_id;
    $groupBy = '';

    // Determine grouping logic and wave names
    switch ($frequencyId) {
        case 1: // Daily
            $groupBy = 'DATE(score_analysis.created_at)';
            $wavePrefix = 'Day'; // Daily prefix
            break;
        case 2: // Weekly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%u")';
            $wavePrefix = 'Week'; // Weekly prefix
            break;
        case 3: // Monthly
            $groupBy = 'DATE_FORMAT(score_analysis.created_at, "%Y-%m")';
            $wavePrefix = 'Month'; // Monthly prefix
            break;
        default:
            return response()->json([], 400);
    }
  session::put('wavePrefix',$wavePrefix);
    // Fetch waves
    $waves = ScoreAnalysis::join('formats', 'score_analysis.format_id', '=', 'formats.id')
        ->join('processes', 'formats.process_id', '=', 'processes.id')
        ->selectRaw("$groupBy AS period, COUNT(*) AS total_records")
        ->where('processes.id', $processId)
        ->groupByRaw($groupBy)
        ->orderByRaw($groupBy)
        ->get();

    // Modify the result to format wave names
    $formattedWaves = $waves->map(function ($wave, $index) use ($wavePrefix) {
        $date = $wave->period;

        // Format wave name based on frequency
        if ($wavePrefix === 'Day') {
            // For daily: 1, 2, 3, 4, etc.
            $wave->name = $wavePrefix . ' ' . ($index + 1);
        } elseif ($wavePrefix === 'Week') {
            // For weekly: Week 1, Week 2, Week 3, etc.
            $wave->name = $wavePrefix . ' ' . ($index + 1);
        } elseif ($wavePrefix === 'Month') {
            // For monthly: January, February, March, etc.
            $wave->name = Carbon::parse($date)->format('F');
        }

        // You can also keep the period for reference if you need
        $wave->period = $date;

        return $wave;
    });

    return response()->json($formattedWaves);
}
public function updateSelection(Request $request)
{
        $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
 // dd($request->all());


    Session::put('wave_id',$request->wave_id);
    Session::put('process_id',$request->process_id);


    return redirect()->back()->with('success', 'Selection updated successfully!');
}

    public function viewdashboard(Request $request)
    {
      dd(1);
        // echo 1;
        // exit();
        $regionID = $request->input('regionID');
        $view = $request->input('view', 'main');
        $format_id =  session::get('format_id');
        $wave_id1 = session::get('wave_id1');
        $wave_id = session::get('wave_id'); // wave 
        $ytd = Session::get('YTD');
        $result = DB::table('formats')
            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')
            ->where('formats.id', $format_id)
            ->where('hierarchylevels.level', 2)
            ->select('hierarchylevels.hierarchylavelname as name')
            ->first(); // Use first() to get a single result

        $secondlevelname = $result->name;
        $criterias = Criteria::where('format_id', $format_id)->get();
        $strengthRange = $criterias->first();
        $weaknessRange = $criterias->last();
        $strenghtcriteria = $strengthRange->range1;
        $weaknesscriteria = $weaknessRange->range1;
        // echo  $regionID;
        // echo $view;
        // echo $format_id;
        // echo '<br>';
        // echo $wave_id;
        // echo '<br>';
        // echo  $ytd;
        // echo '<br>';
        // echo  $wave_id1;
        $overallScore = null;
        if ($view == "main") {
            session::put('title', "Dashboard");
            if (!empty($ytd) && $wave_id == 0) {
                //section
                $counttotal = assignshops::where('format_id', $format_id)
                    ->where('status', 'submit to client')
                    ->count();
                $sections = DB::table('scoreanalysics')
                    ->select(
                        'section_id as sectionid',
                        'section_name as sectionname',
                        DB::raw('SUM(achieved) / SUM(applicable) * 100 AS sectionoverall'),
                        DB::raw('SUM(achieved) as achived'),
                        DB::raw('SUM(applicable) as applicable')
                    )
                    ->where('wave_id', '<=', $ytd)
                    ->where('format_id', '=', $format_id)
                    ->groupBy('section_id', 'section_name')
                    ->get();

                // dd($sections);
                $res1 = [];
                $res2 = [];
                $ress = [];

                foreach ($sections as $section) {
                    $results = DB::table('scoreanalysics')
                        ->select(
                            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) AS overallscore'),
                            'question_id',
                            'question_name',
                            DB::raw('SUM(achieved) as achived'),
                            DB::raw('SUM(applicable) as applicable')

                        )
                        ->where('section_id', $section->sectionid)
                        ->groupBy('question_id', 'question_name')
                        ->get();

                    // Process question results for each section
                    $res3 = [];
                    $background_colors = ['#3162D4']; // You can add more colors if needed

                    foreach ($results as $ques) {
                        if (empty($ques->achived)) {
                            continue;
                        }

                        $rand_background = $background_colors[array_rand($background_colors)];
                        $achappi = 'Achieved: ' . $ques->achived . ' - Applicable: ' . $ques->applicable;

                        $res3[] = [
                            'name' => $ques->question_name . ' : ' . $achappi,
                            'tscore' => $ques->overallscore,
                            'score2' => $ques->achived,
                            'score3' => $ques->applicable
                        ];
                    }

                    // Format section-level result
                    $res1[] = [
                        'name' => $section->sectionname,
                        'y' => round($section->sectionoverall),
                        'drilldown' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'score2' => $section->achived,
                        'score3' => $section->applicable
                    ];
                    // dd( $res);
                    $ress[] = [
                        'name' => $section->sectionname,
                        'id' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'data' => $results->map(function ($result) {
                            return [
                                $result->question_name . ' : Achieved: ' . $result->achived . ' - Applicable: ' . $result->applicable,
                                $result->overallscore,
                                $result->achived,
                                $result->applicable
                            ];
                        })->toArray()
                    ];
                    // dd($ress);
                }
                $mdata = [
                    'res' => $res1,
                    'ress' => $ress
                ];
                // dd($mdata);
                // Output the data as JSON
                //end section
                //overALL start
                // echo 1;
                // exit();
                $overallScore = DB::table('scoreanalysics')
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('assignshops.status', 'submit to client')
                    ->select(DB::raw('ROUND((SUM(achieved) / SUM(applicable)) * 100) as overALLSore'))
                    ->value('overALLSore');
                // overall end
                // trend start

                $trend = DB::table('scoreanalysics')
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')
                    ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', '<=', $ytd)
                    ->where('assignshops.status', "submit to client")
                    ->groupBy('scoreanalysics.wave_id', 'waves.name')
                    ->get();

                $res = [];

                foreach ($trend as $value) {
                    $indexLabel = $value->wave_score . " ";
                    $res[] = array(
                        "y" => $value->wave_score,
                        "indexLabel" => $indexLabel,
                        "label" => $value->waveName
                    );
                }
                $tredresult = $res;
                $difference = '-';

                // Check if there are at least two records
                // if (count($trend) >= 2) {
                //     // $lastScore = $trend[0]->wave_score; // Last record score
                //     // $secondLastScore = $trend[1]->wave_score; // Second last record score
                //     // $difference = $secondLastScore -  $lastScore; // Calculate the difference
                //     // Get the last element
                //     $lastIndex = count($trend) - 1;  // Index of the last record
                //     $secondLastIndex = $lastIndex - 1;  // Index of the second-to-last record

                //     $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                //     $secondLastScore = $trend[$secondLastIndex]->wave_score;  // Second last record score

                //     $difference = $lastScore - $secondLastScore;  // Calculate the difference
                // }

                $difference = '-';  // Default if less than 2 waves
                // $cagr = '-';  // Default CAGR if less than 3 waves

                // Check if there are at least two records
                if (count($trend) == 2) {
                    // Calculate difference between the last two wave scores
                    $lastIndex = count($trend) - 1;  // Index of the last record
                    $secondLastIndex = $lastIndex - 1;  // Index of the second-to-last record

                    $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                    $secondLastScore = $trend[$secondLastIndex]->wave_score;  // Second last record score

                    $difference = $lastScore - $secondLastScore;  // Calculate the difference
                } elseif (count($trend) > 2) {
                    // Calculate CAGR when there are more than 2 waves
                    $lastIndex = count($trend) - 1;  // Index of the last record
                    $firstScore = $trend[0]->wave_score;  // First record score
                    $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                    $n = count($trend);  // Number of periods (waves)

                    // CAGR formula
                    $difference = round((pow($lastScore / $firstScore, 1 / ($n - 1)) - 1) * 100);  // CAGR in percentage
                }
                // echo "<pre>";
                // var_dump($tredresult);
                // echo "</pre>";
                //trend end

                //performance of branches start
                // $performanceOfBranches = branchCalculations::where('format_id',  $format_id)
                //     ->get(['overAllScore', 'shop_id']);
                $performanceOfBranches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->where('branch_calculations.format_id', $format_id)
                    ->where('branch_calculations.wave_id', '<=', $ytd)
                    ->where('assignshops.status', 'submit to client')
                    ->select('branch_calculations.overAllScore', 'branch_calculations.shop_id')
                    ->get();
                // dd($performanceOfBranches);
                $criterias = Criteria::where('format_id', $format_id)->get();
                // Initialize counters
                $counts = [];
                $totalBranches = count($performanceOfBranches);

                // Initialize counts for each criteria
                foreach ($criterias as $criteria) {
                    $counts[$criteria->id] = 0;
                }

                // Iterate through branch performance data
                foreach ($performanceOfBranches as $branch) {
                    $achScore = floatval($branch->overAllScore);

                    // Track if a branch has been classified by any criteria
                    $classified = false;

                    // Check which criteria the branch falls into
                    foreach ($criterias as $criteria) {
                        switch ($criteria->operator) {
                            case ">":
                                if ($achScore > $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case ">=":
                                if ($achScore >= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<":
                                if ($achScore < $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<=":
                                if ($achScore <= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "b/w":
                                if ($achScore > $criteria->range1 && $achScore < $criteria->range2) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "==":
                                if ($achScore == $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                        }

                        // Break out of the loop if classified
                        if ($classified) {
                            break;
                        }
                    }
                }

                // Prepare data for the view
                $data = [];
                foreach ($criterias as $criteria) {
                    $percentage = ($totalBranches > 0) ? ($counts[$criteria->id] / $totalBranches) * 100 : 0;
                    $data[] = [
                        'label' => $criteria->label,
                        'color' => $criteria->color,
                        'percentage' => round($percentage, 2),
                    ];
                }

                // Output results for debugging purposes
                // $totalPercentage = array_sum(array_column($data, 'percentage'));
                // echo "Total Percentage: " . round($totalPercentage, 2) . "%<br>";

                // foreach ($data as $result) {
                //     echo "Label: " . $result['label'] . "<br>";
                //     echo "Color: " . $result['color'] . "<br>";
                //     echo "Percentage: " . $result['percentage'] . "%<br><br>";
                // }

                //peroformance of branches end 
                //region start
                $regionchart = DB::table('region_calculations')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)

                    ->where('assignshops.status', 'submit to client')
                    ->groupBy('region_calculations.region_id', 'region_calculations.regionName')
                    ->select(
                        DB::raw('ROUND(SUM(region_calculations.achived) / SUM(region_calculations.applicable) * 100) as overAllscore'),
                        'region_calculations.regionName as regionName'
                    )
                    ->get();
                $regionChartData = [];
                foreach ($regionchart as $region) {
                    $regionChartData[] = [
                        'regionName' => $region->regionName,
                        'overAllscore' => $region->overAllscore,
                    ];
                }
                //region end

                //strenght and weeknes start


                $strengthRange = $criterias->first();
                $weaknessRange = $criterias->last();

                $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', '<=', $ytd)
                    ->where('assignshops.status', 'submit to client')
                    ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')
                    ->get();
                // echo  $strengthRange->range1;
                // echo  $weaknessRange->range1;
                $strengths = [];
                $weaknesses = [];

                foreach ($strenghtAndWeekness  as $result) {
                    if ($result->score >= $strengthRange->range1) { // Assuming strength criteria uses range1
                        $strengths[] = $result;
                    } elseif ($result->score <= $weaknessRange->range1) { // Assuming weakness criteria uses range2
                        $weaknesses[] = $result;
                    }
                }
                // dd($strengths);
                //starenght and weeknes end
                //top and bootom branch
                // Fetch the data with Eloquent
                $branches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->select('branch_calculations.branchName as branchname', 'branch_calculations.overAllScore as score')
                    ->where('branch_calculations.format_id',  $format_id)
                    ->where('branch_calculations.wave_id', '<=', $ytd)
                    ->where('assignshops.status', 'submit to client')
                    ->orderBy('branch_calculations.overAllScore', 'desc')
                    ->get();
                $topBranches = [];
                $bottomBranches = [];

                // Get top 3 branches based on scores
                $topBranches = $branches->take(3);

                // Get bottom 3 branches based on scores
                $bottomBranches = $branches->reverse()->take(3)->reverse();
                //end top and bootom branch
            } elseif (!empty($wave_id1) && empty($wave_id)) {

                $counttotal = assignshops::where('format_id', $format_id)->where('wave_id',  $wave_id1)
                    ->where('status', 'submit to client')
                    ->count();
                $sections = DB::table('scoreanalysics')
                    ->select(
                        'section_id as sectionid',
                        'section_name as sectionname',
                        DB::raw('SUM(achieved) / SUM(applicable) * 100 AS sectionoverall'),
                        DB::raw('SUM(achieved) as achived'),
                        DB::raw('SUM(applicable) as applicable')
                    )
                    ->where('wave_id',  $wave_id1)
                    ->where('format_id', '=', $format_id)
                    ->groupBy('section_id', 'section_name')
                    ->get();
                $res1 = [];
                $res2 = [];
                $ress = [];

                foreach ($sections as $section) {
                    $results = DB::table('scoreanalysics')
                        ->select(
                            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) AS overallscore'),
                            'question_id',
                            'question_name',
                            DB::raw('SUM(achieved) as achived'),
                            DB::raw('SUM(applicable) as applicable')

                        )
                        ->where('section_id', $section->sectionid)
                        ->groupBy('question_id', 'question_name')
                        ->get();

                    // Process question results for each section
                    $res3 = [];
                    $background_colors = ['#3162D4']; // You can add more colors if needed

                    foreach ($results as $ques) {
                        if (empty($ques->achived)) {
                            continue;
                        }

                        $rand_background = $background_colors[array_rand($background_colors)];
                        $achappi = 'Achieved: ' . $ques->achived . ' - Applicable: ' . $ques->applicable;

                        $res3[] = [
                            'name' => $ques->question_name . ' : ' . $achappi,
                            'tscore' => round($ques->overallscore),
                            'score2' => $ques->achived,
                            'score3' => $ques->applicable
                        ];
                    }

                    // Format section-level result
                    $res1[] = [
                        'name' => $section->sectionname,
                        'y' => round($section->sectionoverall),
                        'drilldown' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'score2' => $section->achived,
                        'score3' => $section->applicable
                    ];
                    // dd( $res);
                    $ress[] = [
                        'name' => $section->sectionname,
                        'id' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'data' => $results->map(function ($result) {
                            return [
                                $result->question_name . ' : Achieved: ' . $result->achived . ' - Applicable: ' . $result->applicable,
                                round($result->overallscore),
                                $result->achived,
                                $result->applicable
                            ];
                        })->toArray()
                    ];
                    // dd($ress);
                }
                $mdata = [
                    'res' => $res1,
                    'ress' => $ress
                ];
                // dd($mdata);
                // Output the data as JSON
                //end section
                // echo 2;
                // exit();
                //overall start
                $overallScore = DB::table('scoreanalysics')
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', $wave_id1)
                    ->where('assignshops.status', "submit to client")
                    ->select(DB::raw('ROUND((SUM(achieved) / SUM(applicable)) * 100) as overALLSore'))
                    ->value('overALLSore');
                //overall end
                //trend start
                $trend = DB::table('scoreanalysics')
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')
                    ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', '<=', $wave_id1)
                    ->where('assignshops.status', "submit to client")
                    ->groupBy('scoreanalysics.wave_id', 'waves.name')
                    ->get();
                $res = [];

                foreach ($trend as $value) {
                    $indexLabel = $value->wave_score . " ";
                    $res[] = array(
                        "y" => $value->wave_score,
                        "indexLabel" => $indexLabel,
                        "label" => $value->waveName
                    );
                }

                $tredresult = $res;
                $difference = '-';

                // Check if there are at least two records
                if (count($trend) >= 2) {
                    // $lastScore = $trend[0]->wave_score; // Last record score
                    // $secondLastScore = $trend[1]->wave_score; // Second last record score
                    // $difference = $secondLastScore -  $lastScore; // Calculate the difference
                    $lastIndex = count($trend) - 1;  // Index of the last record
                    $secondLastIndex = $lastIndex - 1;  // Index of the second-to-last record

                    $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                    $secondLastScore = $trend[$secondLastIndex]->wave_score;  // Second last record score

                    $difference = $lastScore - $secondLastScore;
                }
                // echo "<pre>";
                // print_r($tredresult);
                // echo "</pre>";
                //end trend

                //performance of branches start
                // $performanceOfBranches = branchCalculations::where('format_id', $format_id)->where('wave_id', $wave_id1)
                // ->get(['overAllScore', 'shop_id']);
                $performanceOfBranches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->where('branch_calculations.format_id', $format_id)
                    ->where('branch_calculations.wave_id', $wave_id1)
                    ->where('assignshops.status', 'submit to client')
                    ->select('branch_calculations.overAllScore', 'branch_calculations.shop_id')
                    ->get();

                $criterias = Criteria::where('format_id', $format_id)->get();
                // Initialize counters
                $counts = [];
                $totalBranches = count($performanceOfBranches);

                // Initialize counts for each criteria
                foreach ($criterias as $criteria) {
                    $counts[$criteria->id] = 0;
                }

                // Iterate through branch performance data
                foreach ($performanceOfBranches as $branch) {
                    $achScore = floatval($branch->overAllScore);

                    // Track if a branch has been classified by any criteria
                    $classified = false;

                    // Check which criteria the branch falls into
                    foreach ($criterias as $criteria) {
                        switch ($criteria->operator) {
                            case ">":
                                if ($achScore > $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case ">=":
                                if ($achScore >= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<":
                                if ($achScore < $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<=":
                                if ($achScore <= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "b/w":
                                if ($achScore > $criteria->range1 && $achScore < $criteria->range2) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "==":
                                if ($achScore == $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                        }

                        // Break out of the loop if classified
                        if ($classified) {
                            break;
                        }
                    }
                }

                // Prepare data for the view
                $data = [];
                foreach ($criterias as $criteria) {
                    $percentage = ($totalBranches > 0) ? ($counts[$criteria->id] / $totalBranches) * 100 : 0;
                    $data[] = [
                        'label' => $criteria->label,
                        'color' => $criteria->color,
                        'percentage' => round($percentage, 2),
                    ];
                }

                // Output results for debugging purposes
                // $totalPercentage = array_sum(array_column($data, 'percentage'));
                // echo "Total Percentage: " . round($totalPercentage, 2) . "%<br>";

                // foreach ($data as $result) {
                //     echo "Label: " . $result['label'] . "<br>";
                //     echo "Color: " . $result['color'] . "<br>";
                //     echo "Percentage: " . $result['percentage'] . "%<br><br>";
                // }

                //peroformance of branches end 
                //region start
                $regionchart = DB::table('region_calculations')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('assignshops.status', 'submit to client')
                    ->where('region_calculations.wave_id', $wave_id1)
                    ->groupBy('region_calculations.region_id', 'region_calculations.regionName')
                    ->select(
                        DB::raw('ROUND(SUM(region_calculations.achived) / SUM(region_calculations.applicable) * 100) as overAllscore'),
                        'region_calculations.regionName as regionName'
                    )
                    ->get();
                $regionChartData = [];
                foreach ($regionchart as $region) {
                    $regionChartData[] = [
                        'regionName' => $region->regionName,
                        'overAllscore' => $region->overAllscore,
                    ];
                }
                //region end

                //strenght and weeknes start


                $strengthRange = $criterias->first();
                $weaknessRange = $criterias->last();

                $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', '=', $wave_id1)
                    ->where('assignshops.status', 'submit to client')
                    ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')
                    ->get();
                // echo  $strengthRange->range1;
                // echo  $weaknessRange->range1;
                $strengths = [];
                $weaknesses = [];

                foreach ($strenghtAndWeekness  as $result) {
                    if ($result->score >= $strengthRange->range1) { // Assuming strength criteria uses range1
                        $strengths[] = $result;
                    } elseif ($result->score <= $weaknessRange->range1) { // Assuming weakness criteria uses range2
                        $weaknesses[] = $result;
                    }
                }
                // dd($strengths);
                //starenght and weeknes end

                //top and bootom branch
                // Fetch the data with Eloquent
                $branches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->select('branch_calculations.branchName as branchname', 'branch_calculations.overAllScore as score')
                    ->where('branch_calculations.format_id',  $format_id)
                    ->where('branch_calculations.wave_id', '=', $wave_id1)
                    ->where('assignshops.status', 'submit to client')
                    ->orderBy('branch_calculations.overAllScore', 'desc')
                    ->get();
                $topBranches = [];
                $bottomBranchess = [];

                // Get top 3 branches based on scores
                $topBranches = $branches->take(3);

                // Get bottom 3 branches based on scores
                $bottomBranchess = $branches->reverse()->take(3)->reverse();

                $bottomBranches = $bottomBranchess->reverse();
                // dd($bottomBranches);
                //end top and bootom branch
            }
        } else {
            $locationName = DB::table('hierarchies')
                ->join('locations', 'hierarchies.LID', '=', 'locations.id')
                ->where('hierarchies.id', $regionID)
                ->select('locations.locationname as locationname')
                ->first();
            $locationame  = $locationName->locationname;


            session::put('title', $locationame);
            if (!empty($ytd) && $wave_id == 0) {
                //overALL start
                // echo 1;
                // exit();
                // $overallScore = regionCalculations::where('format_id', $format_id)
                //     ->where('region_id',  $regionID)
                //     ->selectRaw('ROUND((SUM(achived)/SUM(applicable))*100) AS overallscore')
                //     ->value('overallscore');
                $counttotal = assignshops::where('format_id', $format_id)->where('wave_id',  $wave_id1)
                    ->where('status', 'submit to client')
                    ->count();

                $allHierarchyLevels = [];

                $recursiveCTE = "WITH RECURSIVE NodeHierarchy AS ( SELECT id, parentID, levelID, LID, id AS RootID
          FROM hierarchies WHERE id IN ($regionID) UNION ALL SELECT h.id, h.parentID, h.levelID, h.LID, nh.RootID
           FROM hierarchies h INNER JOIN NodeHierarchy nh ON h.parentID = nh.id )
            SELECT nh.id, nh.parentID, nh.LID, nh.RootID, loc.locationname, ass.id AS assignshop_id 
            FROM NodeHierarchy nh LEFT JOIN locations loc ON nh.LID = loc.id 
            LEFT JOIN assignshops ass ON nh.id = ass.location_id AND ass.wave_id <= $ytd
             WHERE nh.id NOT IN ( SELECT DISTINCT parentID FROM hierarchies WHERE parentID IS NOT NULL ) 
             AND ass.status='submit to client' ORDER BY nh.levelID";
                // Execute the raw SQL query with bindings

                // Execute the raw SQL query
                $results = DB::select($recursiveCTE);

                // Count the number of results
                $counttotal = count($results);
                //section
                $sections = DB::table('scoreanalysics')
                    ->select(
                        'section_id as sectionid',
                        'section_name as sectionname',
                        DB::raw('SUM(achieved) / SUM(applicable) * 100 AS sectionoverall'),
                        DB::raw('SUM(achieved) as achived'),
                        DB::raw('SUM(applicable) as applicable')
                    )
                    ->where('wave_id',  $ytd)
                    ->where('format_id', '=', $format_id)
                    ->groupBy('section_id', 'section_name')
                    ->get();
                $res1 = [];
                $res2 = [];
                $ress = [];

                foreach ($sections as $section) {
                    $results = DB::table('scoreanalysics')
                        ->select(
                            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) AS overallscore'),
                            'question_id',
                            'question_name',
                            DB::raw('SUM(achieved) as achived'),
                            DB::raw('SUM(applicable) as applicable')

                        )
                        ->where('section_id', $section->sectionid)
                        ->groupBy('question_id', 'question_name')
                        ->get();

                    // Process question results for each section
                    $res3 = [];
                    $background_colors = ['#3162D4']; // You can add more colors if needed

                    foreach ($results as $ques) {
                        if (empty($ques->achived)) {
                            continue;
                        }

                        $rand_background = $background_colors[array_rand($background_colors)];
                        $achappi = 'Achieved: ' . $ques->achived . ' - Applicable: ' . $ques->applicable;

                        $res3[] = [
                            'name' => $ques->question_name . ' : ' . $achappi,
                            'tscore' => round($ques->overallscore),
                            'score2' => $ques->achived,
                            'score3' => $ques->applicable
                        ];
                    }

                    // Format section-level result
                    $res1[] = [
                        'name' => $section->sectionname,
                        'y' => round($section->sectionoverall),
                        'drilldown' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'score2' => $section->achived,
                        'score3' => $section->applicable
                    ];
                    // dd( $res);
                    $ress[] = [
                        'name' => $section->sectionname,
                        'id' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'data' => $results->map(function ($result) {
                            return [
                                $result->question_name . ' : Achieved: ' . $result->achived . ' - Applicable: ' . $result->applicable,
                                round($result->overallscore),
                                $result->achived,
                                $result->applicable
                            ];
                        })->toArray()
                    ];
                    // dd($ress);
                }
                $mdata = [
                    'res' => $res1,
                    'ress' => $ress
                ];
                // dd($mdata);
                // Output the data as JSON
                //end section
                $overallScore = DB::table('region_calculations')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('region_calculations.region_id', $regionID)
                    ->where('assignshops.status', 'submit to client')
                    ->select(DB::raw('ROUND((SUM(region_calculations.achived) / SUM(region_calculations.applicable)) * 100)
                     AS overallscore'))->value('overallscore'); // ->limit(1)
                $trend  = DB::table('region_calculations')
                    ->selectRaw('round((SUM(achived) / SUM(applicable) * 100)) AS wave_score, waves.id AS wave_id, waves.name AS waveName')
                    ->join('waves', 'region_calculations.wave_id', '=', 'waves.id')
                    ->join('hierarchies', 'region_calculations.region_id', '=', 'hierarchies.parentID')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('region_calculations.region_id',  $regionID)
                    ->where('assignshops.status', 'submit to client')
                    ->where('region_calculations.wave_id', '<=', $ytd)
                    ->groupBy('region_calculations.wave_id', 'waves.id', 'waves.name')
                    ->get();

                $res = [];

                foreach ($trend as $value) {
                    $indexLabel = $value->wave_score . " ";
                    $res[] = array(
                        "y" => $value->wave_score,
                        "indexLabel" => $indexLabel,
                        "label" => $value->waveName
                    );
                }
                $tredresult = $res;
                $difference = '-';

                // // Check if there are at least two records
                // if (count($trend) >= 2) {
                //     // $lastScore = $trend[0]->wave_score; // Last record score
                //     // $secondLastScore = $trend[1]->wave_score; // Second last record score
                //     // $difference = $secondLastScore -  $lastScore; // Calculate the difference
                //     $lastIndex = count($trend) - 1;  // Index of the last record
                //     $secondLastIndex = $lastIndex - 1;  // Index of the second-to-last record

                //     $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                //     $secondLastScore = $trend[$secondLastIndex]->wave_score;  // Second last record score

                //     $difference = $lastScore - $secondLastScore;
                // }
                if (count($trend) == 2) {
                    // Calculate difference between the last two wave scores
                    $lastIndex = count($trend) - 1;  // Index of the last record
                    $secondLastIndex = $lastIndex - 1;  // Index of the second-to-last record

                    $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                    $secondLastScore = $trend[$secondLastIndex]->wave_score;  // Second last record score

                    $difference = $lastScore - $secondLastScore;  // Calculate the difference
                } elseif (count($trend) > 2) {
                    // Calculate CAGR when there are more than 2 waves
                    $lastIndex = count($trend) - 1;  // Index of the last record
                    $firstScore = $trend[0]->wave_score;  // First record score
                    $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                    $n = count($trend);  // Number of periods (waves)

                    // CAGR formula
                    $difference = round((pow($lastScore / $firstScore, 1 / ($n - 1)) - 1) * 100);  // CAGR in percentage
                }
                //performance of branches start
                $performanceOfBranches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->where('branch_calculations.format_id', $format_id)
                    ->where('branch_calculations.region_id', $regionID)
                    ->where('assignshops.status', 'submit to client')
                    ->select('branch_calculations.overAllScore', 'branch_calculations.shop_id')
                    ->get();

                $criterias = Criteria::where('format_id', $format_id)->get();
                // Initialize counters
                $counts = [];
                $totalBranches = count($performanceOfBranches);

                // Initialize counts for each criteria
                foreach ($criterias as $criteria) {
                    $counts[$criteria->id] = 0;
                }

                // Iterate through branch performance data
                foreach ($performanceOfBranches as $branch) {
                    $achScore = floatval($branch->overAllScore);

                    // Track if a branch has been classified by any criteria
                    $classified = false;

                    // Check which criteria the branch falls into
                    foreach ($criterias as $criteria) {
                        switch ($criteria->operator) {
                            case ">":
                                if ($achScore > $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case ">=":
                                if ($achScore >= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<":
                                if ($achScore < $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<=":
                                if ($achScore <= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "b/w":
                                if ($achScore > $criteria->range1 && $achScore < $criteria->range2) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "==":
                                if ($achScore == $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                        }

                        // Break out of the loop if classified
                        if ($classified) {
                            break;
                        }
                    }
                }

                // Prepare data for the view
                $data = [];
                foreach ($criterias as $criteria) {
                    $percentage = ($totalBranches > 0) ? ($counts[$criteria->id] / $totalBranches) * 100 : 0;
                    $data[] = [
                        'label' => $criteria->label,
                        'color' => $criteria->color,
                        'percentage' => round($percentage, 2),
                    ];
                }

                // Output results for debugging purposes
                // $totalPercentage = array_sum(array_column($data, 'percentage'));
                // echo "Total Percentage: " . round($totalPercentage, 2) . "%<br>";

                // foreach ($data as $result) {
                //     echo "Label: " . $result['label'] . "<br>";
                //     echo "Color: " . $result['color'] . "<br>";
                //     echo "Percentage: " . $result['percentage'] . "%<br><br>";
                // }

                //peroformance of branches end 
                //region start
                $regionchart = DB::table('region_calculations')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('assignshops.status', 'submit to client')
                    ->groupBy('region_calculations.region_id', 'region_calculations.regionName')
                    ->select(
                        DB::raw('ROUND(SUM(region_calculations.achived) / SUM(region_calculations.applicable) * 100) as overAllscore'),
                        'region_calculations.regionName as regionName'
                    )
                    ->get();
                $regionChartData = [];
                foreach ($regionchart as $region) {
                    $regionChartData[] = [
                        'regionName' => $region->regionName,
                        'overAllscore' => $region->overAllscore,
                    ];
                }
                //region end
                //strenght and weeknes start


                $strengthRange = $criterias->first();
                $weaknessRange = $criterias->last();

                $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->join('branch_calculations', 'assignshops.id', '=', 'branch_calculations.shop_id')
                    ->where('branch_calculations.region_id', $regionID)
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', '<=', $ytd)
                    ->where('assignshops.status', 'submit to client')
                    ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')
                    ->get();
                // echo  $strengthRange->range1;
                // echo  $weaknessRange->range1;
                $strengths = [];
                $weaknesses = [];

                foreach ($strenghtAndWeekness  as $result) {
                    if ($result->score >= $strengthRange->range1) { // Assuming strength criteria uses range1
                        $strengths[] = $result;
                    } elseif ($result->score <= $weaknessRange->range1) { // Assuming weakness criteria uses range2
                        $weaknesses[] = $result;
                    }
                }
                // dd($strengths);
                //starenght and weeknes end
                $branches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->select('branch_calculations.branchName as branchname', 'branch_calculations.overAllScore as score')
                    ->where('branch_calculations.format_id',  $format_id)
                    ->where('branch_calculations.region_id', $regionID)
                    ->where('branch_calculations.wave_id', '<=', $ytd)
                    ->where('assignshops.status', 'submit to client')
                    ->orderBy('branch_calculations.overAllScore', 'desc')
                    ->get();
                $topBranches = [];
                $bottomBranchess = [];

                // Get top 3 branches based on scores
                $topBranches = $branches->take(3);

                // Get bottom 3 branches based on scores
                $bottomBranchess = $branches->reverse()->take(3)->reverse();

                $bottomBranches = $bottomBranchess->reverse();
                // dd($bottomBranches);
                //end top and bootom branch
            } elseif (!empty($wave_id1) && empty($wave_id)) {

                $allHierarchyLevels = [];

                $recursiveCTE = "WITH RECURSIVE NodeHierarchy AS ( SELECT id, parentID, levelID, LID, id AS RootID
          FROM hierarchies WHERE id IN ($regionID) UNION ALL SELECT h.id, h.parentID, h.levelID, h.LID, nh.RootID
           FROM hierarchies h INNER JOIN NodeHierarchy nh ON h.parentID = nh.id )
            SELECT nh.id, nh.parentID, nh.LID, nh.RootID, loc.locationname, ass.id AS assignshop_id 
            FROM NodeHierarchy nh LEFT JOIN locations loc ON nh.LID = loc.id 
            LEFT JOIN assignshops ass ON nh.id = ass.location_id AND ass.wave_id =  $wave_id1
             WHERE nh.id NOT IN ( SELECT DISTINCT parentID FROM hierarchies WHERE parentID IS NOT NULL ) 
             AND ass.status='submit to client' ORDER BY nh.levelID";
                // Execute the raw SQL query with bindings

                // Execute the raw SQL query
                $results = DB::select($recursiveCTE);

                // Count the number of results
                $counttotal = count($results);
                //section
                $sections = DB::table('scoreanalysics')
                    ->select(
                        'section_id as sectionid',
                        'section_name as sectionname',
                        DB::raw('SUM(achieved) / SUM(applicable) * 100 AS sectionoverall'),
                        DB::raw('SUM(achieved) as achived'),
                        DB::raw('SUM(applicable) as applicable')
                    )
                    ->where('wave_id',  $wave_id1)
                    ->where('format_id', '=', $format_id)
                    ->groupBy('section_id', 'section_name')
                    ->get();
                $res1 = [];
                $res2 = [];
                $ress = [];
                foreach ($sections as $section) {
                    $results = DB::table('scoreanalysics')
                        ->select(
                            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) AS overallscore'),
                            'question_id',
                            'question_name',
                            DB::raw('SUM(achieved) as achived'),
                            DB::raw('SUM(applicable) as applicable')

                        )
                        ->where('section_id', $section->sectionid)
                        ->groupBy('question_id', 'question_name')
                        ->get();

                    // Process question results for each section
                    $res3 = [];
                    $background_colors = ['#3162D4']; // You can add more colors if needed

                    foreach ($results as $ques) {
                        if (empty($ques->achived)) {
                            continue;
                        }

                        $rand_background = $background_colors[array_rand($background_colors)];
                        $achappi = 'Achieved: ' . $ques->achived . ' - Applicable: ' . $ques->applicable;

                        $res3[] = [
                            'name' => $ques->question_name . ' : ' . $achappi,
                            'tscore' => round($ques->overallscore),
                            'score2' => $ques->achived,
                            'score3' => $ques->applicable
                        ];
                    }

                    // Format section-level result
                    $res1[] = [
                        'name' => $section->sectionname,
                        'y' => round($section->sectionoverall),
                        'drilldown' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'score2' => $section->achived,
                        'score3' => $section->applicable
                    ];
                    // dd( $res);
                    $ress[] = [
                        'name' => $section->sectionname,
                        'id' => $section->sectionname,
                        'color' => '#ff9933', // or dynamically set if needed
                        'data' => $results->map(function ($result) {
                            return [
                                $result->question_name . ' : Achieved: ' . $result->achived . ' - Applicable: ' . $result->applicable,
                                round($result->overallscore),
                                $result->achived,
                                $result->applicable
                            ];
                        })->toArray()
                    ];
                    // dd($ress);
                }
                $mdata = [
                    'res' => $res1,
                    'ress' => $ress
                ];
                // dd($mdata);
                // Output the data as JSON
                //end section
                // echo 2;
                // exit();
                // $overallScore = regionCalculations::where('format_id', $format_id)
                //     ->where('wave_id', $wave_id1)
                //     ->where('regin_id',  $regionID)
                //     ->selectRaw('ROUND((SUM(achived)/SUM(applicable))*100) AS overallscore')
                //     ->value('overallscore');
                $overallScore = DB::table('region_calculations')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('region_calculations.wave_id', $wave_id1)
                    ->where('region_calculations.region_id', $regionID)
                    ->where('assignshops.status', 'submit to client')
                    ->select(DB::raw('ROUND((SUM(region_calculations.achived) / SUM(region_calculations.applicable)) * 100) AS overallscore'))->value('overallscore');; // ->limit(1)
                // ->first();
                $trend  = DB::table('region_calculations')
                    ->selectRaw('round((SUM(achived) / SUM(applicable) * 100)) AS wave_score, waves.id AS wave_id, waves.name AS waveName')
                    ->join('waves', 'region_calculations.wave_id', '=', 'waves.id')
                    ->join('hierarchies', 'region_calculations.region_id', '=', 'hierarchies.parentID')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('region_calculations.region_id',  $regionID)
                    ->where('assignshops.status', 'submit to client')
                    ->where('region_calculations.wave_id', '<=', $wave_id1)
                    ->groupBy('region_calculations.wave_id', 'waves.id', 'waves.name')
                    ->get();
                $res = [];

                foreach ($trend as $value) {
                    $indexLabel = $value->wave_score . " ";
                    $res[] = array(
                        "y" => $value->wave_score,
                        "indexLabel" => $indexLabel,
                        "label" => $value->waveName
                    );
                }
                $tredresult = $res;
                $difference = '-';

                // Check if there are at least two records
                if (count($trend) >= 2) {
                    // $lastScore = $trend[0]->wave_score; // Last record score
                    // $secondLastScore = $trend[1]->wave_score; // Second last record score
                    // $difference = $secondLastScore -  $lastScore; // Calculate the difference
                    $lastIndex = count($trend) - 1;  // Index of the last record
                    $secondLastIndex = $lastIndex - 1;  // Index of the second-to-last record

                    $lastScore = $trend[$lastIndex]->wave_score;  // Last record score
                    $secondLastScore = $trend[$secondLastIndex]->wave_score;  // Second last record score

                    $difference = $lastScore - $secondLastScore;
                }
                // end trend
                //performance of branches start
                $performanceOfBranches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->where('branch_calculations.format_id', $format_id)
                    ->where('branch_calculations.region_id', $regionID)
                    ->where('branch_calculations.wave_id', $wave_id1)
                    ->where('assignshops.status', 'submit to client')
                    ->select('branch_calculations.overAllScore', 'branch_calculations.shop_id')
                    ->get();
                $criterias = Criteria::where('format_id', $format_id)->get();
                // Initialize counters
                $counts = [];
                $totalBranches = count($performanceOfBranches);

                // Initialize counts for each criteria
                foreach ($criterias as $criteria) {
                    $counts[$criteria->id] = 0;
                }

                // Iterate through branch performance data
                foreach ($performanceOfBranches as $branch) {
                    $achScore = floatval($branch->overAllScore);

                    // Track if a branch has been classified by any criteria
                    $classified = false;

                    // Check which criteria the branch falls into
                    foreach ($criterias as $criteria) {
                        switch ($criteria->operator) {
                            case ">":
                                if ($achScore > $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case ">=":
                                if ($achScore >= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<":
                                if ($achScore < $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "<=":
                                if ($achScore <= $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "b/w":
                                if ($achScore > $criteria->range1 && $achScore < $criteria->range2) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                            case "==":
                                if ($achScore == $criteria->range1) {
                                    $counts[$criteria->id]++;
                                    $classified = true;
                                }
                                break;
                        }

                        // Break out of the loop if classified
                        if ($classified) {
                            break;
                        }
                    }
                }

                // Prepare data for the view
                $data = [];
                foreach ($criterias as $criteria) {
                    $percentage = ($totalBranches > 0) ? ($counts[$criteria->id] / $totalBranches) * 100 : 0;
                    $data[] = [
                        'label' => $criteria->label,
                        'color' => $criteria->color,
                        'percentage' => round($percentage, 2),
                    ];
                }

                // Output results for debugging purposes
                // $totalPercentage = array_sum(array_column($data, 'percentage'));
                // echo "Total Percentage: " . round($totalPercentage, 2) . "%<br>";

                // foreach ($data as $result) {
                //     echo "Label: " . $result['label'] . "<br>";
                //     echo "Color: " . $result['color'] . "<br>";
                //     echo "Percentage: " . $result['percentage'] . "%<br><br>";
                // }

                //peroformance of branches end 

                //region start
                $regionchart = DB::table('region_calculations')
                    ->join('assignshops', 'region_calculations.wave_id', '=', 'assignshops.wave_id')
                    ->where('region_calculations.format_id', $format_id)
                    ->where('assignshops.status', 'submit to client')
                    ->where('region_calculations.wave_id', $wave_id1)
                    ->groupBy('region_calculations.region_id', 'region_calculations.regionName')
                    ->select(
                        DB::raw('ROUND(SUM(region_calculations.achived) / SUM(region_calculations.applicable) * 100) as overAllscore'),
                        'region_calculations.regionName as regionName'
                    )
                    ->get();
                $regionChartData = [];
                foreach ($regionchart as $region) {
                    $regionChartData[] = [
                        'regionName' => $region->regionName,
                        'overAllscore' => $region->overAllscore,
                    ];
                }
                //region end
                //strenght and weeknes start


                $strengthRange = $criterias->first();
                $weaknessRange = $criterias->last();

                $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))
                    ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                    ->join('branch_calculations', 'assignshops.id', '=', 'branch_calculations.shop_id')
                    ->where('branch_calculations.region_id', $regionID)
                    ->where('scoreanalysics.format_id', $format_id)
                    ->where('scoreanalysics.wave_id', '=', $wave_id1)
                    ->where('assignshops.status', 'submit to client')
                    ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')
                    ->get();
                // echo  $strengthRange->range1;
                // echo  $weaknessRange->range1;
                $strengths = [];
                $weaknesses = [];

                foreach ($strenghtAndWeekness  as $result) {
                    if ($result->score >= $strengthRange->range1) { // Assuming strength criteria uses range1
                        $strengths[] = $result;
                    } elseif ($result->score <= $weaknessRange->range1) { // Assuming weakness criteria uses range2
                        $weaknesses[] = $result;
                    }
                }
                // dd($strengths);
                //starenght and weeknes end
                $branches = DB::table('branch_calculations')
                    ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')
                    ->select('branch_calculations.branchName as branchname', 'branch_calculations.overAllScore as score')
                    ->where('branch_calculations.format_id',  $format_id)
                    ->where('branch_calculations.region_id', $regionID)
                    ->where('branch_calculations.wave_id', '=',  $wave_id1)
                    ->where('assignshops.status', 'submit to client')
                    ->orderBy('branch_calculations.overAllScore', 'desc')
                    ->get();
                $topBranches = [];
                $bottomBranchess = [];

                // Get top 3 branches based on scores
                $topBranches = $branches->take(3);

                // Get bottom 3 branches based on scores
                $bottomBranchess = $branches->reverse()->take(3)->reverse();

                $bottomBranches = $bottomBranchess->reverse();
                // dd($bottomBranches);
                //end top and bootom branch
            }
        }
        return view('client.dashboard', [
            'overallScore' => $overallScore,
            'tredresult' => $tredresult,
            'data' => $data,
            'criteriaData' =>  $criterias, // Pass criteria data to the view
            'regionChartData' => $regionChartData, // Pass region data to the view
            'strengths' =>   $strengths,
            'weaknesses' => $weaknesses,
            'topBranches' => $topBranches,
            'bottomBranches' => $bottomBranches,
            'res1' => $res1,
            'ress' => $ress,
            'secondlevelname' => $secondlevelname,
            'strenghtcriteria' => $strenghtcriteria,
            'weaknesscriteria' =>  $weaknesscriteria,
            'difference' => $difference,
            'counttotal' => $counttotal,
            //    'mdata'=> $mdata,
            // Add other variables to pass to the view as needed
        ]);
    }
}
