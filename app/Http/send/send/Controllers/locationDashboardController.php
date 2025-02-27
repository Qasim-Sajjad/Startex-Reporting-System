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

use Barryvdh\DomPDF\Facade\PDF;

use Maatwebsite\Excel\Facades\Excel; // Import the Excel facade

use App\Exports\ReportExport;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;





class locationDashboardController extends Controller

{



    public function visitreport1(Request $request)

    {

        session::put('title', "Visit Report");
        $location_id = session::get('location');
        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        $format = DB::table('formats')

            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')

            ->where('formats.id',  $format_id)

            ->select('formats.*', 'hierarchylevels.*')

            ->get();
        // Retrieve all shops for the client with the given format_id and wave_id

        $shopDetailsList = DB::table('assignshops')

            ->where('format_id',  $format_id)

            ->where('wave_id', $wave_id1)

            ->where('status', "submit to client")

            ->get();



        $allHierarchyLevels = [];

        $recursiveCTE = "WITH RECURSIVE NodeHierarchy AS ( SELECT id, parentID, levelID, LID, id AS RootID
      FROM hierarchies WHERE id IN ($location_id) UNION ALL SELECT h.id, h.parentID, h.levelID, h.LID, nh.RootID
       FROM hierarchies h INNER JOIN NodeHierarchy nh ON h.parentID = nh.id )
        SELECT nh.id, nh.parentID, nh.LID, nh.RootID, loc.locationname, ass.id AS assignshop_id 
        FROM NodeHierarchy nh LEFT JOIN locations loc ON nh.LID = loc.id 
        LEFT JOIN assignshops ass ON nh.id = ass.location_id AND ass.wave_id = $wave_id1
         WHERE nh.id NOT IN ( SELECT DISTINCT parentID FROM hierarchies WHERE parentID IS NOT NULL ) 
         AND ass.status='submit to client' ORDER BY nh.levelID";

        // Execute the raw SQL query with bindings

        $result = DB::select($recursiveCTE);


        // dd($result);

