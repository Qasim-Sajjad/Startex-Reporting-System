<?php



namespace App\Http\Controllers;



use illuminate\Http\Request;

use App\Models\User;

use Auth;

use Illuminate\Support\Facades\Session;

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



class DashboardController extends Controller

{

    public function dashboard()

    {
        // dd(122);
        // echo "sia";
        // exit();

        if (Auth::user()->role_id == 2) {



            return view('superadmin.dashboard', []);
        } elseif (Auth::User()->role_id == 1) {
            // dd(12);
            return view('admin.dashboard');
        } else {



            return redirect('login')->with('error', 'credential not available');
        }
    }

    public function getWaves($formatId)

    {
        // dd(1);
        // dd($formatId);
        // echo $formatId;

        $waves = DB::table('waves')->where('format_id', $formatId)->get();

        $wave = DB::table('waves')

            ->where('format_id', $formatId)

            ->orderBy('id', 'DESC')

            ->first();

        $ytdWaveId =  $wave->id;

        // if ($wave) {

        //     Session::put('YTD', $wave->id);

        // }

        return response()->json(['waves' => $waves, 'YTD' => $ytdWaveId]);
    }
}
