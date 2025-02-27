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



class sectiondashboardController extends Controller

{

    public function sectionDashboard(Request $request)

    {

        // echo 1;

        // exit();


        $sectionID = $request->input('sectionID');

        $view = $request->input('view', 'main');

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');
        $criterias = Criteria::where('format_id', $format_id)->get();
        $strengthRange = $criterias->first();
        $weaknessRange = $criterias->last();
        $strenghtcriteria = $strengthRange->range1;
        $weaknesscriteria = $weaknessRange->range1;
        // echo  $sectionID;
        $sectionName = Section::where('id',$sectionID)
        ->select('section_name as sectioname')
        ->first();
        // echo $view;
       $sectionanme = $sectionName->sectioname;
        session::put('title', $sectionanme);

        // echo $format_id;

        // echo '<br>';

        // echo $wave_id;

        // echo '<br>';

        // echo  $ytd;

        // echo '<br>';

        // echo  $wave_id1;

        // exit();

        if (!empty($ytd) && $wave_id == 0) {



            //overALL start
            $counttotal = assignshops::where('format_id', $format_id)
                ->where('status', 'submit to client')
                ->count();


            $overallScore = DB::table('scoreanalysics')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->select(DB::raw('ROUND((SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100)) as overallscore'))

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.format_id', $format_id)

                ->where('assignshops.status', "submit to client")

                ->where('assignshops.wave_id', '<=', $ytd)

                ->value('overALLSore');



            // Access the result

            $count = DB::table('assignshops')

                ->where('format_id', $format_id)

                ->where('wave_id', '<=', $ytd)

                ->where('status', "submit to client")

                ->count();

            // dd($count);

            // echo 1;

            // exit();

            $trend = DB::table('scoreanalysics')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

                ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

                ->where('scoreanalysics.format_id', $format_id)

                ->where('scoreanalysics.section_id', $sectionID)

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
            if (count($trend) >= 2) {
                $lastScore = $trend[0]->wave_score; // Last record score
                $secondLastScore = $trend[1]->wave_score; // Second last record score
                $difference = $secondLastScore -  $lastScore; // Calculate the difference

            }

            $section = DB::table('scoreanalysics')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->select(

                    'scoreanalysics.question_id',

                    'scoreanalysics.question_name',

                    DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS score')

                )

                ->where('scoreanalysics.format_id', $format_id)

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.wave_id', '<=', $ytd)

                ->where('assignshops.status', "submit to client")

                ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')

                ->get();





            $performanceOfBranches = DB::table('scoreanalysics')

                ->select(DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overAllScore'), 'scoreanalysics.shop_id')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.wave_id', '<=', $ytd)

                ->where('assignshops.status', 'submit to client')

                ->groupBy('scoreanalysics.shop_id')

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



            //top and bootom branch

            // Fetch the data with Eloquent

            $branches = DB::table('scoreanalysics')

                ->selectRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) AS overAllScore')

                ->addSelect('scoreanalysics.shop_id', 'branch_calculations.branchName AS branchname')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->join('branch_calculations', 'assignshops.id', '=', 'branch_calculations.shop_id')

                ->where('scoreanalysics.format_id', $format_id)

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.wave_id', '<=', $ytd)

                ->where('assignshops.status', 'submit to client')

                ->groupBy('scoreanalysics.shop_id', 'branch_calculations.branchName')

                ->havingRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) IS NOT NULL')

                ->havingRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) >= 0')

                ->orderBy('overAllScore', 'DESC')

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
            // echo 2;

            // exit();



            $overallScore = DB::table('scoreanalysics')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->select(DB::raw('ROUND((SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100)) as overallscore'))

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.format_id', $format_id)

                ->where('assignshops.status', "submit to client")

                ->where('assignshops.wave_id', '=', $wave_id1)

                ->value('overALLSore');

            $count = DB::table('assignshops')

                ->where('format_id', $format_id)

                ->where('wave_id', '=', $wave_id1)

                ->where('status', "submit to client")

                ->count();

            $trend = DB::table('scoreanalysics')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

                ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

                ->where('scoreanalysics.format_id', $format_id)

                ->where('scoreanalysics.section_id', $sectionID)

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
                $lastScore = $trend[0]->wave_score; // Last record score
                $secondLastScore = $trend[1]->wave_score; // Second last record score
                $difference = $secondLastScore -  $lastScore; // Calculate the difference

            }
            // dd($count);

            $section = DB::table('scoreanalysics')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->select(

                    'scoreanalysics.question_id',

                    'scoreanalysics.question_name',

                    DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS score')

                )

                ->where('scoreanalysics.format_id', $format_id)

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.wave_id', '=',  $wave_id1)

                ->where('assignshops.status', "submit to client")

                ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')

                ->get();

            $performanceOfBranches = DB::table('scoreanalysics')

                ->select(DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overAllScore'), 'scoreanalysics.shop_id')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.wave_id', '<=', $wave_id1)

                ->where('assignshops.status', 'submit to client')

                ->groupBy('scoreanalysics.shop_id')

                ->get();

            $criterias = Criteria::where('format_id', $format_id)->get();
            $strengthRange = $criterias->first();
            $weaknessRange = $criterias->last();
            $strenghtcriteria = $strengthRange->range1;
            $weaknesscriteria = $weaknessRange->range1;
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

            //top and bootom branch

            // Fetch the data with Eloquent

            $branches = DB::table('scoreanalysics')

                ->selectRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) AS overAllScore')

                ->addSelect('scoreanalysics.shop_id', 'branch_calculations.branchName AS branchname')

                ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                ->join('branch_calculations', 'assignshops.id', '=', 'branch_calculations.shop_id')

                ->where('scoreanalysics.format_id', $format_id)

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.wave_id', '=', $wave_id1)

                ->where('assignshops.status', 'submit to client')

                ->groupBy('scoreanalysics.shop_id', 'branch_calculations.branchName')

                ->havingRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) IS NOT NULL')

                ->havingRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) >= 0')

                ->orderBy('overAllScore', 'DESC')

                ->get();

            // dd($branches);

            $topBranches = [];

            $bottomBranches = [];



            // Get top 3 branches based on scores

            $topBranches = $branches->take(3);



            // Get bottom 3 branches based on scores

            $bottomBranches = $branches->reverse()->take(3)->reverse();
        }

        return view('client.sectionDashboard', [

            'overallScore' => $overallScore,

            'totalvisit' => $count,

            'tredresult' => $tredresult,

            'sections' => $section,

            'criteriaData' =>  $criterias,

            'data' => $data,

            'topBranches' => $topBranches,
            'difference' => $difference,
            'counttotal' => $counttotal,
            'strenghtcriteria' => $strenghtcriteria,
            'weaknesscriteria' =>  $weaknesscriteria,
            'bottomBranches' => $bottomBranches

        ]);
    }
}
