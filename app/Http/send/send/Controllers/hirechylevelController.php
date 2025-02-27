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





class hirechylevelController extends Controller

{

    public function viewdashboard($locationName)

    {
        // dd("1");

        // echo 1;

        // exit();

        // $regionID = $request->input('regionID');


        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id'); // wave 

        $ytd = Session::get('YTD');

        $regionID = Session::get('location');



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

        session::put('title',$locationName);

        if (!empty($ytd) && $wave_id == 0) {
            //section
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

            //overALL start

            // echo 1;

            // exit();

            // $overallScore = regionCalculations::where('format_id', $format_id)

            //     ->where('region_id',  $regionID)

            //     ->selectRaw('ROUND((SUM(achived)/SUM(applicable))*100) AS overallscore')

            //     ->value('overallscore');

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

            // Check if there are at least two records
            if (count($trend) >= 2) {
                $lastScore = $trend[0]->wave_score; // Last record score
                $secondLastScore = $trend[1]->wave_score; // Second last record score
                $difference = $secondLastScore -  $lastScore; // Calculate the difference
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
                $lastScore = $trend[0]->wave_score; // Last record score
                $secondLastScore = $trend[1]->wave_score; // Second last record score
                $difference = $secondLastScore -  $lastScore; // Calculate the difference
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



        return view('hierarchylevel.viewdashboard', [

            'overallScore' => $overallScore,

            'tredresult' => $tredresult,

            'data' => $data,

            'criteriaData' =>  $criterias, // Pass criteria data to the view

            'regionChartData' => $regionChartData, // Pass region data to the view

            'strengths' =>   $strengths,

            'weaknesses' => $weaknesses,
            'secondlevelname' => $secondlevelname,
            'topBranches' => $topBranches,
            'strenghtcriteria' => $strenghtcriteria,
            'weaknesscriteria' =>  $weaknesscriteria,
            'difference' => $difference,
            'counttotal' => $counttotal,
            'bottomBranches' => $bottomBranches,
            'res1' => $res1,
            'ress' => $ress,

            // Add other variables to pass to the view as needed

        ]);
    }
}
