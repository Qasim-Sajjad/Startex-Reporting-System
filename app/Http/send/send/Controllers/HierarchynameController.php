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


use Illuminate\Support\Str;


use Illuminate\Support\Facades\Hash; // Import the Hash facade

use Auth;



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


class HierarchynameController extends Controller
{
    public function createhierarchy(Request $request)
    {
        $parentName = Session::get('parentName');
        $users = User::where('is_role', '4')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');

        return view('superadmin.createhierarchy', compact('users'));
    }

    public function processData(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'hierarchy_name' => 'required|string',
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);
        // Retrieve form data
        $formatId = $request->input('format_id');
        $hierarchyName = $request->input('hierarchy_name');
        $excelFile = $request->file('excel_file');
        $clientid = $request->input('user_id');
        // dd($clientid);
        $filePath = $excelFile->storeAs('temp', $excelFile->getClientOriginalName());

        $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        $hierarchynames = new Hierarchynames();
        $hierarchynames->hierarchyname = $hierarchyName; // Assuming 'hierarchyname' is the column name
        $hierarchynames->client_id = $clientid;
        $hierarchynames->save();
        $insertedId = $hierarchynames->id;
        $hierarchicalLevels = $data[1];
        // dd($hierarchicalLevels);
        $levelsCount = count($hierarchicalLevels); // Get the count of levels
        // dd($levelsCount);
        foreach ($hierarchicalLevels as $key => $level) {
            // if ($key === $levelsCount - 1) {
            //     continue; // Skip the last iteration
            // }
            if ($key >= $levelsCount - 2) {
                continue; // Skip the last two iterations
            }
            $hierarchylevel = new Hierarchylevels();
            $hierarchylevel->hierarchylavelname = $level; // Assuming 'hierarchylavelname' is the column name
            $hierarchylevel->level = $key + 1; // Assuming levels start from 1 and increment by 1
            $hierarchylevel->HID = $insertedId; // Assuming you have the hierarchy ID available

            $hierarchylevel->save();
        }

        $parentID = 0; // Initialize parentID as 0 for the first level

