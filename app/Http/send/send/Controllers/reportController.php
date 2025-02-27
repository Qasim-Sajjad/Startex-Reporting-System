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

class reportController extends Controller
{


    public function viewreport(Request $request)
    {
        // dd($request->all());
        // Retrieve request parameters
        $formatID = $request->format_id;
        $locationID = $request->location_id;
        $shopID = $request->shop_id;
        $waveID = $request->wave_id;
        $status = $request->status;
        $initialHierarchyId = $locationID;
        $type = $request->type;
        $user = $request->user;
        // dd($shopID);

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

        // Fetch sections and related questions and options and keyword
        $sections = DB::table('sections')
            ->where('format_id', $formatID)
            ->get();

        foreach ($sections as $section) {
            $section->questions = DB::table('questions')
                ->where('section_id', $section->id)
                ->get();

            foreach ($section->questions as $question) {
                $question->options = DB::table('options')
                    ->where('question_id', $question->id)
                    ->get();

                // Fetch the saved score and comment for the question
                $question->saved_score = DB::table('scores')
                    ->where('shop_id', $shopID)
                    ->where('question_id', $question->id)
                    ->first();

                $question->saved_comment = DB::table('comments')
                    ->where('shop_id', $shopID)
                    ->where('question_id', $question->id)
                    ->first();
                // Fetch keywords as a comma-separated string
                $keywordsString = DB::table('keywords')
                    ->where('question_id', $question->id)
                    ->value('keywords'); // Get keywords as a comma-separated string

                // Convert comma-separated string to array
                $keywordsArray = array_map('trim', explode(',', $keywordsString));

                // Assign keywords array to the question object
                $question->keywords = $keywordsArray;
            }
        }

        // dd($formatID);
        // Calculate the overall score
        $overallscore = DB::table('scoreanalysics')
            ->where('shop_id', $shopID)
            ->select(DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overAllScore'))
            ->first();

        // If no score is found, default to 0
        $overAllScore = $overallscore->overAllScore ?? 0;
        // dd($overAllScore);

        // dd($shopoverall);
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

        // dd($overallresult);

        // Pass data to the view
        return view('viewreport', [
            'hierarchyLevels' => $hierarchyLevels,
            'sections' => $sections,
            'shopID' => $shopID,
            'waveID' => $waveID,
            'formatID' => $formatID,
            'status' =>  $status1,
            'shopDetails' => $shopDetails,
            'type' => $type,
            'time' => $time,
            'date' =>  $date,
            'overAllScore' => $overAllScore,
            'sectionScores' =>  $sectionScores,
            // 'embedvideo' => $embedvideo,
            // 'audioUrls' => $audioUrls,
            // 'receiptUrls' => $receiptUrls,
            // 'lostopertunity' => $lostopertunity,
            'overallresult' => $overallresult,
            'user' => $user,
        ]);
    }
    public function proceedback(Request $request)
    {
        $shopID = $request->shopID;
        $assignShop = assignshops::find($shopID);
        $role = $request->session()->get('is_role'); // Use request's session

        if ($assignShop) {
            $assignShop->update([
                'status' => "shopper",
            ]);

            // Return a JSON response indicating success
            return response()->json(['success' => true]);
        }

        // Return a JSON response indicating failure
        return response()->json(['success' => false]);
    }

    // public function viewreport(Request $request)
    // {
    //     // dd($request->all());
    //     // Retrieve request parameters
    //     $formatID = $request->format_id;
    //     $locationID = $request->location_id;
    //     $shopID = $request->shop_id;
    //     $waveID = $request->wave_id;
    //     $status = $request->status;
    //     $initialHierarchyId = $locationID;
    //     $type = $request->type;
    //     // dd($shopID);

    //     $shopDetails = DB::table('assignshops')
    //         ->where('id', $shopID)
    //         ->first();
    //     // dd($shopDetails);
    //     // $status1 = $shopDetails->status;
    //     $status1 =  $shopDetails->status;
    //     $formatID = $shopDetails->format_id;
    //     $waveID =  $shopDetails->wave_id;

    //     // Recursive query to fetch hierarchical data
    //     $query = "
    //     WITH RECURSIVE HierarchyCTE AS (
    //         SELECT 
    //             h.id AS hierarchy_id,
    //             h.levelID AS level_id,
    //             hl.hierarchylavelname AS level_name,
    //             hl.level AS level,
    //             hl.HID AS hid,
    //             l.locationname AS location_name,
    //             h.parentID AS parent_id
    //         FROM 
    //             hierarchies h
    //         INNER JOIN 
    //             hierarchylevels hl ON h.levelID = hl.id
    //         INNER JOIN 
    //             locations l ON h.LID = l.id
    //         WHERE 
    //             h.id = :initialHierarchyId

    //         UNION ALL

    //         SELECT 
    //             h.id AS hierarchy_id,
    //             h.levelID AS level_id,
    //             hl.hierarchylavelname AS level_name,
    //             hl.level AS level,
    //             hl.HID AS hid,
    //             l.locationname AS location_name,
    //             h.parentID AS parent_id
    //         FROM 
    //             hierarchies h
    //         INNER JOIN 
    //             hierarchylevels hl ON h.levelID = hl.id
    //         INNER JOIN 
    //             locations l ON h.LID = l.id
    //         INNER JOIN 
    //             HierarchyCTE hc ON hc.parent_id = h.id
    //     )
    //     SELECT 
    //         hierarchy_id,
    //         level_id,
    //         level_name,
    //         level,
    //         hid,
    //         location_name
    //     FROM 
    //         HierarchyCTE;
    // ";

    //     // Execute query and fetch results
    //     $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);

    //     // Fetch sections and related questions and options
    //     $sections = DB::table('sections')
    //         ->where('format_id', $formatID)
    //         ->get();

    //     foreach ($sections as $section) {
    //         $section->questions = DB::table('questions')
    //             ->where('section_id', $section->id)
    //             ->get();

    //         foreach ($section->questions as $question) {
    //             $question->options = DB::table('options')
    //                 ->where('question_id', $question->id)
    //                 ->get();

    //             // Fetch the saved score and comment for the question
    //             $question->saved_score = DB::table('scores')
    //                 ->where('shop_id', $shopID)
    //                 ->where('question_id', $question->id)
    //                 ->first();

    //             $question->saved_comment = DB::table('comments')
    //                 ->where('shop_id', $shopID)
    //                 ->where('question_id', $question->id)
    //                 ->first();
    //         }
    //     }


    //     // Pass data to the view
    //     return view('viewreport', [
    //         'hierarchyLevels' => $hierarchyLevels,
    //         'sections' => $sections,
    //         'shopID' => $shopID,
    //         'waveID' => $waveID,
    //         'formatID' => $formatID,
    //         'status' =>  $status1,
    //         'shopDetails' => $shopDetails,
    //         'type' => $type,
    //     ]);
    // }

    public function store(Request $request)
    {
        // dd($request->all());

        $shopID = $request->shopID;
        $waveID = $request->waveID;
        $formatID = $request->formatID;
        $locationName =  $request->locations[0];
        $locationid =  $request->hierarchy[0];
        $RegionName =  $request->locations[count($request->locations) - 2];
        $Regionid =  $request->hierarchy[count($request->hierarchy) - 2];

        $assignShop = assignshops::find($request->shopID);
        $statusvalue = $request->status;
        $role = Session::get('is_role');
        // dd($role);
        // Check if the current status is "submitted to client"
        if ($assignShop->status == "submit to client") {
            // Update the status to "proceed back"
            $assignShop->update([
                'status' => "Assigned",

            ]);
            if ($role == 3) {
                return redirect('shopper/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 2) {
                return redirect('manager/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 10) {
                return redirect('superadmin/overallperformance')->with('success', 'Data saved successfully.');
            }
        } elseif ($assignShop->status ==  "Assigned") {
            // Update the status with the value from the request
            $assignShop->update([
                'status' => "shopper",
                'flagforsaved' => '0',
            ]);
            // echo 1;
            // exit();
            if ($role == 3) {
                return redirect('shopper/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 2) {
                return redirect('manager/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 10) {
                return redirect('superadmin/overallperformance')->with('success', 'Data saved successfully.');
            }
        } elseif ($assignShop->status ==  "shopper") {
            $assignShop->update([
                'status' => "manager approved",
            ]);
            if ($role == 3) {
                return redirect('shopper/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 2) {
                return redirect('manager/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 10) {
                return redirect('superadmin/overallperformance')->with('success', 'Data saved successfully.');
            }
        } elseif ($assignShop->status ==  "manager approved") {
            $assignShop->update([
                'status' => "submit to client",

            ]);

            $overallScore = DB::table('scoreanalysics')
                ->select(DB::raw('(SUM(achieved) / (SUM(applicable))) * 100 AS overallScore'))
                ->where('shop_id', $shopID)
                ->value('overallScore');
            $branchoverallscore = $overallScore;
            $data = [
                'format_id' => $formatID,
                'shop_id' => $shopID,
                'wave_id' => $waveID,
                'branchName' => $locationName,
                'overAllScore' => $overallScore,
                'region_id' => $Regionid,
            ];
            // dd($data);
            // exit();
            $existingRecord = branchCalculations::where('shop_id', $shopID)->first();
            if ($existingRecord) {
                $existingRecord->update($data);
            } else {
                branchCalculations::create($data);
            }
            $sections = Section::where('format_id',  $formatID)->get();
            foreach ($sections as $section) {
                $analytics = scoreanalysics::selectRaw('SUM(achieved) as achieved, SUM(applicable) as applicable')
                    ->where('section_id', $section->id)
                    ->first();

                // Format the analytics data into an array
                $data = [
                    'format_id' => $formatID,
                    'shop_id' => $shopID,
                    'wave_id' => $waveID,
                    'section_id' => $section->id,
                    'sectionName' => $section->section_name, // Assuming a field name for section_name
                    'achived' => $analytics->achieved ?? 0, // Default to 0 if no data found
                    'applicable' => $analytics->applicable ?? 0, // Default to 0 if no data found


                ];

                // Check if a record already exists for this section_id
                $existingRecord = sectionCalculations::where('section_id', $section->id)->where('wave_id', $waveID)->first();

                if ($existingRecord) {
                    // Update the existing record
                    $existingRecord->update($data);
                } else {
                    // Insert a new record
                    sectionCalculations::create($data);
                }
            }

            $scores = scoreanalysics::selectRaw('SUM(achieved) as achievedSum, SUM(applicable) as applicableSum')
                ->where('shop_id', $shopID)
                ->first();
            if ($scores) {
                $achievedSum = $scores->achievedSum;
                $applicableSum = $scores->applicableSum;
            }
            $regionData = [
                'format_id' => $formatID,
                'shop_id' => $shopID,
                'wave_id' => $waveID,
                'region_id' => $Regionid,
                'regionName' => $RegionName,
                'achived' => $achievedSum, // Use the overall score or adjust as needed
                'applicable' => $applicableSum, // Use the overall score or adjust as needed
            ];

            $existingRegionRecord = RegionCalculations::where('region_id', $Regionid)
                ->where('wave_id', $waveID)->first();
            if ($existingRegionRecord) {
                // Update the existing record
                $existingRegionRecord->achived +=  $achievedSum; // Add the new score to existing achieved sum
                $existingRegionRecord->applicable += $applicableSum; // Add the new score to existing applicable sum
                $existingRegionRecord->update();
            } else {
                // Insert a new record
                RegionCalculations::create($regionData);
            }

            if ($role == 3) {
                return redirect('shopper/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 2) {
                return redirect('manager/dashboard')->with('success', 'Data saved successfully.');
            } elseif ($role == 10) {
                return redirect('superadmin/overallperformance')->with('success', 'Data saved successfully.');
            }
        }




        // foreach ($request->questions as $question) {
        //     $option = Option::find($question['option_id']);
        //     scores::create([
        //         'shop_id' => $request->shopID,
        //         'question_id' => $question['question_id'],
        //         'option_id' => $question['option_id'],
        //         'achieved_score' =>  $option->score, // Assuming option_id is the score
        //     ]);
        //     $comment = $question['comment'] ?? '';
        //     comments::create([
        //         'question_id' => $question['question_id'],
        //         'shop_id' => $request->shopID,
        //         'comments' => $comment,
        //         // Provide appropriate logic to analyze comments
        //     ]);
        //     $question = Question::find($question['question_id']);
        //     $sectionID = $question->section_id;
        //     $sections = Section::find($sectionID);
        //     // echo $option->score;
        //     if ($option->score === 999) {
        //         $applicablescore = 0;
        //         $achivedscore = 0;
        //     } elseif ($option->score === 0) {
        //         // echo 1;
        //         // exit();
        //         $applicablescore =  $question->score;
        //         $achivedscore = $option->score;
        //     } else {
        //         $applicablescore = $option->score;
        //         $achivedscore = $option->score;
        //     }
        //     scoreanalysics::create([
        //         'shop_id' => $request->shopID,
        //         'format_id' => $request->formatID,
        //         'section_id' =>   $sectionID,
        //         'question_id' => $question->id,
        //         'response' => $option->text,
        //         'achieved' => $achivedscore,
        //         'applicable' => $applicablescore,
        //         'total' =>  $question->score,
        //         'wave_id' => $request->waveID,
        //         'section_name' =>  $sections->section_name,
        //         'question_name' =>  $question->question_name,
        //     ]);
        // }

        // return redirect('shopper/mainshopper')->with('success', 'Data saved successfully.');

    }


    public function storefile(Request $request)
    {
        // dd($request->all());
        if ($request->input('fileType') == "video") {
            $fileUpload = new VisitAudioRecord();
            $fileUpload->shop_id = $request->input('shopID');
            $fileUpload->type = $request->input('fileType');
            $fileUpload->attachmentname = $request->input('video');
            // You may also store the file path if needed
            $fileUpload->attachment = $request->input('video'); // Set the file path accordingly
            $fileUpload->save();
            return redirect()->back()->with('success', 'media file uploaded');
        } else {
            $encryptedFileName = Crypt::encrypt($request->file('file')->getClientOriginalName());

            // Store file details in the database
            $fileUpload = new VisitAudioRecord();
            $fileUpload->shop_id = $request->input('shopID');
            $fileUpload->type = $request->input('fileType');
            $fileUpload->attachmentname = $request->file('file')->getClientOriginalName();
            // You may also store the file path if needed
            $fileUpload->attachment =  $encryptedFileName; // Set the file path accordingly
            $fileUpload->save();
            // Store the file on disk
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            return redirect()->back()->with('success', 'media file uploaded');
        }
    }



    public function saveOption(Request $request)
    {
        // Validate the request data if needed

        $option = Option::find($request->option_id);
        $question = Question::find($request->question_id);
        $sectionID = $question->section_id;
        $sections = Section::find($sectionID);

        // Find the existing score record based on shop_id, question_id, and option_id
        $existingScore = scores::where([
            'question_id' => $request->question_id,
            'shop_id' => $request->shopID,

        ])->first();

        if ($existingScore) {
            // Update the existing record
            $existingScore->achieved_score = $option->score;
            $existingScore->option_id = $request->option_id;
            $existingScore->save();
        } else {
            // Create a new record
            scores::create([
                'question_id' => $request->question_id,
                'shop_id' => $request->shopID,
                'option_id' => $request->option_id,
                'achieved_score' => $option->score,
            ]);
        }

        // Calculate applicable and achieved scores based on the option's score
        if ($option->score == 999) {
            $applicablescore = 0;
            $achivedscore = 0;
        } elseif ($option->score == 0) {
            $applicablescore = $question->score;
            $achivedscore = $option->score;
        } else {
            $applicablescore = $question->score;
            $achivedscore = $option->score;
        }

        // Update or create a record in scoreanalysics table
        $response = $option->text ? $option->text : ''; // Set response based on option text
        // Find the existing score record based on shop_id, question_id, and option_id
        $existing = scoreanalysics::where([
            'question_id' => $request->question_id,
            'shop_id' => $request->shopID,

        ])->first();
        // dd($existing);

        if ($existing) {
            $existing->response = $response;
            $existing->achieved = $achivedscore;
            $existing->applicable = $applicablescore;
            $existing->save(); // Save the updated record
        } else {
            // Create a new record
            scoreanalysics::create([
                'shop_id' => $request->shopID,
                'question_id' => $request->question_id,
                'format_id' => $request->formatID,
                'section_id' => $request->section_id,
                'response' => $response,
                'achieved' => $achivedscore,
                'applicable' => $applicablescore,
                'total' => $question->score,
                'wave_id' => $request->waveID,
                'section_name' => $sections->section_name,
                'question_name' => $question->question_name,
            ]);
        }

        // scoreanalysics::updateOrCreate(
        //     [
        //         'shop_id' => $request->shopID,
        //         'question_id' => $request->question_id,
        //     ],
        //     [
        //         'format_id' => $request->formatID,
        //         'section_id' => $request->section_id,
        //         'response' => $response,
        //         'achieved' => $achivedscore,
        //         'applicable' => $applicablescore,
        //         'total' => $question->score,
        //         'wave_id' => $request->waveID,
        //         'section_name' => $sections->section_name,
        //         'question_name' => $question->question_name,
        //     ]
        // );

        // return response()->json(['message' => 'Option saved successfully.']);
    }

    public function savekeyword(Request $request)
    {
        // Extract request data
        $keyword = $request->input('keyword_id');
        $question_id = $request->input('question_id');
        $section_id = $request->input('section_id');
        $waveID = $request->input('waveID');
        $formatID = $request->input('formatID');
        $shopID = $request->input('shopID');

        // Check if a record with the same question_id and shopID already exists
        $existingRecord = comments::where('question_id', $question_id)
            ->where('shop_id', $shopID)
            ->first();

        if ($existingRecord) {
            // If the record exists, update the comment_analysis field
            $existingRecord->comment_analysis = $existingRecord->comment_analysis
                ? $existingRecord->comment_analysis . ',' . $keyword
                : $keyword;
            $existingRecord->save();

            return response()->json(['message' => 'Comment updated successfully'], 200);
        } else {
            $newComment = new comments();
            $newComment->comment_analysis = $keyword;
            $newComment->save();

            return response()->json(['message' => 'Comment added successfully'], 201);
        }
    }
    public function saveComment(Request $request)
    {
        // Validate the request data if needed

        // // Create or update the comment in the database
        $comment = comments::updateOrCreate(
            [
                'question_id' => $request->question_id,
                'shop_id' => $request->shopID,
            ],
            [
                'comments' => $request->comment,

            ]
        );


        // return response()->json(['message' => 'Comment saved successfully.']);
    }
    public function saveShopData(Request $request)
    {
        // Validate the request data if needed

        // Find the shop record based on shopID
        $shop = assignshops::find($request->shopID);

        // Check if shop record exists
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        // Update the shop data based on the field provided
        switch ($request->field) {
            case 'timeIn':
                $shop->timeIn = $request->value;
                break;
            case 'timeOut':
                $shop->timeOut = $request->value;
                break;
            case 'visitType':
                $shop->visit_type = $request->value;
                break;
            case 'date':
                $shop->date = $request->value;
                break;
            default:
                return response()->json(['error' => 'Invalid field'], 400);
        }

        // Save the updated shop data
        $shop->save();

        return response()->json(['message' => 'Shop data updated successfully']);
    }
    public function save(Request $request)
    {
        $flag = 1;
        $shopID = $request->shopID;
        // dd($request->all());
        $username = session()->get('username');
        $assignshop = assignshops::where('id', $shopID)->first();
        $assignshop->project_manager_name = $username;
        $assignshop->flagforsaved = 1;
        $assignshop->save();

        // Optionally, you can also return a response or perform other actions after the update
        return redirect()->back()->with('success', 'Data Saved Successfully');
    }
}
