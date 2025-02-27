<?php

namespace App\Http\Controllers;

use App\Models\GlobalUsersClients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\ClientDatabaseManager;
use App\Models\ClientDBUser;
use App\Models\Client;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\ResetPasswordMail;

class EndUserController extends Controller
{
    public function update(Request $request)
    {
        $user = auth('sanctum')->user();
    
        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }
    
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',
            'password' => 'sometimes|string|min:8',
        ]);
    
        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'password' => isset($validated['password']) ? bcrypt($validated['password']) : $user->password,
        ]);
    
        if ($user->database_name) {
            ClientDatabaseManager::setConnection($user->database_name);
    
            $clientDBUser = ClientDBUser::find($user->id);
            if ($clientDBUser) {
                $clientDBUser->update([
                    'name' => $validated['name'] ?? $clientDBUser->name,
                    'email' => $validated['email'] ?? $clientDBUser->email,
                    'password' => isset($validated['password']) ? bcrypt($validated['password']) : $clientDBUser->password,
                ]);
            }
        }
    
        
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, // Include role
            ],
            'status_code' => 200,
            'message' => 'User updated successfully',
            'success' => true,
        ], 200);
    }

    public function profile()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }
        ClientDatabaseManager::setConnection($user->database_name);
        $clientdbUser = ClientDBUser::where('email', $user->email)->first();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $clientdbUser->name,
                'email' => $clientdbUser->email,
                'role' => $user->role, // Include role
            ],
            'status_code' => 200,
            'message' => 'Profile fetched successfully',
            'success' => true,
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
       
        $user->update(['device_token' => null]);
    
        $user->currentAccessToken()->delete();

        return response()->json([
            'data' => null,
            'status_code' => 200,
            'message' => 'Logout successful',
            'success' => true,
        ], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
            'device_token' => 'nullable|string', 
        ]);
    
        $deviceToken = $request->input('device_token'); 
    
        $client = Client::where('email', $credentials['email'])->first();
    
        if ($client) {
            if (!Hash::check($credentials['password'], $client->password)) {
                return response()->json([
                    'data' => null,
                    'status_code' => 401,
                    'message' => 'Invalid credentials',
                    'success' => false,
                ], 401);
            }
    
            if ($client->role == 'Client Admin') {
                $token = $client->createToken('client_auth_token')->plainTextToken;
                session(['client_database' => $client->database_name]);
                session(['user_role' => $client->role]);
                session(['user_id' => $client->id]); 
             
    
                return response()->json([
                    'data' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'role' => $client->role,
                        'database_name' => $client->database_name,
                        'token' => $token,
                        'device_token' => $deviceToken, 
                    ],
                    'status_code' => 200,
                    'message' => 'Client Admin login successful',
                    'success' => true,
                ], 200);
            }
        }
    
        $user = GlobalUsersClients::where('email', $credentials['email'])->first();
    
        if ($user) {
            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'data' => null,
                    'status_code' => 401,
                    'message' => 'Invalid credentials',
                    'success' => false,
                ], 401);
            }
    
            if ($user->role == 'User' || $user->role == 'EndUser') {
                $token = $user->createToken('end_user_token')->plainTextToken;
                ClientDatabaseManager::setConnection($user->database_name);
                $clientdbUser = ClientDBUser::where('email', $user->email)->first();    
                session(['user_role' => $clientdbUser->role]);
                session(['user_id' => $clientdbUser->id]); 
              
    
                return response()->json([
                    'data' => [
                        'id' => $user->id,
                        'name' => $clientdbUser->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'client_id' => $user->client_id,
                        'token' => $token,
                        'device_token' => $deviceToken, // Include device token in the response
                    ],
                    'status_code' => 200,
                    'message' => 'End-user login successful',
                    'success' => true,
                ], 200);
            }
        }
    
        return response()->json([
            'data' => null,
            'status_code' => 401,
            'message' => 'Invalid credentials',
            'success' => false,
        ], 401);
    }
    
    public function forgotPassword(Request $request)
    {
     
        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);

            $user = GlobalUsersClients::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'data' => null,
                    'status_code' => 404,
                    'message' => 'User not found',
                    'success' => false,
                ], 404);
            }

            // Generate reset token
            $token = Str::random(60);
            
            // Save the token
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            // Create reset link
            $resetLink = url("/api/reset-password?token={$token}&email=" . urlencode($user->email));

            // Send email
            Mail::to($user->email)->send(new ResetPasswordMail($resetLink));

            return response()->json([
                'data' => null,
                'status_code' => 200,
                'message' => 'Password reset link has been sent to your email',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Forgot password failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to process forgot password request',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Get the latest token record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'data' => null,
                    'status_code' => 400,
                    'message' => 'No password reset request found. Please use forgot password first.',
                    'success' => false,
                ], 400);
            }

            // Check if token is expired (24 hours)
            if (now()->diffInHours(Carbon::parse($resetRecord->created_at)) > 24) {
                // Delete expired token
                DB::table('password_reset_tokens')
                    ->where('email', $validated['email'])
                    ->delete();

                return response()->json([
                    'data' => null,
                    'status_code' => 400,
                    'message' => 'Password reset link has expired. Please request a new one.',
                    'success' => false,
                ], 400);
            }

            $user = GlobalUsersClients::where('email', $validated['email'])->first();
            $client = Client::where('email', $validated['email'])->first();

            if ($user) {
                // Update in main database
                $user->update(['password' => Hash::make($validated['password'])]);

                // Update in client database if exists
                if ($user->database_name) {
                    ClientDatabaseManager::setConnection($user->database_name);
                    $clientDBUser = ClientDBUser::find($user->id);
                    if ($clientDBUser) {
                        $clientDBUser->update([
                            'password' => Hash::make($validated['password'])
                        ]);
                    }
                }
            } elseif ($client) {
                // Update client in main database
                $client->update(['password' => Hash::make($validated['password'])]);

                // Update in client's own database
                if ($client->database_name) {
                    ClientDatabaseManager::setConnection($client->database_name);
                    DB::table('users')->where('email', $validated['email'])
                        ->update(['password' => Hash::make($validated['password'])]);
                }
            }

            // Delete the used token
            DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->delete();

            return response()->json([
                'data' => null,
                'status_code' => 200,
                'message' => 'Password reset successfully',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Reset password failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null
            ]);

            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to reset password',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showResetForm(Request $request)
    {
        try {
            $email = $request->query('email');
            $token = $request->query('token');

            if (!$email || !$token) {
                return view('auth.reset-password-error', [
                    'message' => 'Invalid reset password link.'
                ]);
            }

            // Verify token exists and is valid
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first(); // Get the latest token record

            if (!$resetRecord) {
                return view('auth.reset-password-error', [
                    'message' => 'Invalid or expired reset token.'
                ]);
            }

            // Check if token is expired (24 hours)
            if (now()->diffInHours(Carbon::parse($resetRecord->created_at)) > 24) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();

                return view('auth.reset-password-error', [
                    'message' => 'Reset token has expired. Please request a new one.'
                ]);
            }

            // Pass the fresh token from database to the view
            return view('auth.reset-password', [
                'email' => $email,
                'token' => $resetRecord->token // Use the fresh token from database
            ]);

        } catch (\Exception $e) {
            \Log::error('Show reset form failed', [
                'error' => $e->getMessage(),
                'email' => $request->query('email') ?? null
            ]);

            return view('auth.reset-password-error', [
                'message' => 'An error occurred. Please try again.'
            ]);
        }
    }
}
