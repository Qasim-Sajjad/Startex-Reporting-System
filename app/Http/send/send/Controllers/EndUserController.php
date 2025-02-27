<?php

namespace App\Http\Controllers;

use App\Models\GlobalUsersClients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\ClientDatabaseManager;
use App\Models\ClientDBUser;
use App\Models\Client;

class EndUserController extends Controller
{
    /**
     * User Login
     */
    // public function login(Request $request)
    // {
       
    //     $credentials = $request->validate([
    //         'email' => 'required|string|email',
    //         'password' => 'required|string|min:8',
    //     ]);
      
    //     $user = GlobalUsersClients::where('email', $credentials['email'])->first();
       
    //     if (!$user || !Hash::check($credentials['password'], $user->password)) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     ClientDatabaseManager::setConnection($user->database_name);

    //     $token = $user->createToken('end_user_token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Login successful',
    //         'user' => [
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'client_id' => $user->client_id,
    //         ],
    //         'token' => $token,
    //     ], 200);
    // }
    
    public function update(Request $request)
    {
        $user = auth('sanctum')->user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',
            'password' => 'sometimes|string|min:8',
        ]);
    
        // Update user in the main database
        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'password' => isset($validated['password']) ? bcrypt($validated['password']) : $user->password,
        ]);
    
        // Switch to the client-specific database using database_name from the user
        if ($user->database_name) {
            
            ClientDatabaseManager::setConnection($user->database_name);
            
    
            // Update the user in the client-specific database
            $clientDBUser = ClientDBUser::find($user->id);
           
            if ($clientDBUser) {
                $clientDBUser->update([
                    'name' => $validated['name'] ?? $clientDBUser->name,
                    'email' => $validated['email'] ?? $clientDBUser->email,
                    'password' => isset($validated['password']) ? bcrypt($validated['password']) : $clientDBUser->password,
                ]);
            }
        }
    
        return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
    }

    /**
     * Fetch User Details
     */
    public function profile()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json(['user' => $user], 200);
    }

    /**
     * Logout User
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout successful'], 200);
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);
      

        $client = Client::where('email', $credentials['email'])->first();

        if ($client) {
          
         
            if (!Hash::check($credentials['password'], $client->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
         
          
            if ($client->role == 'Client Admin') {
              
             
                $token = $client->createToken('client_auth_token')->plainTextToken;
                session(['client_database' => $client->database_name]);

                return response()->json([
                    'message' => 'Client Admin login successful',
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'database_name' => $client->database_name,
                    ],
                    'token' => $token,
                ], 200);
            }

        }

        $user = GlobalUsersClients::where('email', $credentials['email'])->first();
      

        if ($user) {
          
            
            if (!Hash::check($credentials['password'], $user->password)) {
              
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

          
            if ($user->role == 'User') {

                $token = $user->createToken('end_user_token')->plainTextToken;

                return response()->json([
                    'message' => 'End-user login successful',
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'client_id' => $user->client_id,
                    ],
                    'token' => $token,
                ], 200);
            }
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
