<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\Format;
use App\Models\wave;
use App\Models\Section;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash; // Import the Hash facade
use Auth;
use App\Models\Question; // Adjust based on your actual model

class SectionController extends Controller
{
    public function store(Request $request)
    {
        $sections = $request->input('sections');
        foreach ($sections as $sectionData) {
            $section = new Section();
            $section->name = $sectionData['name'];
            $section->formatID = $sectionData['formatID'];
            $section->save();

            // Handle questions and sub-questions as needed
        }

        return redirect()->back()->with('success', 'Sections and questions saved successfully!');
    }

    public function destroy($id)
    {
        // Find the section by ID and delete it
        $section = Section::find($id);
        if ($section) {
            $section->delete();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Section not found.'], 404);
        }
    }
    public function destroyquestion($id)
    {
        // Find the question by ID and delete it
        $question = Question::find($id);
        if ($question) {
            $question->delete();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Question not found.'], 404);
        }
    }
}
