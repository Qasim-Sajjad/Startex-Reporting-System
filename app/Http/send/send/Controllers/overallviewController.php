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
class overallviewController extends Controller
{
    public function overallperformance(Request $request)
    {
        $parentName = Session::get('parentName');

        $users = User::where('is_role', '=', '4')

            ->where('parentName', '=', $parentName)

            ->pluck('name', 'id');
        return view('superadmin.overallperformance', compact('users'));
    }
    public function getreport1(Request $request)
    {
        // Get the format information
        // $formats = DB::table('formats')->orderBy('id', 'desc')->get();
        $parentName = Session::get('parentName');

        $formats = Format::join('users', 'formats.user_id', '=', 'users.id')
            ->where('users.parentName', '=', $parentName)
            ->select('formats.id as id', 'formats.user_id  as user_id', 'formats.name as name', 'formats.assignHID as assignHID') // Select columns from formats, you can customize as needed
            ->orderBy('formats.id', 'desc') // Order by format id in descending order
            ->get();
        $responseData = [];

        foreach ($formats as $HID) {
            $formatID = $HID->id;
            $clientID = $HID->user_id;
            $clientName = User::where('id', '=', $clientID)->value('name');

            $waves = DB::table('waves')
                ->where('format_id', $formatID)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($waves as $wave) {
                $waveid = $wave->id;

                // Get the hierarchy levels information
                $hirerchylevels = DB::table('hierarchylevels')->where('HID', $HID->assignHID)
                    ->orderBy('id', 'desc')
                    ->first();

                // Proceed only if hierarchy levels information is found
                if ($hirerchylevels) {
                    $locations = DB::table('hierarchies as hierarchy')
                        ->join('locations as lid', 'hierarchy.LID', '=', 'lid.id')
                        ->join('assignshops as assign', 'hierarchy.id', '=', 'assign.location_id')
                        ->join('waves as wave', 'assign.wave_id', '=', 'wave.id')
                        ->where('assign.format_id', $formatID)
                        ->where('assign.wave_id', $waveid)
                        ->select('wave.name as waveName', 'hierarchy.branch_code as branchcode', 'lid.locationname as locationName', 'assign.id as ID', 'assign.project_manager_name as project_manager_name', 'assign.status as statusreport', 'hierarchy.id as locationID')
                        ->get();

                    $overallCount = $locations->count();
                    $statusCount = [
                        'Assigned' => 0,
                        'shopper' => 0,
                        'manager approved' => 0,
                        'submit to client' => 0,
                    ];
                    $locationDetails = [
                        'Assigned' => [],
                        'shopper' => [],
                        'manager approved' => [],
                        'submit to client' => [],
                    ];

                    foreach ($locations as $location) {
                        $statusCount[$location->statusreport]++;
                        $locationDetails[$location->statusreport][] = [
                            'locationName' => $location->locationName,
                            'ID' => $location->ID,
                            'locationID' => $location->locationID,
                            'waveName' => $location->waveName,
                            'branchcode' => $location->branchcode,
                            'edit' => $location->project_manager_name,
                        ];
                    }

                    $responseData[] = [
                        'clientName' => $clientName,
                        'formatName' => $HID->name,
                        'waveName' => $wave->name,
                        'assignedCount' => $overallCount,
                        'Assigned' => $statusCount['Assigned'],
                        'shopperCount' => $statusCount['shopper'],
                        'managerCount' => $statusCount['manager approved'],
                        'submittedCount' => $statusCount['submit to client'],
                        'locationDetails' => $locationDetails,
                    ];
                }
            }
        }

        // Return the aggregated response data as JSON
        return response()->json($responseData);
    }
    public function getreport(Request $request)
    {
        $formatid = $request->format_id;
        $waveid = $request->wave_id;
        $shopperID = Session::get('user_id');

        // Get the format information
        $HID = DB::table('formats')->where('id', $formatid)->first();
        if ($HID) {
            $assignHID = $HID->assignHID;
            $clientID = $HID->user_id;
            $clientName = User::where('id', '=', $clientID)->value('name');

            // Get the hierarchy levels information
            $hirerchylevels = DB::table('hierarchylevels')->where('HID', $assignHID)
                ->orderBy('id', 'desc')
                ->first();

            // Proceed only if hierarchy levels information is found
            if ($hirerchylevels) {
                $locationid = $hirerchylevels->id;
                $locationname = $hirerchylevels->hierarchylavelname;

                // Get the locations based on the hierarchy level, format ID, and wave ID
                $locations = DB::table('hierarchies as hierarchy')
                    ->join('locations as lid', 'hierarchy.LID', '=', 'lid.id')
                    ->join('assignshops as assign', 'hierarchy.id', '=', 'assign.location_id')
                    ->join('waves as wave', 'assign.wave_id', '=', 'wave.id')
                    // ->where('hierarchy.levelID', $locationid)
                    ->where('assign.format_id', $formatid)
                    ->where('assign.wave_id', $waveid)
                    ->select('wave.name as waveName', 'hierarchy.branch_code as branchcode', 'lid.locationname as locationName', 'assign.id as ID', 'assign.project_manager_name as project_manager_name', 'assign.status as statusreport', 'hierarchy.id as locationID')
                    ->get();

                $overallCount = $locations->count();
                // Prepare the response data as an array
                $responseData = [];
                $statusCount = [
                    'Assigned' => 0,
                    'shopper' => 0,
                    'manager approved' => 0,
                    'submit to client' => 0,
                ];

                $locationDetails = [
                    'Assigned' => [],
                    'shopper' => [],
                    'manager approved' => [],
                    'submit to client' => [],
                ];

                foreach ($locations as $location) {
                    $statusCount[$location->statusreport]++;
                    $locationDetails[$location->statusreport][] = [
                        // 'locationName' => $location->locationName,
                        // 'ID' => $location->ID,
                        // 'locationID' => $location->locationID
                        'locationName' => $location->locationName,
                        'ID' => $location->ID,
                        'locationID' => $location->locationID,
                        'waveName' => $location->waveName,
                        'branchcode' => $location->branchcode,
                        'edit' =>  $location->project_manager_name,
                    ];
                }

                // Add the formatted data to the response array
                $responseData[] = [
                    'clientName' => $clientName,
                    'formatName' => $HID->name,
                    'assignedCount' => $overallCount,
                    'Assigned' => $statusCount['Assigned'],
                    'shopperCount' => $statusCount['shopper'],
                    'managerCount' => $statusCount['manager approved'],
                    'submittedCount' => $statusCount['submit to client'],
                    'locationDetails' => $locationDetails
                ];

                // Return the response as JSON
                return response()->json($responseData);
            }
        }

        return "No location exists";
    }
}