        for ($key = 2; $key < count($data); $key++) {
            $row = $data[$key]; // Get the row at the current index
            $branchCode = $row[count($row) - 2]; // Second last column
            $address = $row[count($row) - 1];
            // dd($address);
            // exit();
            foreach ($row  as $index => $columnData) {
                $level = $index + 1;
                $hierarchyID = $insertedId;
                // echo $hierarchyID;
                $hierarchylevel = Hierarchylevels::where('HID', $hierarchyID)->where('level', $level)->first();
                if ($hierarchylevel) {
                    $levelID = $hierarchylevel->id;
                }
                // echo $columnData;
                $locationname = locations::where('locationname', $columnData)->select('id', 'locationname')->first();
                // dd($locationname);
                // die();
                // echo  $locationname->locationname . "br>";
                if ($locationname) {
                    $locationID = $locationname->id;
                } else {
                    $newLocation = new locations();
                    $newLocation->locationname = $columnData;
                    $newLocation->save();

                    // Retrieve the ID of the newly inserted location
                    $locationID = $newLocation->id;
                }

                if ($index === count($row) - 1) {
                    $hierarchy = hierarchies::find($parentID);
                    $hierarchy->address = $address; // Assuming $columnData contains the branch code
                    $hierarchy->save();
                }
                if ($index === count($row) - 2) {
                    $hierarchy = hierarchies::find($parentID);
                    $hierarchy->branch_code = $branchCode; // Assuming $columnData contains the branch code
                    $hierarchy->save();
                } else {
                    $checked = DB::table('hierarchies')
                        ->where('LID', $locationID)
                        ->where('levelID', $levelID)
                        ->first();
                    // dd($checked);
                    if (!empty($checked)) {
                        $parentID = $checked->id;
                        continue;
                    } else {
                        $hierarchy = new hierarchies();
                        $hierarchy->LID = $locationID; // Assuming each column contains the level names
                        $hierarchy->levelID =  $levelID;
                        $hierarchy->parentID =  $parentID; // Set the parentID
                        $hierarchy->save();
                        $parentID = $hierarchy->id;
                    }
                }
            }

            $parentID = 0;
        }
        // return redirect()->back()->with('success', 'hierarchy stored successfully!');
        return redirect()->route('createformat')->with('success', '');
    }
    public function processData1(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'hierarchy_name' => 'required|string',
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);
        // Retrieve form data
        $formatId = $request->input('format_id');
        $hierarchyName = $request->input('hierarchy_name');
        $excelFile = $request->file('excel_file');
        $clientid = $request->input('user_id');
        // dd($clientid);
        $filePath = $excelFile->storeAs('temp', $excelFile->getClientOriginalName());

        $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        $hierarchynames = new Hierarchynames();
        $hierarchynames->hierarchyname = $hierarchyName; // Assuming 'hierarchyname' is the column name
        $hierarchynames->client_id = $clientid;
        $hierarchynames->save();
        $insertedId = $hierarchynames->id;
        $hierarchicalLevels = $data[1];
        // dd($hierarchicalLevels);
        $levelsCount = count($hierarchicalLevels); // Get the count of levels
        // dd($levelsCount);
        foreach ($hierarchicalLevels as $key => $level) {
            // if ($key === $levelsCount - 1) {
            //     continue; // Skip the last iteration
            // }
            if ($key >= $levelsCount - 2) {
                continue; // Skip the last two iterations
            }
            $hierarchylevel = new Hierarchylevels();
            $hierarchylevel->hierarchylavelname = $level; // Assuming 'hierarchylavelname' is the column name
            $hierarchylevel->level = $key + 1; // Assuming levels start from 1 and increment by 1
            $hierarchylevel->HID = $insertedId; // Assuming you have the hierarchy ID available

            $hierarchylevel->save();
        }

        $parentID = 0; // Initialize parentID as 0 for the first level

        for ($key = 2; $key < count($data); $key++) {
            $row = $data[$key]; // Get the row at the current index
            $branchCode = $row[count($row) - 2]; // Second last column
            $address = $row[count($row) - 1];
            foreach ($row  as $index => $columnData) {
                $level = $index + 1;
                $hierarchyID = $insertedId;
                // echo $hierarchyID;
                $hierarchylevel = Hierarchylevels::where('HID', $hierarchyID)->where('level', $level)->first();
                if ($hierarchylevel) {
                    $levelID = $hierarchylevel->id;
                }
                // echo $columnData;
                $locationname = locations::where('locationname', $columnData)->select('id', 'locationname')->first();
                // dd($locationname);
                // die();
                // echo  $locationname->locationname . "br>";
                if ($locationname) {
                    $locationID = $locationname->id;
                } else {
                    $newLocation = new locations();
                    $newLocation->locationname = $columnData;
                    $newLocation->save();

                    // Retrieve the ID of the newly inserted location
                    $locationID = $newLocation->id;
                }

                if ($index === count($row) - 1) {
                    $hierarchy = hierarchies::find($parentID);
                    $hierarchy->address = $address; // Assuming $columnData contains the branch code
                    $hierarchy->save();
                }
                if ($index === count($row) - 2) {
                    $hierarchy = hierarchies::find($parentID);
                    $hierarchy->branch_code = $branchCode; // Assuming $columnData contains the branch code
                    $hierarchy->save();
                } else {
                    $checked = DB::table('hierarchies')
                        ->where('LID', $locationID)
                        ->where('levelID', $levelID)
                        ->first();
                    // dd($checked);
                    if (!empty($checked)) {
                        $parentID = $checked->id;
                        continue;
                    } else {
                        $hierarchy = new hierarchies();
                        $hierarchy->LID = $locationID; // Assuming each column contains the level names
                        $hierarchy->levelID =  $levelID;
                        $hierarchy->parentID =  $parentID; // Set the parentID
                        $hierarchy->save();
                        $parentID = $hierarchy->id;
                    }
                }
            }

            $parentID = 0;
        }
        session()->forget('currentStep');
        session()->put('currentStep', 2);

        // return redirect()->back()->with('success', 'hierarchy stored successfully!');
        return view('superadmin.clientcreate')->with('success', 'hierarchy stored successfully!');
    }
    public function assignHierarchy(Request $request)
    {
        $parentName = Session::get('parentName');
        $users = User::where('is_role', '4')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');

        return view('superadmin.assignHierarchy', compact('users'));
    }
    public function getFormatsAndHierarchies(Request $request)
    {
        $userId = $request->id;

        // Fetch formats related to the user
        $formats = Format::where('user_id', $userId)->get();
        $formatOptions = '<option value="">Select Format</option>'; // Default option
        foreach ($formats as $format) {
            $formatOptions .= '<option value="' . $format->id . '">' . $format->name . '</option>';
        }

        // Fetch hierarchies related to the user
        $hierarchies = Hierarchynames::where('client_id', $userId)->get();
        $hierarchyOptions = '<option value="">Select Hierarchy</option>'; // Default option
        foreach ($hierarchies as $hierarchy) {
            $hierarchyOptions .= '<option value="' . $hierarchy->id . '">' . $hierarchy->hierarchyname . '</option>';
        }

        return response()->json([
            'formats' => $formatOptions,
            'hierarchies' => $hierarchyOptions
        ]);
    }
    public function  assignformat(Request $request)
    {
        // $request->validate([
        //     'format_id' => 'required|numeric',
        //     'hierarchy_id' => 'required|numeric'
        // ]);
        // dd($request->all());
        // Get the format ID from the request
        $formatId = $request->input('format_id');

        // Find the format by ID
        $format = Format::find($formatId);

        // If format found, update the hierarchy ID
        if ($format) {
            if ($format->assignHID) {
                session()->forget('currentStep');
                session()->put('currentStep', 4);
                return back()->with('success', 'Already Assign');
            } else {
                $format->assignHID = $request->input('hierarchy_id');
                $format->save();
                session()->forget('currentStep');
                session()->put('currentStep', 4);
                return back()->with('success', 'Assign format to hierarchy successfully.');
            }
        }
    }

    public function  assignproject(Request $request)
    {
        $parentName = Session::get('parentName');
        $managers = User::where('is_role', '2')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');
        $users = User::where('is_role', '4')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');
        $shoppers = User::where('is_role', '3')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');

        return view('superadmin.assignproject', [
            'managers' => $managers,
            'shoppers' => $shoppers,
            'users' => $users
        ]);
    }
    public function  assignprojecttomanager(Request $request)
    {
        // dd($request->all());
        // exit()
        $request->validate([
            'format_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'manager_id' => 'required|numeric'
        ]);


        $checked = DB::table('assignprojects')
            ->where('user_id',  $request->manager_id)
            ->where('format_id', $request->format_id)
            ->first();
        // dd($checked);
        if (!empty($checked)) {

            session()->forget('currentStep');
            session()->put('currentStep', 10);
            return redirect()->back()->with('success', 'Already Assign');
        } else {
            $assignProject = new assignprojects();
            $assignProject->user_id = $request->manager_id;
            $assignProject->format_id = $request->format_id;
            $assignProject->percentage = $request->percentage;
            $assignProject->save();

            session()->forget('currentStep');
            session()->put('currentStep', 10);
            return redirect()->back()->with('success', 'Assign format to manager successfully.');
        }
    }
    public function  assignprojecttomanager1(Request $request)
    {
        // exit()
        $request->validate([
            'format_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'manager_id' => 'required|numeric'
        ]);


        $checked = DB::table('assignprojects')
            ->where('user_id',  $request->manager_id)
            ->where('format_id', $request->format_id)
            ->first();
        // dd($checked);
        if (!empty($checked)) {

            session()->forget('currentStep');
            session()->put('currentStep', 10);
            // return redirect()->back()->with('success', 'Already Assign');
            return view('superadmin.clientcreate')->with('success', 'Already Assign');
        } else {
            $assignProject = new assignprojects();
            $assignProject->user_id = $request->manager_id;
            $assignProject->format_id = $request->format_id;
            $assignProject->save();

            session()->forget('currentStep');
            session()->put('currentStep', 10);
            // return redirect()->back()->with('success', 'Assign format to manager successfully.');
            return view('superadmin.clientcreate')->with('success', 'Assign format to manager successfully.');
        }
    }
    public function  assignshops(Request $request)
    {
        $parentName = Session::get('parentName');
        $shoppers = User::where('is_role', '3')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');
        $users = User::where('is_role', '4')
            ->where('parentName', $parentName)
            ->pluck('name', 'id');
        return view('superadmin.assignshops', compact('shoppers', 'users'));
    }
    public function  getwave(Request $request)
    {
        // dd($request->all());
        $format_id1 = $request->format_id; // Get the user ID from the request

        // Find all formats where the client_id matches $userId
        $waves = Waves::where('format_id', $format_id1)->get();
        $options = '';
        $options = '<option value="">Select Wave</option>'; // Default option
        foreach ($waves as $wave) {
            $options .= "<option value='{$wave->id}'>{$wave->name}</option>";
        }
        return $options;
    }
    public function  getwave1(Request $request)
    {
        // dd($request->all());
        $format_id = $request->input('format_id');
        // Retrieve waves for the selected format
        $waves = Waves::where('format_id', $format_id)->get(); // Adjust according to your models

        return response()->json($waves); // Return waves as a JSON array
    }
    public function  getshops(Request $request)
    {
        // dd($request->all());
        $formatid = $request->format_id;
        $waveid = $request->wave_id;
        $HID = DB::table('formats')->where('id', $formatid)->first();
        if ($HID) {
            $assignHID = $HID->assignHID;
            $hirerchylevels = DB::table('hierarchylevels')->where('HID', $assignHID)
                ->orderBy('id', 'desc')
                ->first();
            $locationid = $hirerchylevels->id;
            $locationname = $hirerchylevels->hierarchylavelname;
            $locations = DB::table('hierarchies as hierarchy')
                ->join('locations as lid', 'hierarchy.LID', '=', 'lid.id')
                ->where('hierarchy.levelID', $locationid)
                ->select('lid.locationname as locationName', 'hierarchy.id as ID', 'hierarchy.branch_code as branch_code')
                ->get();
            return response()->json($locations);
        } else {
            return "no location exits";
        }
    }
    public function  assignlocation(Request $request)
    {
        //dd($request->all());
        $shoppers_id = $request->shoppers_id;
        $user_id = $request->user_id1;
        $format_id = $request->format_id1;
        $wave_id = $request->wave_id;
        $client_id = $request->user_id1;
        $checked_location_ids = json_decode($request->checked_location_ids); // Decode the JSON string

        $currentTime = now(); // Get the current time using Laravel's now() helper

        // Loop through the checked_location_ids array and store each location ID in the database
        foreach ($checked_location_ids as $location_id) {
            $existingAssignment = assignshops::where('location_id', $location_id)
                ->where('wave_id', $wave_id)
                ->first();

            // If the combination exists, skip this iteration
            if ($existingAssignment) {
                continue;
            }
            $status = "Assigned";
            assignshops::create([
                'shopper_id' => $shoppers_id,
                'location_id' => $location_id,
                'format_id' => $format_id,
                'wave_id' => $wave_id,
                'client_id' => $client_id,
                'status' => $status,
            ]);
        }

        session()->forget('currentStep');
        session()->put('currentStep', 9);
        // Optionally, you can add a success message or redirect to another page
        return redirect()->back()->with('success', 'Locations assigned successfully');
    }
    public function  assignlocation1(Request $request)
    {
        // dd($request->all());
        $shoppers_id = $request->shoppers_id;
        $user_id = $request->user_id;
        $format_id = $request->format_id;
        $wave_id = $request->wave_id;
        $client_id = $request->user_id;
        $checked_location_ids = json_decode($request->checked_location_ids); // Decode the JSON string

        $currentTime = now(); // Get the current time using Laravel's now() helper

        // Loop through the checked_location_ids array and store each location ID in the database
        foreach ($checked_location_ids as $location_id) {
            $existingAssignment = assignshops::where('location_id', $location_id)
                ->where('wave_id', $wave_id)
                ->first();

            // If the combination exists, skip this iteration
            if ($existingAssignment) {
                continue;
            }
            $status = "Assigned";
            assignshops::create([
                'shopper_id' => $shoppers_id,
                'location_id' => $location_id,
                'format_id' => $format_id,
                'wave_id' => $wave_id,
                'client_id' => $client_id,
                'status' => $status,
            ]);
        }

        session()->forget('currentStep');
        session()->put('currentStep', 9);
        // Optionally, you can add a success message or redirect to another page
        // return redirect()->back()->with('success', 'Locations assigned successfully');
        return view('superadmin.clientcreate')->with('success', 'Locations assigned successfully');
    }
}
