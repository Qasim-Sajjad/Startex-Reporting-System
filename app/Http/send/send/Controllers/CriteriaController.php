<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\Format;
use App\Models\wave;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash; // Import the Hash facade
use Auth;


class CriteriaController extends Controller
{
    // Method for displaying the criteria page
    public function index()
    {
        $parentName = Session::get('parentName');

        $users = User::where('is_role', '=', '4')

            ->where('parentName', '=', $parentName)

            ->pluck('name', 'id');
        return view('superadmin.createcriteria', compact('users'));
    }
    public function storeCriteria(Request $request)
    {
        // Validate the request if needed
        $validatedData = $request->validate([
            'format_id' => 'required|numeric',
            'option' => 'required|string',
            // Add validation rules for other fields as needed
        ]);

        // Extract data from the request
        $formatId = $validatedData['format_id'];
        $option = $validatedData['option'];

        if ($option === 'default') {
            // Define default criteria
            $defaultCriteria = [
                [
                    'label' => 'Good',
                    'operator' => '>',
                    'range1' => 80,
                    'range2' => 0,
                    'color' => '#00b050'
                ],
                [
                    'label' => 'Average',
                    'operator' => 'b/w',
                    'range1' => 65,
                    'range2' => 75,
                    'color' => '#ff9933',
                    // 'color' => '#ffdd47'
                ],
                [
                    'label' => 'Poor',
                    'operator' => '<',
                    'range1' => 65,
                    'range2' => 0,
                    'color' => '#ffcccc'
                ]
            ];

            // Save default criteria to the database
            $this->saveCriteria($formatId, $defaultCriteria);
        } else if ($option === 'custom') {
            // Extract custom criteria data
            $labels = $request->input('labels');
            $operators = $request->input('operators');
            $colors = $request->input('colors');
            $ranges = $request->input('ranges');
            $ranges2 = $request->input('ranges_2');

            $customCriteria = [];
            foreach ($labels as $index => $label) {
                $customCriteria[] = [
                    'label' => $label,
                    'operator' => $operators[$index],
                    'color' => $colors[$index],
                    'range1' => $ranges[$index],
                    'range2' => $ranges2[$index],
                ];
            }

            // Save custom criteria to the database
            $this->saveCriteria($formatId, $customCriteria);
        } else {
            // Handle other options if needed
        }

        // Return a response indicating success
        return redirect()->route('createwave')->with('success', '');
    }
    private function saveCriteria($formatId, $criteria)
    {
        foreach ($criteria as $criterion) {
            Criteria::create([
                'format_id' => $formatId,
                'label' => $criterion['label'],
                'operator' => $criterion['operator'],
                'range1' => $criterion['range1'],
                'range2' => $criterion['range2'] ?? null,
                'color' => $criterion['color']
            ]);
        }
    }

    public function getCriteria(Request $request)
    {
        $formatId = $request->input('format_id');

        // Fetch criteria from the database based on $formatId
        $criteria = Criteria::where('format_id', $formatId)->get();

        return response()->json($criteria);
    }

    public function updateCriteria(Request $request)
    {

        $criterion = Criteria::find($request->criterion_id);
        $criterion->label = $request->label;
        $criterion->operator = $request->operator;
        $criterion->range1 = $request->range1;
        $criterion->range2 = $request->range2;
        $criterion->color = $request->color;
        $criterion->save();

        return response()->json(['success' => true]);
    }
    public function deleteCriteria(Request $request)
    {

        $criterionId = $request->input('id');
        Criteria::findOrFail($criterionId)->delete();

        return response()->json(['success' => 'Criterion deleted successfully.']);
    }
}
