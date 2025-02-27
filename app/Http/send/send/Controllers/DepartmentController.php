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
use App\Models\DepartmentUser;
use Illuminate\Support\Facades\Session;

class DepartmentController extends Controller
{
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
