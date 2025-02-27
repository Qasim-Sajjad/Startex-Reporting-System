<?php



namespace App\Http\Controllers;



use App\Models\Format;

use App\Models\waves;
use App\Models\SurveyAnswer;
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
use App\Services\GoogleDriveService;


use App\Models\assignshops;

use App\Models\Keyword;



class survayController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    public function survay()
    {
        // dd(1);
        $format = DB::table('formats')->where('format_for', 'survay')->get();

        // dd($sections);
        return view('survayuser.survay', [
            'formats' => $format,

        ]);
    }

    public function search(Request $request)
    {
        // Retrieve the search query from the AJAX request
        $query = $request->input('query');
        $format = $request->input('format_id');
        // Query the Survey model to find a survey by phone number
        $survey = DB::table('surveys')
            ->where('MOBILE_NUMBER', $query)
            ->where('format_id', $format)
            ->whereNull('username')
            ->get(); // Use ->first() if you expect only one result.

        if ($survey && count($survey) > 0) {
            $survey = $survey[0]; // Get the first result from the array

            // Fetch associated sections, questions, and options for the found survey
            $sections = Section::with(['questions.options'])
                ->where('format_id', $format)
                ->get();

            // Return the survey details and associated sections, questions, and options
            return response()->json([
                'success' => true,
                'survey' => [
                    'name' => $survey->CUSTOMER_NAME,
                    'phone_number' => $survey->MOBILE_NUMBER,
                    'id' => $survey->id,
                ],
                'sections' => $sections, // Return the sections for dynamic display
            ]);
        } else {
            // No survey found
            return response()->json(['success' => false]);
        }
    }
    public function submitSurvey(Request $request)
    {
        // Get the uploaded audio file
        $audioFile = $request->file('audio_file');
        $surveyId = $request->input('survey_id');
        $answers = $request->input('answers'); // Automatically handled by Laravel
        $username = Session::get('username');
        $matrixAnswers = $request->input('matrixAnswers'); // Automatically handled by Laravel

        // Fetch the data using Query Builder
        $result = DB::table('surveys')
            ->join('formats', 'surveys.format_id', '=', 'formats.id')
            ->where('surveys.id', $surveyId)
            ->select('surveys.MOBILE_NUMBER as mobilenumber', 'formats.name as formatname')
            ->limit(1)
            ->first(); // Use `first` to fetch a single row directly

        // Extract the values
        $phoneNumber = $result->mobilenumber ?? null;
        $formatName = $result->formatname ?? null;

        // Decode the `answers` field if it's a JSON string
        if (is_string($answers)) {
            $answers = json_decode($answers, true); // Decode JSON string to associative array
        }
        if (is_string($matrixAnswers)) {
            $matrixAnswers = json_decode($matrixAnswers, true); // Decode to an associative array
        }
        // Ensure `$answers` is now an array
        if (!is_array($answers)) {
            return response()->json(['message' => 'Invalid answers format'], 400);
        }

        // If there's an audio file, upload it
        if ($audioFile) {

            // Upload the audio file to Google Drive
            // $fileId = $this->googleDriveService->uploadFile($audioFile);

            $dynamicFileName = $formatName . '_' . $phoneNumber . '.' . $audioFile->getClientOriginalExtension();

            // Upload the audio file to Google Drive with the dynamic name
            $fileId = $this->googleDriveService->uploadFile($audioFile, $dynamicFileName);
            // Process each answer
            foreach ($answers as $answer) {
                // Ensure required fields are available
                if (isset($answer['question_id'], $answer['answer'])) {
                    $questionId = $answer['question_id'];
                    $answerText = $answer['answer'];
                    $reasonText = null;

                    // Handle "Other" type answers
                    if ((str_contains($answerText, 'Other')) || ($questionId == 1265924 && $answerText == 'Yes')) {
                        $parts = explode(':', $answerText, 2); // Split into "Other" and additional value
                        $answerText = $parts[0]; // "Other"
                        $reasonText = $parts[1] ?? null; // Additional value
                    }

                    // Insert the answer into the survey_answers table using Query Builder
                    DB::table('survey_answers')->insert([
                        'survey_id' => $surveyId,
                        'question_id' => $questionId,
                        'answer' => $answerText,
                        'reason' => $reasonText,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $insertedServiceIndexes = [];

            foreach ($matrixAnswers as $answer) {

                // Ensure required fields are available
                if (isset($answer['question_id'], $answer['rating'], $answer['serviceIndex'])) {
                    $serviceIndex = $answer['serviceIndex'];

                    // Check if serviceIndex is greater than 1
                    if ($serviceIndex > -1) {
                        $questionId = $answer['question_id'];
                        $rating = $answer['rating'];
                        $answerText = $rating; // Default to the rating value
                        $reasonText = null; // Default to no reason

                        // Handle "Other" type answers and any reason provided
                        if (isset($answer['reason']) && !empty($answer['reason'])) {
                            $reasonText = $answer['reason']; // Assign the reason if provided
                        }

                        // Concatenate the service name based on the serviceIndex
                        $services = [
                            0 => "Staff Behavior",
                            1 => "Branch Environment (Internal, External)",
                            2 => "ATM Services",
                            3 => "Debit card",
                            4 => "Phone Banking",
                            5 => "Remittance (Counter Services)"
                        ];

                        // Check if the serviceIndex is valid and get the service name
                        if (isset($services[$serviceIndex])) {
                            $serviceName = $services[$serviceIndex];

                            // Insert only if this serviceIndex has not been inserted before
                            if (!in_array($serviceIndex, $insertedServiceIndexes)) {
                                // Concatenate the rating with the service name
                                $answerText = $rating . ' - ' . $serviceName;

                                // Insert the answer into the survey_answers table using Query Builder
                                DB::table('survey_answers')->insert([
                                    'survey_id' => $surveyId, // Assuming you have the survey_id available
                                    'question_id' => $questionId,
                                    'answer' => $answerText, // Save the concatenated answer
                                    'reason' => $reasonText, // Save the reason if available
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                // Mark this serviceIndex as inserted
                                $insertedServiceIndexes[] = $serviceIndex;
                            }
                        }
                    }
                    // If serviceIndex <= 1, no action will be performed
                }
            }

            DB::table('surveys')
                ->where('id', $surveyId)
                ->update([
                    'username' => $username, // Assuming the 'submitted_by' column exists in surveys table
                ]);
            // Return success response after all answers are processed
            // return response()->json(['message' => 'Survey submitted successfully!', 'file_id' => $fileId], 200);
        }

        // If no audio file, return a success message
        // return response()->json(['message' => 'Survey submitted successfully!'], 200);
    }
    public function surveydata()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('survaydata', compact('groupedData'));
    }
    public function BranchBanking()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->where('s.format_id', 923) // Add the condition for format_id
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('BranchBanking', compact('groupedData'));
    }
    public function ClosedAccounts()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->where('s.format_id', 936) // Add the condition for format_id
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('ClosedAccounts', compact('groupedData'));
    }
    public function Banca()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->where('s.format_id', 928) // Add the condition for format_id
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('Banca', compact('groupedData'));
    }
    public function Consumer()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->where('s.format_id', 929) // Add the condition for format_id
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('Banca', compact('groupedData'));
    }
    public function MobileBanking()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->where('s.format_id', 932) // Add the condition for format_id
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('Banca', compact('groupedData'));
    }
    public function Remittance()
    {
        $data = DB::table('surveys as s')
            ->join('survey_answers as sa', 's.id', '=', 'sa.survey_id')
            ->where('s.format_id', 935) // Add the condition for format_id
            ->select(
                DB::raw('DATE(sa.created_at) as survey_date'),
                's.username as user_name',
                DB::raw('COUNT(DISTINCT sa.survey_id) as survey_count')
            )
            ->groupBy('survey_date', 'user_name')
            ->orderBy('survey_date')
            ->get();

        // Grouping data by date, and then each user's survey count
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->survey_date][$row->user_name] = $row->survey_count;
        }


        return view('Banca', compact('groupedData'));
    }
}
