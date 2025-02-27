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


class ShopperController extends Controller
{
    public function mainshopper(Request $request)
    {
        $shopperID = Session::get('user_id');
        // echo $shopperID;
        // exit();
        if (!$shopperID) {
            return redirect('login')->with('error', 'User ID not found in session.');
        }

        // Use Eloquent for a more Laravel-idiomatic approach
        $clients = DB::table('assignshops')
            ->join('users', 'assignshops.client_id', '=', 'users.id')
            ->where('assignshops.shopper_id', $shopperID)
            ->select('users.id as client_id', 'users.name as clientName')
            ->groupBy('users.id', 'users.name') // Make sure all selected columns are included in the groupBy
            ->get();

        return view('shopper.mainshopper', compact('clients'));
    }

    public function getUserFormat(Request $request)
    {
        $userId = $request->id;
        // dd($userId);

        $formats = DB::table('assignshops')
            ->join('formats', 'assignshops.format_id', '=', 'formats.id')
            ->where('assignshops.client_id', $userId)
            ->select('formats.id as format_id', 'formats.name as formatName')
            ->groupBy('formats.id', 'formats.name') // Make sure all selected columns are included in the groupBy
            ->get();
        $options = '';

        $options = '<option value="">Select Format</option>'; // Default option
        foreach ($formats as $format) {
            $options .= "<option value='{$format->format_id}'>{$format->formatName}</option>";
        }
        return $options;
    }
    public function  getwave(Request $request)
    {
        // dd($request->all());
        $formatID = $request->format_id;
        $waves = DB::table('assignshops')
            ->join('waves', 'assignshops.wave_id', '=', 'waves.id')
            ->where('assignshops.format_id',  $formatID)
            ->select('waves.id as wave_id', 'waves.name as wavesName')
            ->groupBy('waves.id', 'waves.name') // Make sure all selected columns are included in the groupBy
            ->get();
        $options = '';

        $options = '<option value="">Select wave</option>'; // Default option
        foreach ($waves as $wave) {
            $options .= "<option value='{$wave->wave_id}'>{$wave->wavesName}</option>";
        }
        // dd($options);
        return response()->json($options);
    }
    public function  getshops(Request $request)
    {
        // dd($request->all());
        $formatid = $request->format_id;
        $waveid = $request->wave_id;
        $shopperID = Session::get('user_id');
        $HID = DB::table('Formats')->where('id', $formatid)->first();
        if ($HID) {
            $assignHID = $HID->assignHID;
            $hirerchylevels = DB::table('hierarchylevels')->where('HID', $assignHID)
                ->orderBy('id', 'desc')
                ->first();
            $locationid = $hirerchylevels->id;
            $locationname = $hirerchylevels->hierarchylavelname;
            $locations = DB::table('hierarchies as hierarchy')
                ->join('locations as lid', 'hierarchy.LID', '=', 'lid.id')
                ->join('assignshops as assign', 'hierarchy.id', '=', 'assign.location_id')
                ->where('hierarchy.levelID', $locationid)
                ->where('assign.shopper_id', $shopperID)
                ->where('assign.status', "Assigned")
                ->where('assign.format_id', $formatid)
                ->where('assign.wave_id',  $waveid)
                ->select('lid.locationname as locationName', 'assign.id as ID', 'hierarchy.id as locationID')
                ->get();
            return response()->json($locations);
        } else {
            return "no location exits";
        }
    }


    public function getdata(Request $request)
    {
        $shopperID = $request->input('shopperID');
        $type = $request->input('type');
        if ($type == "completeandsubmit") {
            $results = DB::table('assignshops')
                ->select('users.name as clientName', 'assignshops.id as shopID', 'assignshops.location_id as locationID', 'locations.locationname as locationName', 'assignshops.status as locationstatus', 'assignshops.flagforsaved as savedvalue', 'hierarchies.branch_code as branchcode')
                ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')
                ->join('locations', 'hierarchies.LID', '=', 'locations.id')
                ->join('users', 'assignshops.client_id', '=', 'users.id')
                ->where('assignshops.shopper_id', $shopperID)
                ->whereIn('assignshops.status', ['submit to client', 'shopper', 'manager approved'])
                ->get();
            return $results;
        } elseif ($type == "saved") {
            $results = DB::table('assignshops')
                ->select('users.name as clientName', 'assignshops.id as shopID', 'assignshops.location_id as locationID', 'locations.locationname as locationName', 'assignshops.status as locationstatus', 'assignshops.flagforsaved as savedvalue', 'hierarchies.branch_code as branchcode')
                ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')
                ->join('locations', 'hierarchies.LID', '=', 'locations.id')
                ->join('users', 'assignshops.client_id', '=', 'users.id')
                ->where('assignshops.shopper_id', $shopperID)
                ->where('assignshops.flagforsaved', 1)
                ->get();
            return $results;
        } elseif ($type == "pending") {
            $results = DB::table('assignshops')
                ->select('users.name as clientName', 'assignshops.id as shopID', 'assignshops.location_id as locationID', 'locations.locationname as locationName', 'assignshops.status as locationstatus', 'assignshops.flagforsaved as savedvalue', 'hierarchies.branch_code as branchcode')
                ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')
                ->join('locations', 'hierarchies.LID', '=', 'locations.id')
                ->join('users', 'assignshops.client_id', '=', 'users.id')
                ->where('assignshops.shopper_id', $shopperID)
                ->where('assignshops.status', 'Assigned')
                ->where(function ($query) {
                    $query->where('assignshops.flagforsaved', 0)
                        ->orWhereNull('assignshops.flagforsaved');
                })
                ->get();
            return $results;
        }
        // dd($request->all());
    }
}
