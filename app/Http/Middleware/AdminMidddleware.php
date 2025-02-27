<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use  Symfony\Component\HttpFoundation\Response;
use App\Models\GlobalUsersClients; // New model for the client_users table

use Auth;



class AdminMidddleware

{



    public function handle(Request $request, Closure $next): Response

    {


       $role = session('user_role');

        // Check if the role is "Super Admin"
        if ($role ==  "Client Admin") {
            return $next($request);
        } else {
            return redirect(url('login'));
        }
    }

    

}

