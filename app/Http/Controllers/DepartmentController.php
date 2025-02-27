<?php

namespace App\Http\Controllers;



use Illuminate\Support\Str;

use Illuminate\Http\Request;

use App\Models\User; // Import User model

use Illuminate\Support\Facades\Hash; // Import Hash facade

use App\Models\Format;

use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\HierarchyName;
use App\Models\HierarchyLevel;
use App\Models\Hierarchy;
use App\Models\Location;

use Illuminate\Support\Facades\DB;

use App\Models\assignprojects;

use App\Models\waves;

use App\Models\assignshops;
use App\Models\Department;
use App\Models\DepartmentUser;
use Illuminate\Support\Facades\Session;
use App\Services\ClientDatabaseManager;
use App\Models\GlobalUsersClients; // New model for the client_users table

class DepartmentController extends Controller
{
      public function getDepartments(Request $request)
      {
        
            $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);

    // Corrected line: Added missing semicolon
    $departments = Department::all();
            $hierarchy = HierarchyName::all();
    return view('admin.departmentanduser', [
    'departments'=>$departments,
             'hierarchy'=>$hierarchy

    ]);
      }
   public function userDepartment(Request $request)
   {
   
  // dd($request->all());
      // Validate the incoming request data
    $validatedData = $request->validate([
        'deparment' => 'required|integer', // Ensure department is valid
        'username' => 'required|string|unique:globalusersclients,email',
        'password' => 'required|string|min:8',
        'hierarchy' => 'required|integer',
    ]);

    // Step 1: Create a new user in the `globalusersclients` table
    $user = GlobalUsersClients::create([
        'client_id' => $validatedData['deparment'], // Assuming department refers to client_id
        'role' => 'User', // Or any other role you want to assign
        'email' => $validatedData['username'],
        'password' => Hash::make($validatedData['password']),
        'database_name' => 'client_' . $validatedData['deparment'], // Example logic
    ]);
     $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
    // Step 2: Assign the user to the hierarchy
    $hierarchy = hierarchies::find($validatedData['hierarchy']);

    if ($hierarchy) {
        $hierarchy->update([
            'client_dbusers_id' => $user->id,
        ]);
    } else {
        return response()->json([
            'message' => 'Hierarchy not found.',
        ], 404);
    }

    return response()->json([
        'message' => 'User created and assigned to hierarchy successfully.',
    ]);
   
   }
  public function createDepartment(Request $request)
{
            $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    // Save the new department
    $department = new Department();
    $department->name = $request->name;
    $department->save();

    return response()->json([
        'success' => true,
        'department' => $department
    ]);
}

    public function savenewdepartment(Request $request)
    {

        $user_id = Session::get('user_id');



        $validated = $request->validate([
            'department' => 'required|string|max:255',
        ]);

        // Save the new department
        $department = new Department();
        $department->name = $validated['department'];
        $department->user_id = $user_id;  // Store the user_id in the department record

        $department->save();

        // Return a response (optional)
        return response()->json(['message' => 'Department saved successfully']);
    }
    public function saveUser(Request $request)
    {
        // dd($request->all());
        // Validate incoming data
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8', // Adjust password validation rules as needed
            'department_id' => 'required|exists:departments,id',
        ]);

        // Hash the password
        $hashedPassword = Hash::make($request->password);

        // Save the user data in the department's user table
        $user = new DepartmentUser();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->password = $hashedPassword;
        $user->department_id = $request->department_id; // Link the user with the department
        $user->save();

        return redirect()->back()->with('success', 'User created successfully');
    }
}