        return view('hierarchylevel.visitRepport1', [

            'headerName' => $format,

            'reports' => $allHierarchyLevels,
            'report' => $result,

            // Add other variables to pass to the view as needed

        ]);
    }


    public function reportdashboard()

    {

        // dd($id);

        $id = session::get('shopId');



        $shopID = $id;

        // dd( $id);

        $shopDetails = DB::table('assignshops')

            ->where('id', $shopID)

            ->first();

        // dd($shopDetails);

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');



        session::put('title', "Dashboard");

        $criterias = Criteria::where('format_id', $format_id)->get();

        $strengthRange = $criterias->first();

        $weaknessRange = $criterias->last();



        $strengthRange = $criterias->first();

        $weaknessRange = $criterias->last();



        $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '=', $wave_id1)

            ->where('scoreanalysics.shop_id', '=', $shopID)

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





        //trend start

        $trend = DB::table('scoreanalysics')

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

            ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '<=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

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

        $values = array_reverse($res);

        $currentWaveScore = $values[0]['indexLabel'] ?? null;

        $previousWaveScore = $values[1]['indexLabel'] ?? null;





        $tredresult = $res;

        $completereport =     DB::table('scoreanalysics')

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

            ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.status', "submit to client")

            ->groupBy('scoreanalysics.wave_id', 'waves.name')

            ->get();

        $waves = DB::table('waves')

            ->where('format_id', $format_id)

            ->orderBy('id', 'desc')

            ->get();



        $previousWaveId = null;

        foreach ($waves as $index => $value) {

            $waveId = $value->id;

            if ($waveId == $wave_id1 && isset($waves[$index + 1])) {

                $previousWaveId = $waves[$index + 1]->id;

                break;
            }
        }

        // Fetch current sections with scores

        $curentsections = Scoreanalysics::selectRaw(

            'ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overallscore, scoreanalysics.section_name as sectionName'

        )

            ->join('assignshops', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->where('scoreanalysics.wave_id', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('scoreanalysics.section_id', 'scoreanalysics.section_name')

            ->get();



        // Fetch previous sections with scores

        $previoussections = Scoreanalysics::selectRaw(

            'ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overallscore, scoreanalysics.section_name as sectionName'

        )

            ->join('assignshops', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->where('scoreanalysics.wave_id', $previousWaveId)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('scoreanalysics.section_id', 'scoreanalysics.section_name')

            ->get();



        // Convert collections to associative arrays for easier merging

        $currentSectionsArray = $curentsections->keyBy('sectionName')->toArray();

        $previousSectionsArray = $previoussections->keyBy('sectionName')->toArray();



        // Combine results

        $combinedSections = [];



        foreach ($currentSectionsArray as $sectionName => $currentSection) {

            $combinedSections[$sectionName] = [

                'sectionName' => $sectionName,

                'current' => $currentSection['overallscore'],

                'previous' => $previousSectionsArray[$sectionName]['overallscore'] ?? null, // Use null if not present

            ];
        }

        $vistreports = branchCalculations::select(

            'assignshops.id as shop_id',

            'assignshops.wave_id as wave_id',

            'branch_calculations.overAllScore as overallscore',

            'waves.name as waveName'

        )

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('waves', 'assignshops.wave_id', '=', 'waves.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.wave_id', '<=', $wave_id1)

            ->get();

        // dd($vistreports);

        $shopCount = assignshops::where('location_id', $shopDetails->location_id)

            ->where('status', 'submit to client')

            ->where('wave_id', '<=', $wave_id1)

            ->where('format_id', '=', $format_id)

            ->count();

        $percentage = BranchCalculations::select(DB::raw('SUM(branch_calculations.overAllScore) AS percentage'))

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('branch_calculations.format_id')

            ->value('percentage'); // Retrieves the calculated percentage



        $total = $shopCount * 100;

        $ytd = round($percentage / $total * 100);

        $ids = assignshops::where('location_id', $shopDetails->location_id)

            ->where('status', 'submit to client')

            ->where('wave_id', '<=', $wave_id1)

            ->pluck('id'); // Retrieve only the 'id' column

        $totalShopCount = $ids->count();

        $halfShopCount = ($totalShopCount / 2);

        // dd($halfShopCount);

        $recuruing = [];

        foreach ($ids as $id) {

            $shopid = $id; // Since $id is already the ID, no need for alias

            $records = Scoreanalysics::where('shop_id',  $shopid)->get();

            foreach ($records  as $record) {

                if ($record->achieved == 0 && $record->applicable > 0) {

                    $recuruing[] = [

                        'section_name' => $record->section_name,

                        'question_name' => $record->question_name,

                        'question_id' => $record->question_id

                    ];
                }
            }
        }

        // Initialize an array to store the counts along with section and question names

        $questionIdData = [];



        // Process each record to count occurrences and store additional info

        foreach ($recuruing as $item) {

            $questionId = $item['question_id'];



            if (!isset($questionIdData[$questionId])) {

                $questionIdData[$questionId] = [

                    'section_name' => $item['section_name'],

                    'question_name' => $item['question_name'],

                    'count' => 0

                ];
            }



            // Increment the count

            $questionIdData[$questionId]['count']++;
        }





        // dd($questionIdData);

        // dd($recuruing);

        // $repotsscore = branchCalculations::where('format_id', $format_id)

        //     ->where('ave_id', '=', $wave_id1)

        //     ->orderBy('overAllScore', 'desc')  // Sort by score in descending order

        //     ->get();

        $repotsscore = branchCalculations::join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', $wave_id1)

            ->where('assignshops.status', 'submit to client')

            ->orderBy('branch_calculations.overAllScore', 'desc')

            ->get(['branch_calculations.*', 'assignshops.*']);  // Adjust the selected columns as needed



        // dd($repotsscore);

        // Initialize an array to hold formatted data with ranks

        $rankedData = [];

        $currentRank = 1;

        $lastScore = null;

        $lastRank = 1;

        $targetLocationRank = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore === $item->overAllScore) {

                $rank = $lastRank;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank;  // Assign the current rank

                $lastRank = $currentRank;  // Update last rank

            }



            $rankedData[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore = $item->overAllScore;

            $currentRank++;
        }



        // Debug output to see the array with ranks

        // dd($rankedData);

        // dd($targetLocationRank);





        $repotsscore2 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData2 = [];

        $currentRank2 = 1;

        $lastScore2 = null;

        $lastRank2 = 1;

        $targetLocationRank2 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore2 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore2 === $item->overAllScore) {

                $rank = $lastRank2;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank2;  // Assign the current rank

                $lastRank = $currentRank2;  // Update last rank

            }



            $rankedData2[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank2 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore2 = $item->overAllScore;

            $currentRank2++;
        }

        // Debug output to see the results

        // dd($repotsscore2);

        $regionId = branchCalculations::where('shop_id', $shopID)->pluck('region_id');

        // dd($regionId);

        // exit();

        $repotsscore3 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('branch_calculations.region_id', '=', $regionId)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData3 = [];

        $currentRank3 = 1;

        $lastScore3 = null;

        $lastRank3 = 1;

        $targetLocationRank3 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore3 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore3 === $item->overAllScore) {

                $rank = $lastRank3;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank3;  // Assign the current rank

                $lastRank = $currentRank3;  // Update last rank

            }



            $rankedData3[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank3 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore3 = $item->overAllScore;

            $currentRank3++;
        }

        $repotsscore4 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '=', $wave_id1)

            ->where('branch_calculations.region_id', '=', $regionId)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData4 = [];

        $currentRank4 = 1;

        $lastScore4 = null;

        $lastRank4 = 1;

        $targetLocationRank4 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore4 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore4 === $item->overAllScore) {

                $rank = $lastRank4;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank4;  // Assign the current rank

                $lastRank = $currentRank4;  // Update last rank

            }



            $rankedData4[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank4 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore4 = $item->overAllScore;

            $currentRank4++;
        }

        return view('branch.dashboard', [

            'tredresult' => $tredresult,

            'strengths' =>   $strengths,

            'weaknesses' => $weaknesses,

            'completereport' => $completereport,

            'curentsections' =>  $curentsections,

            'previoussections' =>  $previoussections,

            'combinedSections' => $combinedSections,

            'vistreports' => $vistreports,

            'shopCount' => $shopCount,

            'ytd' => $ytd,

            'currentWaveScore' =>  $currentWaveScore,

            'previousWaveScore' =>   $previousWaveScore,

            'questionIdData' => $questionIdData,

            'halfShopCount' => $halfShopCount,

            'targetLocationRank' => $targetLocationRank,

            'targetLocationRank2' => $targetLocationRank2,

            'targetLocationRank3' => $targetLocationRank3,

            'targetLocationRank4' => $targetLocationRank4,

        ]);
    }

    public function reportdashboard1($id)

    {

        // dd($id);

        $shopID = $id;

        $shopDetails = DB::table('assignshops')

            ->where('id', $shopID)

            ->first();

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        session::put('title', "Dashboard");

        $criterias = Criteria::where('format_id', $format_id)->get();

        $strengthRange = $criterias->first();

        $weaknessRange = $criterias->last();



        $strengthRange = $criterias->first();

        $weaknessRange = $criterias->last();



        $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '=', $wave_id1)

            ->where('scoreanalysics.shop_id', '=', $shopID)

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





        //trend start

        $trend = DB::table('scoreanalysics')

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

            ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '<=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

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

        $values = array_reverse($res);

        $currentWaveScore = $values[0]['indexLabel'] ?? null;

        $previousWaveScore = $values[1]['indexLabel'] ?? null;





        $tredresult = $res;

        $completereport =     DB::table('scoreanalysics')

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

            ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.status', "submit to client")

            ->groupBy('scoreanalysics.wave_id', 'waves.name')

            ->get();

        $waves = DB::table('waves')

            ->where('format_id', $format_id)

            ->orderBy('id', 'desc')

            ->get();



        $previousWaveId = null;

        foreach ($waves as $index => $value) {

            $waveId = $value->id;

            if ($waveId == $wave_id1 && isset($waves[$index + 1])) {

                $previousWaveId = $waves[$index + 1]->id;

                break;
            }
        }

        // Fetch current sections with scores

        $curentsections = Scoreanalysics::selectRaw(

            'ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overallscore, scoreanalysics.section_name as sectionName'

        )

            ->join('assignshops', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->where('scoreanalysics.wave_id', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('scoreanalysics.section_id', 'scoreanalysics.section_name')

            ->get();



        // Fetch previous sections with scores

        $previoussections = Scoreanalysics::selectRaw(

            'ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overallscore, scoreanalysics.section_name as sectionName'

        )

            ->join('assignshops', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->where('scoreanalysics.wave_id', $previousWaveId)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('scoreanalysics.section_id', 'scoreanalysics.section_name')

            ->get();



        // Convert collections to associative arrays for easier merging

        $currentSectionsArray = $curentsections->keyBy('sectionName')->toArray();

        $previousSectionsArray = $previoussections->keyBy('sectionName')->toArray();



        // Combine results

        $combinedSections = [];



        foreach ($currentSectionsArray as $sectionName => $currentSection) {

            $combinedSections[$sectionName] = [

                'sectionName' => $sectionName,

                'current' => $currentSection['overallscore'],

                'previous' => $previousSectionsArray[$sectionName]['overallscore'] ?? null, // Use null if not present

            ];
        }

        $vistreports = branchCalculations::select(

            'assignshops.id as shop_id',

            'assignshops.wave_id as wave_id',

            'branch_calculations.overAllScore as overallscore',

            'waves.name as waveName'

        )

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('waves', 'assignshops.wave_id', '=', 'waves.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.wave_id', '<=', $wave_id1)

            ->get();

        // dd($vistreports);

        $shopCount = assignshops::where('location_id', $shopDetails->location_id)

            ->where('status', 'submit to client')

            ->where('wave_id', '<=', $wave_id1)

            ->where('format_id', '=', $format_id)

            ->count();

        $percentage = BranchCalculations::select(DB::raw('SUM(branch_calculations.overAllScore) AS percentage'))

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('branch_calculations.format_id')

            ->value('percentage'); // Retrieves the calculated percentage



        $total = $shopCount * 100;

        $ytd = round($percentage / $total * 100);

        $ids = assignshops::where('location_id', $shopDetails->location_id)

            ->where('status', 'submit to client')

            ->where('wave_id', '<=', $wave_id1)

            ->pluck('id'); // Retrieve only the 'id' column

        $totalShopCount = $ids->count();

        $halfShopCount = ($totalShopCount / 2);

        // dd($halfShopCount);

        $recuruing = [];

        foreach ($ids as $id) {

            $shopid = $id; // Since $id is already the ID, no need for alias

            $records = Scoreanalysics::where('shop_id',  $shopid)->get();

            foreach ($records  as $record) {

                if ($record->achieved == 0 && $record->applicable > 0) {

                    $recuruing[] = [

                        'section_name' => $record->section_name,

                        'question_name' => $record->question_name,

                        'question_id' => $record->question_id

                    ];
                }
            }
        }

        // Initialize an array to store the counts along with section and question names

        $questionIdData = [];



        // Process each record to count occurrences and store additional info

        foreach ($recuruing as $item) {

            $questionId = $item['question_id'];



            if (!isset($questionIdData[$questionId])) {

                $questionIdData[$questionId] = [

                    'section_name' => $item['section_name'],

                    'question_name' => $item['question_name'],

                    'count' => 0

                ];
            }



            // Increment the count

            $questionIdData[$questionId]['count']++;
        }





        // dd($questionIdData);

        // dd($recuruing);

        // $repotsscore = branchCalculations::where('format_id', $format_id)

        //     ->where('ave_id', '=', $wave_id1)

        //     ->orderBy('overAllScore', 'desc')  // Sort by score in descending order

        //     ->get();

        $repotsscore = branchCalculations::join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', $wave_id1)

            ->where('assignshops.status', 'submit to client')

            ->orderBy('branch_calculations.overAllScore', 'desc')

            ->get(['branch_calculations.*', 'assignshops.*']);  // Adjust the selected columns as needed



        // dd($repotsscore);

        // Initialize an array to hold formatted data with ranks

        $rankedData = [];

        $currentRank = 1;

        $lastScore = null;

        $lastRank = 1;

        $targetLocationRank = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore === $item->overAllScore) {

                $rank = $lastRank;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank;  // Assign the current rank

                $lastRank = $currentRank;  // Update last rank

            }



            $rankedData[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore = $item->overAllScore;

            $currentRank++;
        }



        // Debug output to see the array with ranks

        // dd($rankedData);

        // dd($targetLocationRank);





        $repotsscore2 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData2 = [];

        $currentRank2 = 1;

        $lastScore2 = null;

        $lastRank2 = 1;

        $targetLocationRank2 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore2 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore2 === $item->overAllScore) {

                $rank = $lastRank2;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank2;  // Assign the current rank

                $lastRank = $currentRank2;  // Update last rank

            }



            $rankedData2[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank2 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore2 = $item->overAllScore;

            $currentRank2++;
        }

        // Debug output to see the results

        // dd($repotsscore2);

        $regionId = branchCalculations::where('shop_id', $shopID)->pluck('region_id');

        // dd($regionId);

        // exit();

        $repotsscore3 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('branch_calculations.region_id', '=', $regionId)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData3 = [];

        $currentRank3 = 1;

        $lastScore3 = null;

        $lastRank3 = 1;

        $targetLocationRank3 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore3 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore3 === $item->overAllScore) {

                $rank = $lastRank3;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank3;  // Assign the current rank

                $lastRank = $currentRank3;  // Update last rank

            }



            $rankedData3[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank3 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore3 = $item->overAllScore;

            $currentRank3++;
        }

        $repotsscore4 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '=', $wave_id1)

            ->where('branch_calculations.region_id', '=', $regionId)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData4 = [];

        $currentRank4 = 1;

        $lastScore4 = null;

        $lastRank4 = 1;

        $targetLocationRank4 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore4 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore4 === $item->overAllScore) {

                $rank = $lastRank4;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank4;  // Assign the current rank

                $lastRank = $currentRank4;  // Update last rank

            }



            $rankedData4[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank4 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore4 = $item->overAllScore;

            $currentRank4++;
        }

        return view('hierarchylevel.reportdashboard1', [

            'tredresult' => $tredresult,

            'strengths' =>   $strengths,

            'weaknesses' => $weaknesses,

            'completereport' => $completereport,

            'curentsections' =>  $curentsections,

            'previoussections' =>  $previoussections,

            'combinedSections' => $combinedSections,

            'vistreports' => $vistreports,

            'shopCount' => $shopCount,

            'ytd' => $ytd,

            'currentWaveScore' =>  $currentWaveScore,

            'previousWaveScore' =>   $previousWaveScore,

            'questionIdData' => $questionIdData,

            'halfShopCount' => $halfShopCount,

            'targetLocationRank' => $targetLocationRank,

            'targetLocationRank2' => $targetLocationRank2,

            'targetLocationRank3' => $targetLocationRank3,

            'targetLocationRank4' => $targetLocationRank4,

        ]);
    }

    public function viewReport1($id)

    {

        // echo $id;

        // exit();

        $shopID = $id;

        session::put('title', "Visit Report");

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        $shopDetails = DB::table('assignshops')

            ->where('id', $shopID)

            ->first();

        // dd($shopDetails);

        // $status1 = $shopDetails->status;

        $status1 =  $shopDetails->status;

        $formatID = $shopDetails->format_id;

        $waveID =  $shopDetails->wave_id;

        $initialHierarchyId = $shopDetails->location_id;



        $time =  $shopDetails->timeIn;

        $date = $shopDetails->date;

        // Recursive query to fetch hierarchical data

        $query = "

    WITH RECURSIVE HierarchyCTE AS (

        SELECT 

            h.id AS hierarchy_id,

            h.levelID AS level_id,

            hl.hierarchylavelname AS level_name,

            hl.level AS level,

            hl.HID AS hid,

            l.locationname AS location_name,

            h.parentID AS parent_id

        FROM 

            hierarchies h

        INNER JOIN 

            hierarchylevels hl ON h.levelID = hl.id

        INNER JOIN 

            locations l ON h.LID = l.id

        WHERE 

            h.id = :initialHierarchyId



        UNION ALL



        SELECT 

            h.id AS hierarchy_id,

            h.levelID AS level_id,

            hl.hierarchylavelname AS level_name,

            hl.level AS level,

            hl.HID AS hid,

            l.locationname AS location_name,

            h.parentID AS parent_id

        FROM 

            hierarchies h

        INNER JOIN 

            hierarchylevels hl ON h.levelID = hl.id

        INNER JOIN 

            locations l ON h.LID = l.id

        INNER JOIN 

            HierarchyCTE hc ON hc.parent_id = h.id

    )

    SELECT 

        hierarchy_id,

        level_id,

        level_name,

        level,

        hid,

        location_name

    FROM 

        HierarchyCTE;

";



        // Execute query and fetch results

        $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);



        $shopoverall = branchCalculations::where('shop_id', $shopID)->get();

        $criterias = Criteria::where('format_id', $format_id)->get();



        foreach ($shopoverall as $calculation) {

            $overAllScore = $calculation->overAllScore;
        }

        $conditionLabel = ""; // Initialize a variable to store the condition label



        foreach ($criterias as $criteria) {

            $operator = $criteria->operator;

            $range1 = $criteria->range1;

            $range2 = $criteria->range2;



            switch ($operator) {

                case ">":

                    if ($overAllScore > $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case ">=":

                    if ($overAllScore >= $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "<":

                    if ($overAllScore < $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "<=":

                    if ($overAllScore <= $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "b/w":

                    if ($overAllScore > $range1 && $overAllScore < $range2) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "==":

                    if ($overAllScore == $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                    // Add other cases here...

            }



            // If we found a matching condition, break the loop to avoid unnecessary iterations

            if ($conditionLabel != "") {

                break;
            }
        }

        $sectionScores = scoreanalysics::select(

            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),

            'section_name',

            'section_id'

        )

            ->where('shop_id', $shopID)

            ->groupBy('section_id', 'section_name')

            ->get();



        $overallresult = [];



        foreach ($sectionScores as $sectionScore) {

            $sectionID = $sectionScore->section_id;

            $sectionName = $sectionScore->section_name;



            $questions = scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.shop_id', $shopID)

                ->orderBy('questions.orderby')

                ->get(['scoreanalysics.*', 'questions.*']);



            $questionsData = [];



            foreach ($questions as $question) {

                $question_id = $question->question_id;

                $question_name = $question->question_name;

                $response = $question->response;

                $achieved = $question->achieved;

                $applicable = $question->applicable;

                $total = $question->total;



                $comments = comments::where('question_id', $question_id)

                    ->where('shop_id', $shopID)

                    ->get()

                    ->pluck('comments')

                    ->toArray();



                $questionsData[] = [

                    'question_id' => $question_id,

                    'question_name' => $question_name,

                    'response' => $response,

                    'achieved' => $achieved,

                    'applicable' => $applicable,

                    'total' => $total,

                    'comments' => $comments

                ];
            }



            $overallresult[] = [

                'section_id' => $sectionID,

                'section_name' => $sectionName,

                'section_score' => $sectionScore->sectionScore,

                'questions' => $questionsData

            ];
        }





        $visitAudioRecords = VisitAudioRecord::where('shop_id', $shopID)->get();

        // dd($visitAudioRecords);

        $embedvideo = ''; // Variable to store video URL

        $audioUrls = []; // Array to store audio URLs

        $receiptUrls = []; // Array to store receipt image URLs



        foreach ($visitAudioRecords as $key => $row) {

            $fileType = $row['type'];

            $fileName = $row['attachmentname'];

            $fileUrl = "public/uploads/$fileName";



            switch ($fileType) {

                case 'audio':

                    // Store audio URL

                    $audioUrls[] = $fileUrl;

                    break;

                case 'image':

                    // Store receipt image URL

                    $receiptUrls[] = $fileUrl;

                    break;

                case 'video':

                    // Store video URL for embedding

                    $embedvideo = $fileName;

                    break;

                default:

                    // Handle unknown file types or log an error

                    error_log("Unknown file type: $fileType");

                    break;
            }
        }

        // dd($audioUrls);

        // $lostopertunity = scoreanalysics::join('comments', 'scoreanalysics.question_id', '=', 'comments.question_id')

        //     ->where('scoreanalysics.shop_id', $shopID)

        //     // ->where('comments.shop_id', $shopID)

        //     ->whereColumn('scoreanalysics.achieved', '<', 'scoreanalysics.applicable')

        //     ->where('scoreanalysics.applicable', '>', 0)

        //     ->get();

        // $lostopertunity = DB::table('scoreanalysics')

        // ->select('section_name', 'question_name')  // Specify the columns you want to select

        // ->where('achieved', '<', 'applicable')

        // ->where('applicable', '>', 0)

        // ->where('shop_id', 2)

        // ->get();

        $lostopertunity = scoreanalysics::where('shop_id', $shopID)

            ->where('applicable', '>', 0)  // Ensure applicable score is greater than 0

            ->whereColumn('achieved', '<', 'applicable')  // Compare achieved and applicable scores

            ->get();

        // dd($lostopertunity);





        return view('branch.viewReport', [

            'hierarchyLevels' => $hierarchyLevels,

            'time' => $time,

            'date' =>  $date,

            'overAllScore' => $overAllScore,

            'conditionLabel' => $conditionLabel,

            'sectionScores' =>  $sectionScores,

            'embedvideo' => $embedvideo,

            'audioUrls' => $audioUrls,

            'receiptUrls' => $receiptUrls,

            'lostopertunity' => $lostopertunity,

            'overallresult' => $overallresult,

            'shopID' => $shopID,

        ]);
    }





    public function viewReport2($id)

    {

        // echo $id;

        // exit();

        $shopID = $id;

        session::put('title', "Visit Report");

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        $shopDetails = DB::table('assignshops')

            ->where('id', $shopID)

            ->first();

        // dd($shopDetails);

        // $status1 = $shopDetails->status;

        $status1 =  $shopDetails->status;

        $formatID = $shopDetails->format_id;

        $waveID =  $shopDetails->wave_id;

        $initialHierarchyId = $shopDetails->location_id;



        $time =  $shopDetails->timeIn;

        $date = $shopDetails->date;

        // Recursive query to fetch hierarchical data

        $query = "

    WITH RECURSIVE HierarchyCTE AS (

        SELECT 

            h.id AS hierarchy_id,

            h.levelID AS level_id,

            hl.hierarchylavelname AS level_name,

            hl.level AS level,

            hl.HID AS hid,

            l.locationname AS location_name,

            h.parentID AS parent_id

        FROM 

            hierarchies h

        INNER JOIN 

            hierarchylevels hl ON h.levelID = hl.id

        INNER JOIN 

            locations l ON h.LID = l.id

        WHERE 

            h.id = :initialHierarchyId



        UNION ALL



        SELECT 

            h.id AS hierarchy_id,

            h.levelID AS level_id,

            hl.hierarchylavelname AS level_name,

            hl.level AS level,

            hl.HID AS hid,

            l.locationname AS location_name,

            h.parentID AS parent_id

        FROM 

            hierarchies h

        INNER JOIN 

            hierarchylevels hl ON h.levelID = hl.id

        INNER JOIN 

            locations l ON h.LID = l.id

        INNER JOIN 

            HierarchyCTE hc ON hc.parent_id = h.id

    )

    SELECT 

        hierarchy_id,

        level_id,

        level_name,

        level,

        hid,

        location_name

    FROM 

        HierarchyCTE;

";



        // Execute query and fetch results

        $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);



        $shopoverall = branchCalculations::where('shop_id', $shopID)->get();

        $criterias = Criteria::where('format_id', $format_id)->get();



        foreach ($shopoverall as $calculation) {

            $overAllScore = $calculation->overAllScore;
        }

        $conditionLabel = ""; // Initialize a variable to store the condition label



        foreach ($criterias as $criteria) {

            $operator = $criteria->operator;

            $range1 = $criteria->range1;

            $range2 = $criteria->range2;



            switch ($operator) {

                case ">":

                    if ($overAllScore > $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case ">=":

                    if ($overAllScore >= $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "<":

                    if ($overAllScore < $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "<=":

                    if ($overAllScore <= $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "b/w":

                    if ($overAllScore > $range1 && $overAllScore < $range2) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "==":

                    if ($overAllScore == $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                    // Add other cases here...

            }



            // If we found a matching condition, break the loop to avoid unnecessary iterations

            if ($conditionLabel != "") {

                break;
            }
        }

        $sectionScores = scoreanalysics::select(

            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),

            'section_name',

            'section_id'

        )

            ->where('shop_id', $shopID)

            ->groupBy('section_id', 'section_name')

            ->get();



        $overallresult = [];



        foreach ($sectionScores as $sectionScore) {

            $sectionID = $sectionScore->section_id;

            $sectionName = $sectionScore->section_name;



            $questions = scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.shop_id', $shopID)

                ->orderBy('questions.orderby')

                ->get(['scoreanalysics.*', 'questions.*']);



            $questionsData = [];



            foreach ($questions as $question) {

                $question_id = $question->question_id;

                $question_name = $question->question_name;

                $response = $question->response;

                $achieved = $question->achieved;

                $applicable = $question->applicable;

                $total = $question->total;



                $comments = comments::where('question_id', $question_id)

                    ->where('shop_id', $shopID)

                    ->get()

                    ->pluck('comments')

                    ->toArray();



                $questionsData[] = [

                    'question_id' => $question_id,

                    'question_name' => $question_name,

                    'response' => $response,

                    'achieved' => $achieved,

                    'applicable' => $applicable,

                    'total' => $total,

                    'comments' => $comments

                ];
            }



            $overallresult[] = [

                'section_id' => $sectionID,

                'section_name' => $sectionName,

                'section_score' => $sectionScore->sectionScore,

                'questions' => $questionsData

            ];
        }





        $visitAudioRecords = VisitAudioRecord::where('shop_id', $shopID)->get();

        // dd($visitAudioRecords);

        $embedvideo = ''; // Variable to store video URL

        $audioUrls = []; // Array to store audio URLs

        $receiptUrls = []; // Array to store receipt image URLs



        foreach ($visitAudioRecords as $key => $row) {

            $fileType = $row['type'];

            $fileName = $row['attachmentname'];

            $fileUrl = "public/uploads/$fileName";



            switch ($fileType) {

                case 'audio':

                    // Store audio URL

                    $audioUrls[] = $fileUrl;

                    break;

                case 'image':

                    // Store receipt image URL

                    $receiptUrls[] = $fileUrl;

                    break;

                case 'video':

                    // Store video URL for embedding

                    $embedvideo = $fileName;

                    break;

                default:

                    // Handle unknown file types or log an error

                    error_log("Unknown file type: $fileType");

                    break;
            }
        }

        // dd($audioUrls);

        // $lostopertunity = scoreanalysics::join('comments', 'scoreanalysics.question_id', '=', 'comments.question_id')

        //     ->where('scoreanalysics.shop_id', $shopID)

        //     // ->where('comments.shop_id', $shopID)

        //     ->whereColumn('scoreanalysics.achieved', '<', 'scoreanalysics.applicable')

        //     ->where('scoreanalysics.applicable', '>', 0)

        //     ->get();

        // $lostopertunity = DB::table('scoreanalysics')

        // ->select('section_name', 'question_name')  // Specify the columns you want to select

        // ->where('achieved', '<', 'applicable')

        // ->where('applicable', '>', 0)

        // ->where('shop_id', 2)

        // ->get();

        $lostopertunity = scoreanalysics::where('shop_id', $shopID)

            ->where('applicable', '>', 0)  // Ensure applicable score is greater than 0

            ->whereColumn('achieved', '<', 'applicable')  // Compare achieved and applicable scores

            ->get();

        // dd($lostopertunity);





        return view('hierarchylevel.viewReport2', [

            'hierarchyLevels' => $hierarchyLevels,

            'time' => $time,

            'date' =>  $date,

            'overAllScore' => $overAllScore,

            'conditionLabel' => $conditionLabel,

            'sectionScores' =>  $sectionScores,

            'embedvideo' => $embedvideo,

            'audioUrls' => $audioUrls,

            'receiptUrls' => $receiptUrls,

            'lostopertunity' => $lostopertunity,

            'overallresult' => $overallresult,

            'shopID' => $shopID,

        ]);
    }
}
