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

class managerController extends Controller
{
    public function mainmanager(Request $request)
    {
        $managerID = Session::get('manager_id');
        // echo $managerID;
        // exit();
        if (!$managerID) {
            return redirect('login')->with('error', 'User ID not found in session.');
        }

        // // Use Eloquent for a more Laravel-idiomatic approach
        $clients = assignprojects::where('assignprojects.user_id',  $managerID)
            ->join('formats', 'assignprojects.format_id', '=', 'formats.id')
            ->join('users', 'formats.user_id', '=', 'users.id')
            ->distinct()
            ->select('users.id as clientID', 'users.name as clientname')
            ->get();

        return view('manager.mainmanager', compact('clients'));
    }
    public function getUserFormat(Request $request)
    {
        $userId = $request->id;

        $formats = DB::table('assignprojects')
            ->join('formats', 'assignprojects.format_id', '=', 'formats.id')
            ->where('formats.user_id', $userId)
            ->select('formats.id as formatID', 'formats.name as formatName')
            ->distinct()
            ->get();

        $options = '<option value="">Select Format</option>'; // Default option
        foreach ($formats as $format) {
            $options .= "<option value='{$format->formatID}'>{$format->formatName}</option>";
        }

        return $options;
    }
    // public function  getshops(Request $request)
    // {
    //     // dd($request->all());
    //     $formatid = $request->format_id;
    //     $waveid = $request->wave_id;
    //     $shopperID = Session::get('user_id');
    //     $HID = DB::table('Formats')->where('id', $formatid)->first();
    //     if ($HID) {
    //         $assignHID = $HID->assignHID;
    //         $hirerchylevels = DB::table('hierarchylevels')->where('HID', $assignHID)
    //             ->orderBy('id', 'desc')
    //             ->first();
    //         $locationid = $hirerchylevels->id;
    //         $locationname = $hirerchylevels->hierarchylavelname;
    //         $locations = DB::table('hierarchies as hierarchy')
    //             ->join('locations as lid', 'hierarchy.LID', '=', 'lid.id')
    //             ->join('assignshops as assign', 'hierarchy.id', '=', 'assign.location_id')
    //             ->where('hierarchy.levelID', $locationid)
    //             ->where('assign.format_id', $formatid)
    //             ->where('assign.wave_id',  $waveid)
    //             ->where(function ($query) {
    //                 $query->where('assign.status', "Assigned")
    //                     ->orWhere('assign.status', "shopper");
    //             })
    //             ->select('lid.locationname as locationName', 'assign.id as ID', 'hierarchy.id as locationID')
    //             ->get();
    //         return response()->json($locations);
    //     } else {
    //         return "no location exits";
    //     }
    // }
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
                    ->where('hierarchy.levelID', $locationid)
                    ->where('assign.format_id', $formatid)
                    ->where('assign.wave_id', $waveid)
                    ->select('wave.name as waveName', 'hierarchy.branch_code as branchcode','lid.locationname as locationName', 'assign.id as ID', 'assign.project_manager_name as project_manager_name', 'assign.status as statusreport', 'hierarchy.id as locationID')
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
                        'locationName' => $location->locationName,
                        'ID' => $location->ID,
                        'locationID' => $location->locationID,
                        'waveName' => $location->waveName,
                      'branchcode' => $location-> branchcode,
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
