<?php



namespace App\Http\Controllers;



use Illuminate\Support\Str;

use Illuminate\Http\Request;

use App\Models\User; // Import User model

use Illuminate\Support\Facades\Hash; // Import Hash facade

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
use App\Models\Department;

use Illuminate\Support\Facades\Session;



class UserManagementController extends Controller

{

    public function usermanagement(Request $request)

    {

        $parentName = Session::get('parentName');
        $manager = User::where('parentName', $parentName)
            ->where('is_role', 2)
            ->get();
        $shopper = User::where('parentName', $parentName)
            ->where('is_role', 3)
            ->get();
        return view('superadmin.usermanagement', [
            'manager' => $manager,
            'shopper' =>  $shopper,
        ]);
    }


    public function clientedit(Request $request)

    {
        $parentName = Session::get('parentName');
        $user_id = Session::get('user_id');

        // Retrieve users
        $users = User::where('parentName', $parentName)
            ->where('is_role', 4)
            ->get();

        // Retrieve departments (example)
        $departments = Department::where('user_id', $user_id)->get();

        return view('superadmin.clientcreateedit', compact('users', 'departments'));
    }
    public function clientedit1(Request $request)

    {

        $parentName = Session::get('parentName');
        $users = User::where('is_role', 10)
            ->get();
        return view('admin.clientcreateedit', compact('users'));
    }

    public function HierarchyUsers(Request $request)

    {
        $parentName = Session::get('parentName');

        $users = User::where('is_role', '=', '4')



            ->where('parentName', '=', $parentName)



            ->pluck('name', 'id');

        return view('superadmin.HierarchyUsers', compact('users'));
    }

    public function postLevelFormat(Request $request)

    {

        // Fetch format details using the format_id from the request

        $format = Format::where('id', $request->format_id)->first();



        // Ensure $format exists before accessing its properties

        if ($format) {

            // Fetch hierarchy level using the assignHID from the format and level_id from the request

            $hierarchyLevel = HierarchyLevels::where('HID', $format->assignHID)

                ->where('level', $request->level_id)

                ->first();



            // Ensure $hierarchyLevel exists before further processing

            if ($hierarchyLevel) {

                // Fetch hierarchies based on levelID from hierarchyLevel

                $hierarchies = Hierarchies::where('levelID', $hierarchyLevel->id)->get();



                // Initialize an array to store results

                $results = [];



                // Loop through each hierarchy

                foreach ($hierarchies as $hierarchy) {

                    // Fetch location details using LID from hierarchy

                    $locations = Locations::where('id', $hierarchy->LID)->first();



                    // Check if locations exist before accessing properties

                    if ($locations) {

                        // Add required data to results array

                        $results[] = [

                            'location_name' => $locations->locationname,

                            'hierarchy_id' => $hierarchy->id,

                            'format_id' => $request->format_id,

                            'level_id' =>  $hierarchyLevel->level,

                        ];
                    }
                }



                // Return the results array

                return $results;
            } else {

                return "Hierarchy level not found : level = {$request->level_id}";
            }
        } else {

            return "Format not found ";
        }
    }

    //     public function saveChanges(Request $request)

    //     {

    //         $usernames = $request->input('username');

    //         $passwords = $request->input('password');

    //         $emails = $request->input('emails');

    //         $hierarchy_ids = $request->input('hierarchy_id');

    //         $format_ids = $request->input('format_id');

    // // dd( $hierarchy_ids );

    //         foreach ($hierarchy_ids as $index => $hierarchy_id) {

    // // dd( $hierarchy_id[$index]);

    //             $user = new User;

    //             $user->email = $usernames[$index];

    //             $user->password = Hash::make($passwords[$index]);

    //             $user->emails = $emails[$index];

    //             $user->remember_token = Str::random(50);

    //             $user->locationID =   $hierarchy_id[$index];

    //             $user->format_id = $format_ids[$index];

    //             $user->save();

    //         }



    //         return response()->json(['success' => true]);

    //     }

    public function saveChanges(Request $request)

    {

        // Retrieve inputs

        $usernames = $request->input('username');

        $passwords = $request->input('password');

        $emails = $request->input('emails');

        $hierarchy_ids = $request->input('hierarchy_id');

        $format_ids = $request->input('format_id');

        $level_id = $request->input('level_id');





        // Debugging: Check input data

        // dd($usernames, $passwords, $emails, $hierarchy_ids, $format_ids);



        // Validate input lengths

        $count = count($emails);

        if ($count !== count($passwords) || $count !== count($emails) || $count !== count($hierarchy_ids) || $count !== count($format_ids)) {

            return response()->json(['error' => 'Input arrays must have the same length.'], 400);
        }



        // Insert users

        for ($index = 0; $index < $count; $index++) {
            // Check if the email or password is empty for this index
            if (empty($emails[$index]) || empty($passwords[$index])) {
                // Skip this iteration if either email or password is empty
                continue;
            }
            $user = new User;
            // $user->email = $usernames[$index];  // Save email as username
            $user->email = $emails[$index];  // Save email as username

            $user->emails = $emails[$index];

            $user->password = Hash::make($passwords[$index]);

            $user->remember_token = Str::random(50);

            $user->locationID = (int) $hierarchy_ids[$index]; // Cast to integer if needed

            $user->format_id = (int) $format_ids[$index]; // Cast to integer if needed

            $user->level =  $level_id[$index];
            $user->save();
        }



        return response()->json(['success' => true]);
    }

    public function destroy($id)

    {

        $user = User::findOrFail($id);

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }
    public function destroy1($id)

    {

        $user = User::findOrFail($id);

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function update(Request $request)

    {



        $userId = $request->userId;

        $name = $request->name;

        $email = $request->email;

        $password = $request->pass; // Assuming 'pass' is the name of your password field



        // Hash the password

        $hashedPassword = Hash::make($password);



        // Find the user by ID

        $user = User::find($userId);



        if ($user) {

            // Update user data

            $user->name = $name;

            $user->email = $email;

            $user->password = $hashedPassword; // Save the hashed password



            $user->save();



            return response()->json(['success' => 'User updated successfully']);
        } else {

            return response()->json(['error' => 'User not found'], 404);
        }
    }
    public function update1(Request $request)

    {



        $userId = $request->userId;

        $name = $request->name;

        $email = $request->email;

        $password = $request->pass; // Assuming 'pass' is the name of your password field



        // Hash the password

        $hashedPassword = Hash::make($password);



        // Find the user by ID

        $user = User::find($userId);



        if ($user) {

            // Update user data

            $user->name = $name;

            $user->email = $email;

            $user->password = $hashedPassword; // Save the hashed password



            $user->save();



            return response()->json(['success' => 'User updated successfully']);
        } else {

            return response()->json(['error' => 'User not found'], 404);
        }
    }
}
