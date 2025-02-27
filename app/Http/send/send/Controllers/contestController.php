<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Format;
use App\Models\User;
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
use App\Models\contests;
use Illuminate\Support\Facades\Mail;

class contestController extends Controller
{
    public function contestlist(Request $request)
    {
        $format_id =  session::get('format_id');
        $wave_id1 = session::get('wave_id1'); //wave
        $clientID = Session::get('user_id');

        session::put('title', "Contest List");
        $shops = assignshops::where('format_id', $format_id)
            ->where('wave_id', '=', $wave_id1)
            ->where('status', 'submit to client')
            ->get();
        foreach ($shops as $shop) {
            $shopID = $shop->id;
        }
        $contest = DB::table('contests')
            ->select(
                'assignshops.location_id AS location',
                'contests.created_at as datecontest',
                'waves.name as waveName',
                'contests.branchName as branch',
                'contests.wave_id',
                'contests.shop_id'
            )
            ->join('waves', 'contests.wave_id', '=', 'waves.id')
            ->join('assignshops', 'contests.shop_id', '=', 'assignshops.id')

            ->where('contests.client_id',  $clientID)
            ->where('contests.wave_id',  '=',  $wave_id1)
            ->groupBy('assignshops.location_id', 'contests.shop_id', 'contests.created_at', 'waves.name', 'contests.branchName', 'contests.wave_id')
            ->get();


        $format = DB::table('formats')

            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')

            ->where('formats.id',  $format_id)

            ->select('formats.*', 'hierarchylevels.*')

            ->get();

        $allHierarchyLevels = [];



        foreach ($contest as $shopDetails) {

            $locationID = $shopDetails->location;
            $shop_id = $shopDetails->shop_id;
            $waveName =  $shopDetails->waveName;
            $datecontest = $shopDetails->datecontest;
            $branch  = $shopDetails->branch;
            $wave_id = $shopDetails->wave_id;
            // Recursive query to fetch hierarchical data for each shop

            $query = "

                WITH RECURSIVE HierarchyCTE AS (

                    SELECT 

                        h.id AS hierarchy_id,

                        h.levelID AS level_id,

                        hl.hierarchylavelname AS level_name,

                        hl.level AS level,

                        hl.HID AS hid,

                        l.locationname AS location_name,

                        h.branch_code AS branch_code,  -- Added location_code

                        h.address AS address,              -- Added address

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

                        h.branch_code AS branch_code,  -- Added location_code

                        h.address AS address,              -- Added address

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

                    location_name,

                    branch_code,  -- Include location_code in the result

                    address          -- Include address in the result

                FROM 

                    HierarchyCTE;

                ";



            // Execute query and fetch results for the current shop

            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $locationID]);

            $hierarchyLevels = array_reverse($hierarchyLevels);



            // Collect the results for all shops with locationID included

            $allHierarchyLevels[] = [
                'locationID' => $locationID,
                'hierarchyLevels' => $hierarchyLevels,
                'shop_id' => $shop_id,
                'waveName' => $waveName,
                'datecontest' => $datecontest,
                'branch' => $branch,
                'wave_id' => $wave_id,
            ];
        }
        // dd(   $allHierarchyLevels);
        return view('client.contestlist', [
            // 'contests' =>  $contests,
            'headerName' => $format,
            'contests' => $allHierarchyLevels,
        ]);
    }
    public function contestlistadmin(Request $request)
    {
        $parentName = Session::get('parentName');

        $contests = contests::join('users', 'contests.client_id', '=', 'users.id')
            ->select('contests.*', 'users.name as clientName')
            ->where('users.parentName', $parentName) // Add the where condition here
            ->groupBy(
                'users.id',
                'contests.id',
                'contests.shop_id',
                'contests.client_id',
                'contests.wave_id',
                'contests.branchName',
                'contests.comentby',
                'contests.comentAcceptReject',
                'contests.clientReply',
                'contests.AdminReply',
                'contests.created_at',
                'contests.contest',
                'contests.updated_at',
                'users.name',
            )
            ->get();
        return view('superadmin.contestlist1', [
            'contests' =>  $contests,
        ]);
    }

    public function submitComment(Request $request)
    {
        $clientID = Session::get('user_id');
        $wave_id1 = session::get('wave_id1');
        $shopID = $request->input('shopID');
        $comment = $request->input('comment');
        $locationName = DB::table('assignshops')
            ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')
            ->join('locations', 'hierarchies.LID', '=', 'locations.id')
            ->where('assignshops.id', $shopID)
            ->value('locations.locationname');
        contests::create([
            'shop_id' =>  $shopID,
            'client_id' => $clientID,
            'wave_id' =>  $wave_id1,
            'branchName' => $locationName,
            'comentby' => $locationName,
            'contest' => $comment,
        ]);
        // $user = User::find($clientID);

        // $clientEmail = $user ? $user->emails : null;
        // $emails = assignshops::where('assignshops.id',  $shopID) // Filter for specific assign shop
        //     ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id') // Join with hierarchies table
        //     ->join('users', 'hierarchies.id', '=', 'users.locationID') // Join with users table
        //     ->pluck('users.email');
        // // Define recipients: always include a default email, and only add clientEmail if it exists
        // $recipients = ["aizasarfraz21@gmail.com"];
        // if ($clientEmail) {
        //     $recipients[] = $clientEmail;
        // }
        // // Email subject and message
        // $subject = "Branh Response Submitted";
        // $message = "A new response has been submitted by branch: " . $locationName . ". Please review it.";

        // // Sender email address
        // $fromEmail = $emails->isNotEmpty() ? $emails->first() : "aizasarfraz21@gmail.com"; // Ensure you always have a valid email
        // $fromName = $locationName;

        // // Send email notifications
        // foreach ($recipients as $recipient) {
        //     Mail::raw($message, function ($mail) use ($recipient, $subject, $fromEmail, $fromName) {
        //         $mail->to($recipient)
        //             ->subject($subject)
        //             ->from($fromEmail, $fromName); // Set the "From" address
        //     });
        // }

        return redirect()->back()->with('success', 'Comment submitted successfully!');
    }
    public function contest($id, $waveid)
    {
        // Fetch contests based on the shop_id and wave_id
        $contests = contests::where('shop_id', $id)
            ->where('wave_id', $waveid)
            ->get();

        // Return the contests as a JSON response
        return response()->json($contests);
    }

    public function updateCommentStatus(Request $request)
    {
        $contestId = $request->input('id');
        $status = $request->input('status');

        $contest = contests::find($contestId);

        if ($contest) {
            $contest->comentAcceptReject = $status;
            $contest->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function submitResponse(Request $request)
    {
        $clientID = Session::get('user_id'); // Assuming you're using session to get the client ID
        $contestId = $request->input('id');
        $clientReply = $request->input('clientReply');
        //  dd($clientID);
        // Get the contest details with proper joins
        $contestDetails = DB::table('contests')
            ->select(
                'waves.name as waveName',
                'contests.shop_id as shop_id',
                'contests.wave_id as wave_id',
                'contests.branchName as branchName',
                'users.name as clientname',
                'users.emails as clientemail',


            )
            ->join('assignshops', 'contests.shop_id', '=', 'assignshops.id')
            ->join('waves', 'contests.wave_id', '=', 'waves.id')
            ->join('users', function ($join) use ($clientID) {
                $join->on('users.id', '=', DB::raw($clientID));
            }) // Use DB::raw to ensure it's treated as a value, not a column
            ->where('contests.id', $contestId)
            ->first();

        // Insert new contest data
        contests::create([
            'shop_id' => $contestDetails->shop_id,
            'client_id' => $clientID,
            'wave_id' => $contestDetails->wave_id,
            'branchName' => $contestDetails->branchName,
            'comentby' => $contestDetails->clientname,
            'contest' => $clientReply,
            'clientReply' => 1,
            'comentAcceptReject' => 2,
        ]);

        // // Update the client reply status
        // DB::table('contests')
        //     ->where('id', $contestId)
        //     ->update(['clientReply' => 1]);

        // $emails = assignshops::where('id',  $contestDetails->shop_id) // Filter for specific assign shop
        //     ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id') // Join with hierarchies table
        //     ->join('users', 'hierarchies.id', '=', 'users.locationID') // Join with users table
        //     ->pluck('users.email');
        // $recipients = ["aizasarfraz21@gmail.com", $emails];

        // // Email subject and message
        // $subject = "Client Response Submitted";
        // $message = "A new response has been submitted by client: " . $contestDetails->clientname . " for branch: " . $contestDetails->branchName . ". Please review it.";

        // // Sender email address
        // $fromEmail =  $contestDetails->clientemail;
        // $fromName = $contestDetails->clientname;

        // // Send email notifications
        // foreach ($recipients as $recipient) {
        //     Mail::raw($message, function ($mail) use ($recipient, $subject, $fromEmail, $fromName) {
        //         $mail->to($recipient)
        //             ->subject($subject)
        //             ->from($fromEmail, $fromName); // Set the "From" address
        //     });
        // }

        return response()->json(['success' => true]);
    }

    public function submitCommentadmin(Request $request)
    {
        $clientID = Session::get('user_id');
        $wave_id1 = session::get('wave_id1');
        $shopID = $request->input('shopID');
        $comment = $request->input('comment');
        $locationName = DB::table('assignshops')
            ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')
            ->join('locations', 'hierarchies.LID', '=', 'locations.id')
            ->where('assignshops.id', $shopID)
            ->value('locations.locationname');
        contests::create([
            'shop_id' =>  $shopID,
            'client_id' => $clientID,
            'wave_id' =>  $wave_id1,
            'branchName' => $locationName,
            'comentby' => $locationName,
            'contest' => $comment,
        ]);
        // dd($request);

        return redirect()->back()->with('success', 'Comment submitted successfully!');
    }
    public function contestadmin($id, $waveid)
    {
        // Fetch contests based on the shop_id and wave_id
        $contests = contests::where('shop_id', $id)
            ->where('wave_id', $waveid)
            ->get();

        // Return the contests as a JSON response
        return response()->json($contests);
    }

    public function updateCommentStatusadmin(Request $request)
    {
        $contestId = $request->input('id');
        $status = $request->input('status');

        $contest = contests::find($contestId);

        if ($contest) {
            $contest->comentAcceptReject = $status;
            $contest->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function submitResponseadmin(Request $request)
    {
        $clientID = Session::get('user_id'); // Assuming you're using session to get the client ID
        $contestId = $request->input('id');
        $clientReply = $request->input('clientReply');
        //  dd($clientID);
        // Get the contest details with proper joins
        $contestDetails = DB::table('contests')
            ->select(
                'waves.name as waveName',
                'contests.shop_id as shop_id',
                'contests.wave_id as wave_id',
                'contests.branchName as branchName',
                'users.name as clientname',
                'users.emails as clientemail'
            )
            ->join('assignshops', 'contests.shop_id', '=', 'assignshops.id')
            ->join('waves', 'contests.wave_id', '=', 'waves.id')
            ->join('users', function ($join) use ($clientID) {
                $join->on('users.id', '=', DB::raw($clientID));
            }) // Use DB::raw to ensure it's treated as a value, not a column
            ->where('contests.id', $contestId)
            ->first();

        // Insert new contest data
        contests::create([
            'shop_id' => $contestDetails->shop_id,
            'client_id' => $clientID,
            'wave_id' => $contestDetails->wave_id,
            'branchName' => $contestDetails->branchName,
            'comentby' => $contestDetails->clientname,
            'contest' => $clientReply,
            'AdminReply' => 1,
            'comentAcceptReject' => 2,
        ]);

        // Update the client reply status
        DB::table('contests')
            ->where('id', $contestId)
            ->update(['AdminReply' => 1]);
        // $emails = assignshops::where('id',  $contestDetails->shop_id) // Filter for specific assign shop
        //     ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id') // Join with hierarchies table
        //     ->join('users', 'hierarchies.id', '=', 'users.locationID') // Join with users table
        //     ->pluck('users.email');
        // $recipients = [$contestDetails->clientemail, $emails];

        // // Email subject and message
        // $subject = "startex Response Submitted";
        // $message = "A new response has been submitted by startex: " . $contestDetails->clientname . " for branch: " . $contestDetails->branchName . ". Please review it.";

        // // Sender email address
        // $fromEmail =  "aizasarfraz21@gmail.com";
        // $fromName = "startex";

        // // Send email notifications
        // foreach ($recipients as $recipient) {
        //     Mail::raw($message, function ($mail) use ($recipient, $subject, $fromEmail, $fromName) {
        //         $mail->to($recipient)
        //             ->subject($subject)
        //             ->from($fromEmail, $fromName); // Set the "From" address
        //     });
        // }

        return response()->json(['success' => true]);
    }

    public function contestshow(Request $request)
    {

        // dd($request->all());
        Session::put('client_id', $request->id);
        return redirect()->back()->with('success', 'Comment submitted successfully!');
    }
}
