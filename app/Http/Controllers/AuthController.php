<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use Illuminate\Support\Str;

use App\Models\User;

use Illuminate\Support\Facades\Hash; // Import the Hash facade

use Auth;



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
use App\Services\ClientDatabaseManager;

use App\Models\GlobalUsersClients; // New model for the client_users table
use App\Models\Client;


use App\Models\Hierarchy;



class AuthController extends Controller

{

    public function login()

    {

        return view('auth.login');
    }


    public function login_post(Request $request)
    {   
      // Ensure validation for email and password fields
        $request->validate([
            'username' => 'required|email',
            'password' => 'required',
        ]);
      
        // Check for user1 (GlobalUsersClients)
        $user1 = GlobalUsersClients::where('email', $request->username)->first();

        if ($user1 && Hash::check($request->password, $user1->password)) {
           
            // Store session and redirect based on role for user1
            session(['user_role' => $user1->role]);
            session(['user_id' => $user1->id]);
            session(['client_database' => $user1->database_name]);

            if ($user1->role === "Client Admin") {
                return redirect()->intended('admin/dashboard');
            } else {
                return redirect('login')->with('error', 'Credential not available for this role.');
            }
        }
        
        // Check for user2 (User model)
        $user2 = User::where('email', $request->username)->first();
        if ($user2 && Hash::check($request->password, $user2->password)) {
            // Store session and redirect based on role for user2
            session(['user_role' => $user2->role]);
            session(['user_id' => $user2->id]);
            session(['client_database' => $user2->database_name]);
            
            if ($user2->role === "Super Admin") {
                return redirect()->intended('superadmin/dashboard');
            } else {
                return redirect('login')->with('error', 'Credential not available for this role.');
            }
        }   
        return redirect('login')->with('error', 'Invalid email or password.');
    }

    public function logout(Request $request)
    {
        // Clear session data
        Session::flush();  // Clears all session data

        // Log out the user (invalidate their authentication session)
        Auth::logout();

        // Redirect to the login page
        return redirect('/login');
    }

    public function createuser(Request $request)

    {

        // dd($request->all());
        $parentName = Session::get('parentName');
        // dd($parentName);
        $existingUser = User::where('email', $request->emails)->first();

        if ($existingUser) {
            // If email already exists, redirect back with an error message
            // return redirect()->back()->with('error', 'Email already in use. Please try another email.');
            return back()->with('success', 'Email already in use. Please try another email.');
        } else {
      $id=  Session::get('user_id');
         $client = Client::create([
            'name' => $request->name,
            'email' => $request->emails,
            'industry' => $request->industry,
            'password' => bcrypt($request->password),
            'address' => $request->address,
            'status' => 'Active',
            'user_id' => $id, // Assuming a Super Admin creates the client
        ]);

        
        $databaseName = 'client_' . $client->id;
        $client->update(['database_name' => $databaseName]);

                $client = GlobalUsersClients::create([
            'email' => $client->email,
            'password' => bcrypt($request->password),
            'role' => 'Client Admin',
            'client_id' => $client->id,
            'database_name' => $databaseName,
        ]);
        ClientDatabaseManager::createDatabase($databaseName);
        ClientDatabaseManager::setConnection($databaseName);

        ClientDatabaseManager::runMigrations($databaseName);
        ClientDatabaseManager::seedClientDatabase($databaseName);

    return back()->with('success', 'User successfully created!');
        }
    }
    public function createuser3(Request $request)

    {

        $parentName = Session::get('parentName');
        // dd($parentName);
        $existingUser = User::where('email', $request->emails)->first();

        if ($existingUser) {
            // If email already exists, redirect back with an error message
            // return redirect()->back()->with('error', 'Email already in use. Please try another email.');
            return back()->with('success', 'Email already in use. Please try another email.');
        } else {

            $user = new User;

            $user->name = trim($request->name);

            $user->email = $request->emails;

            $user->emails = $request->emails;

            $user->password = Hash::make($request->password);

            $user->role_id = trim($request->role);

            $user->parentName = $parentName;

            $user->remember_token = Str::random(50);

            $user->save();
            session()->put('clientcreate_id', $user->id);
            session()->forget('currentStep');
            session()->put('currentStep', 8);

            // return redirect()->back()->with('success', 'hierarchy stored successfully!');
            return redirect()->route('createhierarchy')->with('success', '');
            // return redirect()->back()->with('success', 'User created successfully')->withFragment('section-8');
        }
    }
    public function createuser4(Request $request)

    {

        $parentName = Session::get('parentName');
        //  dd($parentName);
        $existingUser = User::where('email', $request->emails)->first();

        if ($existingUser) {
            // If email already exists, redirect back with an error message
            // return redirect()->back()->with('error', 'Email already in use. Please try another email.');
            return back()->with('success', 'Email already in use. Please try another email.');
        } else {

            $user = new User;

            $user->name = trim($request->name);

            $user->email = $request->emails;

            $user->emails = $request->emails;

            $user->password = Hash::make($request->password);

            $user->role_id = trim($request->role);

            $user->parentName = $parentName;

            $user->remember_token = Str::random(50);

            $user->save();


            // return redirect()->back()->with('success', 'hierarchy stored successfully!');
            // return redirect()->route('createhierarchy')->with('success', '');
            return redirect()->back()->with('success', 'User created successfully');
        }
    }
}
