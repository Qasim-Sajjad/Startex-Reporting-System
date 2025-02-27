<?php



namespace App\Http\Controllers;



use illuminate\Http\Request;

use App\Models\User;

use Auth;

use Illuminate\Support\Facades\Session;

use App\Models\Format;



use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\hierarchynames;

use App\Models\Hierarchylevels;

use App\Models\hierarchies;

use App\Models\locations;

use Illuminate\Support\Facades\DB;

use App\Models\assignprojects;

use App\Models\waves;

use App\Models\assignshops;
use App\Models\Frequency;

use App\Models\Process;
use App\Services\ClientDatabaseManager;
use App\Models\ScoreAnalysis;
use App\Models\QOption;
class DashboardController extends Controller

{

    public function dashboard()

    {
        // dd(122);
        // echo "sia";
        // exit();
    $role = session::get('user_role');
    // Check the role from the session and return the appropriate dashboard view
    if ($role == "Super Admin") {
    return view('superadmin.dashboard');
    } elseif ($role == "Client Admin") {
  
     $database_name = session::get('client_database');
     $connection = ClientDatabaseManager::setConnection($database_name);
     $frequencies = Frequency::get();
    $processes = Process::orderBy('created_at', 'desc')->get();
      $firstProcess = $processes->first();
    Session::put('wave_name', "YTD");
    Session::put('process_name', "$firstProcess->name");
    $processCount = $processes->count();
   $processwithscore = ScoreAnalysis::join('q_options', 'score_analysis.q_option_id', '=', 'q_options.id')
    ->join('formats', 'score_analysis.format_id', '=', 'formats.id')
    ->join('processes', 'formats.process_id', '=', 'processes.id')
    ->select('processes.id as id', 'processes.name as processname', DB::raw('SUM(q_options.score) as score'))
    ->groupBy('processes.id')
    ->get();
            
$processesWithScores2 = Process::join('formats', 'processes.id', '=', 'formats.process_id')
    ->join('sections', 'formats.id', '=', 'sections.format_id')
    ->join('questions', 'sections.id', '=', 'questions.section_id')
    ->join('q_options', 'questions.id', '=', 'q_options.question_id')
    ->select(
        'processes.id as id', 
        'processes.name as processname', 
        DB::raw('SUM(q_options.score) as totalscore')
    )
    ->groupBy('processes.id', 'processes.name')
    ->get();
      
      $mergedData = $processwithscore->map(function ($process) use ($processesWithScores2) {
    // Find corresponding entries for the process in the second collection
    $matchingProcesses = $processesWithScores2->where('id', $process->id);

    // If matching processes are found, calculate the total score and percentage
    if ($matchingProcesses->isNotEmpty()) {
        // Aggregate total score from the second collection (if more than one record exists for the process)
        $totalScore = $matchingProcesses->sum('totalscore');

        // Calculate percentage (score / totalScore) * 100
        $process->percentage = round(($process->score / $totalScore) * 100);
        $process->totalscore = $totalScore; // Add the aggregated total score

        // Ensure the process name from the second collection is preserved (if needed)
        $process->processname = $matchingProcesses->first()->processname;
    } else {
        $process->percentage = 0; // If no match, set percentage as 0
        $process->totalscore = 0; // Set total score to 0
        $process->processname = $process->processname; // Retain the name from the first collection
    }

    return $process;
});
      
      
//dd($mergedData);
  
        return view('admin.dashboard',[
        'processCount'=>$processCount,
          'mergedData'=>$mergedData
        ]);
    } else {
        // If role is not found or is not recognized, redirect to login with an error message
        return redirect('login')->with('error', 'Credential not available');
    }
    }

 
}
