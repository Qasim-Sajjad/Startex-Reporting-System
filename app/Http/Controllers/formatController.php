<?php



namespace App\Http\Controllers;



use App\Models\Format;
use App\Models\waves;

use App\Models\Section;

use App\Models\Question;

use App\Models\Option;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use App\Models\User;

use Illuminate\Support\Facades\Hash; // Import the Hash facade

use App\Models\Criteria;
use Illuminate\Support\Facades\Session;

use Auth;
use Illuminate\Support\Facades\DB;


use App\Models\assignshops;

use App\Models\Keyword;
use App\Models\QOption;
use App\Services\ClientDatabaseManager;
use App\Models\Process;

class formatController extends Controller

{


    public function store(Request $request)
    {
        // // Validate the incoming request data
        // $request->validate([
        //     'user_id' => 'required|integer',
        //     'name' => 'required|string|max:255',
        // ]);

        // Cast user_id to integer
          $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
        $userId = (int) $request->input('user_id');

        // Create a new format for the user
        $format = Format::create([
            'process_id' => $userId,      // Assign user_id to the format
            'name' => $request->input('name'), // Assign name to the format
        ]);
        session()->put('clientFormatId', $format->id);

        return response()->json(['success' => true, 'format' => $format]);
    }

    public function updateMandatoryField(Request $request)
    {
        $question = Question::find($request->question_id);

        if ($question) {
            $field = $request->field;
            $value = $request->value;

            if (in_array($field, ['mandatory_question', 'mandatory_audio', 'mandatory_text', 'mandatory_video', 'mandatory_picture'])) {
                $question->$field = $value;
                $question->save();

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false], 400);
    }
    public function updateSectionMandatoryFields(Request $request)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'mandatoryFields' => 'required|array',
            'mandatoryFields.mandatory' => 'boolean',
            'mandatoryFields.audio' => 'boolean',
            'mandatoryFields.comment' => 'boolean',
            'mandatoryFields.video' => 'boolean',
            'mandatoryFields.picture' => 'boolean',
        ]);

        // Find the section by ID
        $section = Section::find($validated['section_id']);

        // Update the mandatory fields
        $section->update([
            'mandatory' => $validated['mandatoryFields']['mandatory'],
            'audio' => $validated['mandatoryFields']['audio'],
            'comment' => $validated['mandatoryFields']['comment'],
            'video' => $validated['mandatoryFields']['video'],
            'picture' => $validated['mandatoryFields']['picture'],
        ]);

        // Return success response
        return response()->json(['success' => true]);
    }

    public function copyFormat(Request $request)
    {
        $formatId = $request->input('format_id');
        $originalFormat = Format::find($formatId);

        if (!$originalFormat) {
            return response()->json(['success' => false, 'message' => 'Format not found.']);
        }

        // Duplicate the format
        $newFormat = $originalFormat->replicate();
        $newFormat->name = $originalFormat->name . ' - Copy'; // Add "Copy" to the name to distinguish it
        $newFormat->save();

        // Duplicate each section associated with the original format
        foreach ($originalFormat->sections as $originalSection) {
            $newSection = $originalSection->replicate();
            $newSection->format_id = $newFormat->id; // Link the new section to the new format
            $newSection->save();

            // Duplicate each question associated with the original section
            foreach ($originalSection->questions as $originalQuestion) {
                $newQuestion = $originalQuestion->replicate();
                $newQuestion->section_id = $newSection->id; // Link the new question to the new section
                $newQuestion->save();

                // Duplicate each option associated with the original question
                foreach ($originalQuestion->options as $originalOption) {
                    $newOption = $originalOption->replicate();
                    $newOption->question_id = $newQuestion->id; // Link the new option to the new question
                    $newOption->save();
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Format copied successfully with all sections, questions, and options!']);
    }



    public function createformat(Request $request)

    {
        $clientid = session::get(key: 'user_id');
        $database_name = session::get('client_database');
        $connection = ClientDatabaseManager::setConnection($database_name);
        $process = Process::get();
        return view('admin.createformat', compact('process'));
    }

    public function create($user_id)

    {

        // Find the user by ID

        $user = User::find($user_id);



        // Check if user exists

        if (!$user) {

            return redirect()->back()->with('error', 'User not found.');
        }



        // Pass the user to the view

        return view('superadmin.formatcreatepage', compact('user'));
    }

    public function updateFormatName(Request $request)

    {



        $userId = $request->input('user_id');

        $newFormatName = $request->input('format_name');



        // Find the user by ID

        $user = User::find($userId);



        if (!$user) {

            return response()->json(['error' => 'User not found.'], 404);
        }



        // Update or create the format record

        $format = Format::create([

            'user_id' => $userId,

            'name' => $newFormatName

        ]);





        return (['success' => true, 'format_name' => $newFormatName, 'format_id' => $format->id]);
    }

    public function saveSectionsAndQuestions(Request $request)

    {

        // dd($request->all());



        $formatID = $request->format_id;

        $sectionIndex = 0; // Initialize section index



        // Iterate through each section and save

        foreach ($request->input('sections') as $sectionData) {

            // Update or create section with incremented orderby

            $section = Section::create([

                'section_name' => $sectionData['section_name'],

                'format_id' => $formatID,

                'orderby' => $sectionIndex,

            ]);



            // Increment section index for next section

            $sectionIndex++;



            // Check if questions exist for the section

            if (isset($sectionData['questions']) && is_array($sectionData['questions']) && count($sectionData['questions']) > 0) {

                $questionIndex = 0; // Initialize question index



                foreach ($sectionData['questions'] as $questionData) {

                    // Create question with incremented orderby

                    $question = Question::create([

                        'section_id' => $section->id,

                        'question_name' => $questionData['question_name'],

                        'orderby' => $questionIndex,

                        'guideline'   => $questionData['question_guide'],

                        'type'   => $questionData['question_type'],

                        'score' => $questionData['question_score'],



                    ]);



                    // Increment question index for next question

                    $questionIndex++;



                    // Check if options exist for the question

                    if (isset($questionData['options']) && is_array($questionData['options']) && count($questionData['options']) > 0) {

                        foreach ($questionData['options'] as $optionData) {

                            // Create option for the question

                            Option::create([

                                'question_id' => $question->id,

                                'text' => $optionData['label'],

                                'score' => $optionData['score'],

                            ]);
                        }
                    }



                    // Handle keywords

                    if (isset($questionData['keywords']) && is_array($questionData['keywords'])) {

                        // Extract the labels from the keywords array

                        $keywordLabels = array_map(function ($keyword) {

                            return $keyword['label'];
                        }, $questionData['keywords']);



                        // Convert the array of labels into a comma-separated string

                        $keywordsString = implode(',', $keywordLabels);



                        // Debugging: Ensure $keywordsString is a string

                        // dd($keywordsString);



                        // Create a new keyword record

                        Keyword::create([

                            'question_id' => $question->id,

                            'keywords' => $keywordsString

                        ]);
                    }
                }
            }
        }



        // Return response (if needed)

        return response()->json(['redirect' => url('superadmin/createformat')]);
    }

    public function editsavesectionsandquestions(Request $request)

    {



        $formatID = $request->format_id;

        $sectionIndex = 0; // Initialize section index



        // Iterate through each section and save

        foreach ($request->input('sections') as $sectionData) {

            // Update or create section with incremented orderby

            $section = Section::create([

                'section_name' => $sectionData['section_name'],

                'format_id' => $formatID,

                'orderby' => $sectionIndex,

            ]);



            // Increment section index for next section

            $sectionIndex++;



            // Check if questions exist for the section

            if (isset($sectionData['questions']) && is_array($sectionData['questions']) && count($sectionData['questions']) > 0) {

                $questionIndex = 0; // Initialize question index



                foreach ($sectionData['questions'] as $questionData) {

                    // Create question with incremented orderby

                    $question = Question::create([

                        'section_id' => $section->id,

                        'question_name' => $questionData['question_name'],

                        'orderby' => $questionIndex,

                        'guideline'   => $questionData['question_guide'],

                        'type'   => $questionData['question_type'],

                        'score' => $questionData['question_score'],



                    ]);



                    // Increment question index for next question

                    $questionIndex++;



                    // Check if options exist for the question

                    if (isset($questionData['options']) && is_array($questionData['options']) && count($questionData['options']) > 0) {

                        foreach ($questionData['options'] as $optionData) {

                            // Create option for the question

                            Option::create([

                                'question_id' => $question->id,

                                'text' => $optionData['label'],

                                'score' => $optionData['score'],

                            ]);
                        }
                    }
                }
            }
        }



        // Return response (if needed)

        return response()->json(['redirect' => url('superadmin/createformat')]);
    }



    public function createformatname(Request $request)

    {

        // Validate the request

        $data = $request->all();



        // Retrieve user ID and format name

        $userId = $data['user_id'];

        $formatName = $data['format_name'];



        // Save format information

        $format = new Format();

        $format->user_id = $userId;

        $format->name = $formatName;

        $format->save();



        // Process sections

        if (isset($data['sections'])) {

            foreach ($data['sections'] as $sectionData) {

                $section = new Section();

                $section->format_id = $format->id;

                $section->section_name = $sectionData['name'];

                $section->save();



                // Process questions

                if (isset($sectionData['questions'])) {

                    foreach ($sectionData['questions'] as $questionData) {

                        $question = new Question();

                        $question->section_id = $section->id;

                        $question->question_name = $questionData['name'];

                        $question->type = $questionData['type'];

                        $question->score = $questionData['score'];

                        $question->guideline = $questionData['guideline'];

                        $question->save();



                        // Process options

                        if (isset($questionData['options'])) {

                            foreach ($questionData['options'] as $optionData) {

                                $option = new Option();

                                $option->question_id = $question->id;

                                $option->text = $optionData['text'];

                                $option->score = $optionData['score'];

                                $option->save();
                            }
                        }
                    }
                }
            }
        }



        return redirect()->back()->with('success', 'Data stored successfully!');
    }







    public function showExistingFormat(Request $request)

    {



        $validatedData = $request->validate([

            'user_id' => 'required|integer', // Add any validation rules you need

        ]);





        $userId = $request->input('user_id');





        $existingFormats = Format::where('client_id', $userId)->get();



        // Pass the existing formats to your view or return a JSON response

        return view('superadmin.createformat',  compact('existingFormats'));
    }



    public function createwave(Request $request)

    {
        $parentName = Session::get('parentName');

        $users = User::where('is_role', '=', '4')

            ->where('parentName', '=', $parentName)

            ->pluck('name', 'id');

        session()->forget('currentStep');
        session()->put('currentStep', 7);

        return view('superadmin.wave', compact('users'));
    }

    public function getUserFormat(Request $request)

    {

        $userId = $request->id; // Get the user ID from the request



        // Find all formats where the client_id matches $userId

        $formats = Format::where('user_id', $userId)->get();



        // Initialize an empty string to store the options

        $options = '';



        $options = '<option value="">Select Format</option>'; // Default option

        foreach ($formats as $format) {

            $options .= "<option value='{$format->id}'>{$format->name}</option>";
        }



        return $options;
    }

    public function storeData(Request $request)

    {

        // dd($request->all());

        $validatedData = $request->validate([

            'format_id' => 'required|integer',

            'name' => 'required|string|max:255',

        ]);



        $format = new waves();

        $format->format_id = $validatedData['format_id'];

        $format->name = $validatedData['name'];

        $format->created_at = now(); // Set the created_at timestamp

        $format->updated_at = now(); // Set the updated_at timestamp



        // Save the format to the database

        $format->save();

        return redirect()->back()->with('success', 'Data stored successfully!');
    }
    public function updateWave(Request $request)
    {
        $validatedData = $request->validate([
            'wave_id' => 'required|integer|exists:waves,id', // Validate that wave_id exists
            'name' => 'required|string|max:255',
        ]);

        // Find the wave by ID and update its name
        $wave = Waves::find($validatedData['wave_id']);
        $wave->name = $validatedData['name'];
        $wave->updated_at = now(); // Update the timestamp
        $wave->save(); // Save the changes

        return redirect()->back()->with('success', 'Wave updated successfully!');
    }

    public function storeData1(Request $request)

    {

        // dd($request->all());

        $validatedData = $request->validate([

            'format_id' => 'required|integer',

            'name' => 'required|string|max:255',

        ]);



        $format = new waves();

        $format->format_id = $validatedData['format_id'];

        $format->name = $validatedData['name'];

        $format->created_at = now(); // Set the created_at timestamp

        $format->updated_at = now(); // Set the updated_at timestamp



        // Save the format to the database

        $format->save();
        session()->forget('currentStep');
        session()->put('currentStep', 7);

        // return redirect()->back()->with('success', 'hierarchy stored successfully!');
        return view('superadmin.clientcreate')->with('success', 'Data stored successfully!');
    }
   public function fetchFormats(Request $request)

    {

        // dd("1");

        $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
        $formats = Format::where('process_id', $request->user_id)->get();

        return response()->json($formats);
    }


    public function deleteFormat(Request $request)

    {

        $clientid = session::get(key: 'user_id');
        $database_name = session::get('client_database');
        $connection = ClientDatabaseManager::setConnection($database_name);


            $format = Format::find($request->format_id);



            if (!$format) {

                return response()->json(['error' => 'Format not found'], 404);
            }



            $format->delete();



            return response()->json(['message' => 'Format deleted successfully'], 200);
     
    }



    public function fetchFormatData($id)

    {

        // dd($id);



        $format = Format::with('sections.questions.options')->findOrFail($id);

        return response()->json($format);
    }

    public function editformat($id)

    {

        // dd($id);

    $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);

$format = Format::with([
    'sections' => function ($query) {
        $query->with([
            'questions' => function ($query) {
                $query->with('options');
            },
        ]);
    }
])->findOrFail($id);

       //  dd( $format);

        return view('admin.editformat', compact('format'));
    }



    public function update(Request $request)

    {

        // dd($request->all());

        // Validate incoming request data

        $validatedData = $request->validate([

            'id' => 'required|integer|exists:formats,id',

            // Other validation rules for format name, sections, questions, options, etc.

        ]);



        // Update the format

        $format = Format::find($validatedData['id']);

        $format->name = $request->input('name');

        $format->save();



        // Handle sections, questions, and options

        // Handle sections, questions, and options

        foreach ($request->input('sections') as $sectionData) {

            $section = Section::updateOrCreate(['id' => $sectionData['id']], [

                'format_id' => $format->id,

                'section_name' => $sectionData['name'],

            ]);



            foreach ($sectionData['questions'] as $questionData) {

                if (isset($questionData['id'])) {

                    // Existing question, update it

                    $question = Question::updateOrCreate(['id' => $questionData['id']], [

                        'section_id' => $section->id,

                        'question_name' => $questionData['name'],

                        'score' => $questionData['score'],

                        'guideline' => $questionData['guideline'],

                    ]);
                } else {

                    // New question, create it

                    $question = Question::create([

                        'section_id' => $section->id,

                        'question_name' => $questionData['name'],

                        'type' => $questionData['type'],

                        'score' => $questionData['score'],

                        'guideline' => $questionData['guidelines'],

                    ]);
                }



                // Check if 'options' key exists and is an array

                if (array_key_exists('options', $questionData) && is_array($questionData['options'])) {

                    foreach ($questionData['options'] as $optionData) {

                        Option::updateOrCreate(['id' => $optionData['id']], [

                            'question_id' => $question->id,

                            'text' => $optionData['text'],

                            'score' => $optionData['score'],

                        ]);
                    }
                }
            }
        }



        return redirect()->back()->with('success', 'Data stored successfully!');
    }



    public function saveFormat(Request $request)

    {
  
     $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
      
  


        $format = Format::find($request->format_id);

        $format->name = $request->format_name;

        $format->save();



        return response()->json(['success' => 'Format saved successfully!']);
    }



  
    public function saveSection(Request $request)
    {
          $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
        // Find the section by its ID
   //dd($database_name, $connection);

        $section = Section::find($request->section_id);

        // If the section doesn't exist, create a new one
        if (!$section) {
            $section = new Section();
            $section->name = $request->section_name;
            $section->format_id  = $request->format_id;
            // Get the highest orderby value for the format's sections
            $maxOrderBy = Section::where('format_id', $request->format_id)->max('order_by');

            // Set the new section's orderby to maxOrderBy + 1 if sections exist, otherwise set it to 1
            $section->order_by = $maxOrderBy ? $maxOrderBy + 1 : 1;
        } else {
            // Update the existing section
            $section->name = $request->section_name;
        }

        // Save the section (new or updated)
        $section->save();

        // Return a success response with the section ID
        return response()->json([
            'success' => 'Section saved successfully!',
            'section_id' => $section->id,  // Return the section ID
        ]);
    }

public function saveQuestion(Request $request)
{
    // Retrieve the client database from the session
    $database_name = session()->get('client_database');
    if (!$database_name) {
        return response()->json(['error' => 'Client database not set.'], 400);
    }

    // Set the database connection dynamically
    ClientDatabaseManager::setConnection($database_name);
    \Log::info('Current Connection:', ['connection' => DB::getDefaultConnection()]);

    // Ensure the Question model uses the correct connection
    $question = Question::on('client')
        ->where('id', $request->question_id)
        ->where('section_id', $request->section_id)
        ->first();

    // If question doesn't exist, create a new one
    if (!$question) {
        $question = new Question();
        $maxOrder = Question::on('client')
            ->where('section_id', $request->section_id)
            ->max('order_by');  // Get the max order value in the section

        $question->order_by = $maxOrder ? $maxOrder + 1 : 1;
    }

    // Assign attributes
    $question->text = $request->question_name;
    $question->section_id = $request->section_id;
    $question->guidelines = $request->guideline;
    $question->type = $request->question_type;
    $question->save();

    // Return response with question ID and a success message
    return response()->json([
        'success' => 'Question saved successfully!',
        'question_id' => $question->id,
    ]);
}

    public function saveOption(Request $request)
    {
             $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
        // Validate that both text and score are present
        $request->validate([
            'text' => 'required|string|max:255', // Adjust max length as needed
            'score' => 'required|integer' // Ensure score is an integer
        ]);

        // Step 1: Check if the option exists in the Option table
    $option = Option::where('name', $request->text)->first();
    if (!$option) {
        // If the option doesn't exist, create a new one
        $option = new Option();
        $option->name = $request->text;
        $option->save();
    }
  $optionId = $option->id;

    // Step 2: Check if the option exists in the QOption table for the given question
    $optionQ = QOption::where('option_id',  $request->option_id)
        ->where('question_id', $request->question_id)
        ->first();

    if ($optionQ) {
        // If the option exists in QOption, update the score
              $optionQ->option_id = $optionId;
        $optionQ->score = $request->score;
    } else {
        // If the option doesn't exist in QOption, create a new one
        $optionQ = new QOption();
        $optionQ->question_id = $request->question_id;
        $optionQ->option_id = $optionId;
        $optionQ->score = $request->score;
    }

    // Save the QOption record
    $optionQ->save();

    return response()->json([
        'success' => 'Option saved successfully',
        'option_id' => $option->id,
        'q_option_id' => $optionQ->id
    ]);
    }

    public function destroy($id)
    {
      $database_name = session::get('client_database');
    $connection = ClientDatabaseManager::setConnection($database_name);
        // Find the option by ID
        $option = QOption::where('option_id', $id)->first();

        // Check if the option exists
        if (!$option) {
            return response()->json(['message' => 'Option not found.'], 404);
        }

        // Delete the option
        $option->delete();

        // Return a success response
        return response()->json(['message' => 'Option deleted successfully.']);
    }


    public function savekeyword(Request $request)

    {

        $text = $request->input('text');

        $question_id = $request->input('question_id');



        // Check if a keyword already exists for the given question_id

        $keyword = Keyword::where('question_id', $question_id)->first();



        if ($keyword) {

            $keyword->keywords .= $keyword->keywords ? ',' . $text : $text;

            $keyword->save();
        } else {

            // If no keyword exists, create a new one

            Keyword::create([

                'keywords' => $text,

                'question_id' => $question_id,

            ]);
        }



        return response()->json([

            'status' => 'success',

            'message' => 'Keyword saved successfully.',

        ]);
    }

    public function reorderFormat($id)

    {

        // dd($id);



        $format = Format::with('sections.questions')->findOrFail($id);

        return view('superadmin.reorderFormat', compact('format'));
    }







    public function updateSectionOrder(Request $request)

    {

        $sectionId = $request->input('item_id');

        $newOrder = $request->input('new_order');



        // Update section order in database

        $section = Section::find($sectionId);

        if ($section) {

            $section->orderby = $newOrder;

            $section->save();

            return response()->json(['success' => true, 'message' => 'Section order updated successfully.']);
        }



        return response()->json(['success' => false, 'message' => 'Section not found.']);
    }



    public function updateQuestionOrder(Request $request)

    {

        // dd(1);

        $questionId = $request->input('item_id');

        $newOrder = $request->input('new_order');



        // Update question order in database

        $question = Question::find($questionId);

        if ($question) {

            $question->orderby = $newOrder;

            $question->save();

            return response()->json(['success' => true, 'message' => 'Question order updated successfully.']);
        }



        return response()->json(['success' => false, 'message' => 'Question not found.']);
    }

    public function saveHierarchy(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'format_id' => 'required|integer',
            'hierarchy_levels' => 'required|array',
            'timeIn' => 'boolean',
            'timeOut' => 'boolean',
            'branchCode' => 'boolean',
            'date' => 'boolean',
        ]);

        // Retrieve values from the validated request
        $formatId = $request->input('format_id');
        $hierarchyLevels = $request->input('hierarchy_levels');

        // Convert boolean values to integers
        $timeIn = $request->input('timeIn') ? 1 : 0;
        $timeOut = $request->input('timeOut') ? 1 : 0;
        $branchCode = $request->input('branchCode') ? 1 : 0;
        $date = $request->input('date') ? 1 : 0;

        // Loop through each hierarchy level
        foreach ($hierarchyLevels as $levelId) {
            // Check if a record already exists for the given format_id
            $existingRecord = DB::table('headers')
                ->where('format_id', $formatId)
                ->first();

            if ($existingRecord) {
                // Get current hierarchy ids and decode the JSON to an array
                $existingHierarchyIds = json_decode($existingRecord->hierarchyids, true);

                // Ensure existingHierarchyIds is an array
                if (!is_array($existingHierarchyIds)) {
                    $existingHierarchyIds = []; // Initialize to empty array if decoding failed
                }

                // Only add the new levelId if it doesn't already exist
                if (!in_array($levelId, $existingHierarchyIds)) {
                    $existingHierarchyIds[] = $levelId; // Append the new levelId
                }

                // Convert back to JSON format
                $updatedHierarchyIds = json_encode(array_values(array_unique($existingHierarchyIds)));

                // Update the existing record with the updated hierarchyids
                DB::table('headers')->where('id', $existingRecord->id)->update([
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'branch_code' => $branchCode,
                    'date' => $date,
                    'hierarchyids' => $updatedHierarchyIds,
                ]);
            } else {
                // Insert a new record if it does not exist
                DB::table('headers')->insert([
                    'format_id' => $formatId,
                    'hierarchyids' => json_encode([$levelId]), // Store as JSON array
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'branch_code' => $branchCode,
                    'date' => $date,
                ]);
            }
        }

        // Return a success response
        return response()->json(['message' => 'Hierarchy levels saved successfully.'], 200);
    }
}
