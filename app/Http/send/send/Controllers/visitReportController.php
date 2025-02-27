<?php



namespace App\Http\Controllers;



use App\Models\format;

use App\Models\User;

use Illuminate\Http\Request;

use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\hierarchynames;

use App\Models\Hierarchylevels;

use App\Models\hierarchies;

use App\Models\locations;

use Illuminate\Support\Facades\DB;

use App\Models\assignprojects;

use App\Models\waves;

use App\Models\assignshops;

use Illuminate\Support\Facades\Session;

use App\Models\Section;

use App\Models\Question;

use App\Models\Option;

use App\Models\scores;

use App\Models\scoreanalysics;

use App\Models\comments;

use App\Models\VisitAudioRecord;

use Illuminate\Support\Facades\Crypt; // Import the Crypt facade

use App\Models\branchCalculations;

use App\Models\sectionCalculations;

use App\Models\regionCalculations;

use App\Models\Criteria;

use Barryvdh\DomPDF\Facade\Pdf as PDFDom;

use Maatwebsite\Excel\Facades\Excel; // Import the Excel facade

use App\Exports\ReportExport;
use App\Models\contests;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


use ZipArchive;



class visitReportController extends Controller

{
    public function pdfdownload($id)

    {
        // echo "pdf";
        $shopID = $id;
        session::put('title', "Visit Report");
        $format_id =  session::get('format_id');
        $wave_id1 = session::get('wave_id1');
        $wave_id = session::get('wave_id');
        $ytd = Session::get('YTD');
        $shopDetails = DB::table('assignshops')->where('id', $shopID)->first(); // dd($shopDetails);
        // $status1 = $shopDetails->status;
        $status1 =  $shopDetails->status;
        $formatID = $shopDetails->format_id;
        $waveID =  $shopDetails->wave_id;
        $initialHierarchyId = $shopDetails->location_id;
        $time =  $shopDetails->timeIn;
        $date = $shopDetails->date;
        // Recursive query to fetch hierarchical data
        $query = "  WITH RECURSIVE HierarchyCTE AS (SELECT  h.id AS hierarchy_id, h.levelID AS level_id, hl.hierarchylavelname AS level_name,
            hl.level AS level, hl.HID AS hid, l.locationname AS location_name, h.parentID AS parent_id FROM  hierarchies h INNER JOIN  hierarchylevels hl ON h.levelID = hl.id
          INNER JOIN   locations l ON h.LID = l.id WHERE  h.id = :initialHierarchyId
         UNION ALL  SELECT  h.id AS hierarchy_id,  h.levelID AS level_id, hl.hierarchylavelname AS level_name, hl.level AS level,
           hl.HID AS hid,l.locationname AS location_name, h.parentID AS parent_id FROM hierarchies h
        INNER JOIN  hierarchylevels hl ON h.levelID = hl.id INNER JOIN 
            locations l ON h.LID = l.id
        INNER JOIN            HierarchyCTE hc ON hc.parent_id = h.id )SELECT 
        hierarchy_id,
        level_id,
        level_name,
        level,
        hid,
        location_name  FROM 
        HierarchyCTE;";
        $firstLocationName = '';
        if (!empty($hierarchyLevels)) {
            $firstLocationName = $hierarchyLevels[0]->location_name;
        }
        // Execute query and fetch results
        $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);
        // $shopoverall = branchCalculations::where('shop_id', $shopID)->get();
        $overallScore = DB::table('scoreanalysics')
            ->selectRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overallscore')
            ->where('shop_id', $shopID)
            ->pluck('overallscore')
            ->first();
        $criterias = Criteria::where('format_id', $format_id)->get();
        $overAllScore = $overallScore;
        $sectionScores = scoreanalysics::select(
            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),
            DB::raw('SUM(achieved) as achieved'),
            DB::raw('SUM(applicable) as applicable'),
            DB::raw('SUM(total) as total'),
            'section_name',
            'section_id'
        )->where('shop_id', $shopID)->groupBy('section_id', 'section_name')->get();
        $overallresult = [];
        foreach ($sectionScores as $sectionScore) {
            $sectionID = $sectionScore->section_id;
            $sectionName = $sectionScore->section_name;
            $questions = scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')
                ->where('scoreanalysics.section_id', $sectionID)
                ->where('scoreanalysics.shop_id', $shopID)
                ->orderBy('questions.orderby')
                ->get(['scoreanalysics.*', 'questions.*']);
            $questionsData = [];
            foreach ($questions as $question) {
                $question_id = $question->question_id;
                $question_name = $question->question_name;
                $response = $question->response;
                $achieved = $question->achieved;
                $applicable = $question->applicable;
                $total = $question->total;
                $comments = comments::where('question_id', $question_id)
                    ->where('shop_id', $shopID)
                    ->get()
                    ->pluck('comments')
                    ->toArray();
                $questionsData[] = [
                    'question_id' => $question_id,
                    'question_name' => $question_name,
                    'response' => $response,
                    'achieved' => $achieved,
                    'applicable' => $applicable,
                    'total' => $total,
                    'comments' => $comments
                ];
            }
            $overallresult[] = [
                'section_id' => $sectionID,
                'section_name' => $sectionName,
                'section_score' => $sectionScore->sectionScore,
                'questions' => $questionsData
            ];
        }
        $firstLocationName = '';
        if (!empty($hierarchyLevels)) {
            $firstLocationName = $hierarchyLevels[0]->location_name;
        }
        // $pdf = PDFDom::loadView('client.report.pdfdownload.report');
        $pdf = PDFDom::loadView('client.report.pdfdownload', [
            'hierarchyLevels' => $hierarchyLevels,
            'time' => $time,
            'date' =>  $date,
            'overAllScore' => $overAllScore,
            'sectionScores' =>  $sectionScores,
            'overallresult' => $overallresult,

        ])->setPaper('a4', 'portrait');

        return $pdf->download($firstLocationName . '.pdf');
    }
    public function bulkExcelDownload(Request $request)
    {
        $shopids = $request->query('ids'); // Retrieve the IDs from the query string
        $idsArray = explode(',', $shopids);
        $format_id = Session::get('format_id');
        $wave_id1 = Session::get('wave_id1');
        $wave_id = Session::get('wave_id');
        $ytd = Session::get('YTD');

        $zip = new \ZipArchive();
        $zipFileName = 'shop_reports.zip';

        // Create a temporary file for the zip
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        $zip->open($tempFile, \ZipArchive::CREATE);

        foreach ($idsArray as $shopID) {
            // Retrieve shop details
            $shopDetails = DB::table('assignshops')->where('id', $shopID)->first();
            $status1 = $shopDetails->status;
            $formatID = $shopDetails->format_id;
            $waveID = $shopDetails->wave_id;
            $initialHierarchyId = $shopDetails->location_id;
            $time = $shopDetails->timeIn;
            $date = $shopDetails->date;

            // Recursive query to fetch hierarchical data
            $query = "WITH RECURSIVE HierarchyCTE AS (
                        SELECT h.id AS hierarchy_id, h.levelID AS level_id, hl.hierarchylavelname AS level_name, 
                               hl.level AS level, hl.HID AS hid, l.locationname AS location_name, h.parentID AS parent_id
                        FROM hierarchies h
                        INNER JOIN hierarchylevels hl ON h.levelID = hl.id
                        INNER JOIN locations l ON h.LID = l.id
                        WHERE h.id = :initialHierarchyId
                        UNION ALL
                        SELECT h.id AS hierarchy_id, h.levelID AS level_id, hl.hierarchylavelname AS level_name, 
                               hl.level AS level, hl.HID AS hid, l.locationname AS location_name, h.parentID AS parent_id
                        FROM hierarchies h
                        INNER JOIN hierarchylevels hl ON h.levelID = hl.id
                        INNER JOIN locations l ON h.LID = l.id
                        INNER JOIN HierarchyCTE hc ON hc.parent_id = h.id
                      )
                      SELECT hierarchy_id, level_id, level_name, level, hid, location_name
                      FROM HierarchyCTE";

            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);



            // $shopoverall = branchCalculations::where('shop_id', $shopID)->get();
            $overallScore = DB::table('scoreanalysics')
                ->selectRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overallscore')
                ->where('shop_id', $shopID)
                ->pluck('overallscore')
                ->first();
            $criterias = Criteria::where('format_id', $format_id)->get();
            // dd($overallScore);
            $overAllScore = $overallScore;



            $conditionLabel = ""; // Initialize a variable to store the condition label



            $sectionScores = scoreanalysics::select(

                DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),
                DB::raw('SUM(achieved) as achieved'),
                DB::raw('SUM(applicable) as applicable'),
                DB::raw('SUM(total) as total'),

                'section_name',
                'section_id'
            )
                ->where('shop_id', $shopID)
                ->groupBy('section_id', 'section_name')
                ->get();

            $overallresult = [];



            foreach ($sectionScores as $sectionScore) {

                $sectionID = $sectionScore->section_id;

                $sectionName = $sectionScore->section_name;



                $questions = scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')

                    ->where('scoreanalysics.section_id', $sectionID)

                    ->where('scoreanalysics.shop_id', $shopID)

                    ->orderBy('questions.orderby')

                    ->get(['scoreanalysics.*', 'questions.*']);



                $questionsData = [];



                foreach ($questions as $question) {

                    $question_id = $question->question_id;

                    $question_name = $question->question_name;

                    $response = $question->response;

                    $achieved = $question->achieved;

                    $applicable = $question->applicable;

                    $total = $question->total;



                    $comments = comments::where('question_id', $question_id)

                        ->where('shop_id', $shopID)

                        ->get()

                        ->pluck('comments')

                        ->toArray();



                    $questionsData[] = [

                        'question_id' => $question_id,

                        'question_name' => $question_name,

                        'response' => $response,

                        'achieved' => $achieved,

                        'applicable' => $applicable,

                        'total' => $total,

                        'comments' => $comments

                    ];
                }



                $overallresult[] = [

                    'section_id' => $sectionID,

                    'section_name' => $sectionName,

                    'section_score' => $sectionScore->sectionScore,

                    'questions' => $questionsData

                ];
            }

            $firstLocationName = '';
            if (!empty($hierarchyLevels)) {
                $firstLocationName = $hierarchyLevels[0]->location_name;
            }


            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setShowGridlines(false);

            $headerStyleArray = [
                'font' => [
                    'bold' => true, // Bold text
                    'size' => 20,   // Font size
                    'color' => ['rgb' => '000000'], // Black text color
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF808080', // Grey background color
                    ],
                ],
            ];

            // Apply the header style to the "MYSTERY SHOPPING REPORT" cell
            $sheet->setCellValue('A1', 'MYSTERY SHOPPING REPORT');
            $sheet->mergeCells('A1:O1');
            $sheet->getStyle('A1')->applyFromArray($headerStyleArray);

            // Set row height for the header row
            $sheet->getRowDimension(1)->setRowHeight(40); // Adjust the height as needed

            // Optionally, adjust column width to add padding
            $sheet->getColumnDimension('A')->setWidth(20); // Adjust width for padding
            // Add the score row
            $sheet->setCellValue('A2', 'This Visit Score: ' . $overAllScore . '%');
            $sheet->mergeCells('A2:O2');

            // Apply styling to the score row
            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'bold' => true, // Bold text
                    'size' => 18,   // Font size
                    'color' => ['rgb' => '000000'], // Black text color
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFD3D3D3', // Yellow background color
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                        'color' => ['rgb' => 'FFD3D3D3'], // Black borders
                    ],
                ],
            ]);

            // Add hierarchy levels and visit details

            $rowIndex = 4;
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            foreach ($hierarchyLevels as $level) {
                $sheet->setCellValue('A' . $rowIndex, $level->level_name);
                $sheet->setCellValue('B' . $rowIndex, $level->location_name);
                $sheet->mergeCells('C' . $rowIndex . ':O' . $rowIndex); // Merge Section Name header across A to L
                $sheet->setCellValue('C' . $rowIndex, '');
                // Apply styling to hierarchy level rows
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                            'color' => ['rgb' => 'FFFFFF'], // White borders
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFFFFF', // Optional: White background color
                        ],
                    ],
                ]);

                // Optionally, adjust row height for padding
                $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed

                // Optionally, adjust column width for padding
                $sheet->getColumnDimension('A')->setWidth(25); // Adjust column width for padding
                $sheet->getColumnDimension('B')->setWidth(30); // Adjust column width for padding

                $rowIndex++;
            }

            $sheet->setCellValue('A' . $rowIndex, 'Visit Date');
            $sheet->setCellValue('B' . $rowIndex, $date);
            $sheet->mergeCells('C' . $rowIndex . ':O' . $rowIndex); // Merge Section Name header across A to L
            $sheet->setCellValue('C' . $rowIndex, '');

            // Apply styling to visit date row
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                        'color' => ['rgb' => 'FFFFFF'], // White borders
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFFFFF', // Optional: White background color
                    ],
                ],
            ]);

            // Optionally, adjust row height for padding
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed

            // Optionally, adjust column width for padding
            $sheet->getColumnDimension('A')->setWidth(25); // Adjust column width for padding
            $sheet->getColumnDimension('B')->setWidth(30); // Adjust column width for padding

            $rowIndex++;

            $sheet->setCellValue('A' . $rowIndex, 'Visit Time');
            $sheet->setCellValue('B' . $rowIndex, $time);
            $sheet->mergeCells('C' . $rowIndex . ':O' . $rowIndex); // Merge Section Name header across A to L
            $sheet->setCellValue('C' . $rowIndex, '');
            // Apply styling to visit date row
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                        'color' => ['rgb' => 'FFFFFF'], // White borders
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFFFFF', // Optional: White background color
                    ],
                ],
            ]);

            // Optionally, adjust row height for padding
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed

            // Apply styling to visit time row
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                        'color' => ['rgb' => 'FFFFFF'], // Black borders
                    ],
                ],
            ]);
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            $rowIndex += 1;

            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);

            // Add Section Summary Header
            $sheet->setCellValue('A' . $rowIndex, 'Section Summary');
            $sheet->mergeCells('A' . $rowIndex . ':O' . $rowIndex);
            $sheet->getStyle('A' . $rowIndex)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFD3D3D3', // Light grey background
                    ],
                ],
            ]);

            // Adjust row height for padding
            $sheet->getRowDimension($rowIndex)->setRowHeight(30); // Adjust height as needed

            $rowIndex++;

            // Set headers for the section summary
            $sheet->setCellValue('A' . $rowIndex, 'Section Name');
            $sheet->mergeCells('A' . $rowIndex . ':G' . $rowIndex); // Merge Section Name header across A to L
            $sheet->setCellValue('H' . $rowIndex, 'Total');
            $sheet->mergeCells('H' . $rowIndex . ':I' . $rowIndex);
            $sheet->setCellValue('J' . $rowIndex, 'Applicable');
            $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex);
            $sheet->setCellValue('L' . $rowIndex, 'Achieved');
            $sheet->mergeCells('L' . $rowIndex . ':M' . $rowIndex);
            $sheet->setCellValue('N' . $rowIndex, 'Score (%)');
            $sheet->mergeCells('N' . $rowIndex . ':O' . $rowIndex); // Merge Score header across M to O

            // Apply styles to the header row
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFE6E6E6', // Lighter grey for headers
                    ],
                ],
            ]);

            // Adjust row height for padding
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            $rowIndex++;

            // Apply border styles to the section summary rows
            foreach ($sectionScores as $score) {
                // Set the section name and score
                $sheet->setCellValue('A' . $rowIndex, $score->section_name);
                $sheet->mergeCells('A' . $rowIndex . ':G' . $rowIndex); // Merge Section Name across A to L


                $sheet->setCellValue('H' . $rowIndex, $score->sectionScore !== null ? $score->total : 'NA');
                $sheet->mergeCells('H' . $rowIndex . ':I' . $rowIndex); // Merge Score across M to O
                $sheet->setCellValue('J' . $rowIndex, $score->sectionScore !== null ? $score->applicable : 'NA');
                $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex); // Merge Score across M to O
                $sheet->setCellValue('L' . $rowIndex, $score->sectionScore !== null ? $score->achieved : 'NA');
                $sheet->mergeCells('L' . $rowIndex . ':M' . $rowIndex); // Merge Score across M to O


                $sheet->setCellValue('N' . $rowIndex, $score->sectionScore !== null ? $score->sectionScore . '%' : 'NA');
                $sheet->mergeCells('N' . $rowIndex . ':O' . $rowIndex); // Merge Score across M to O

                // Apply styles to the section summary rows
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border color
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFFFFF', // White background color (optional, if needed)
                        ],
                    ],
                ]);

                // Optionally, adjust row height for better padding
                $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed

                $rowIndex++;
            }
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            $rowIndex += 1; // Add some space after the Section Summary

            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);

            // Add Overall Result and Questions
            foreach ($overallresult as $section) {
                // Section Name and Score Row
                $sheet->setCellValue('A' . $rowIndex, $section['section_name']);
                $sheet->setCellValue('M' . $rowIndex, $section['section_score'] . '%');
                $sheet->mergeCells('A' . $rowIndex . ':I' . $rowIndex);
                $sheet->mergeCells('M' . $rowIndex . ':O' . $rowIndex);

                // Apply styles
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // White font color
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '4169E1', // Royal Blue background color
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Optional: Center vertically
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '4169E1'], // Black border color
                        ],
                    ],
                ]);

                // Adjust row height if needed (height in points)
                $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Set the desired row height
                $rowIndex++; // Move to next row for headers
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border
                        ],
                    ],
                ]);
                $rowIndex++;
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border
                        ],
                    ],
                ]);
                // Headers Row
                // Header Row for Questions
                $sheet->setCellValue('A' . $rowIndex, 'Sr. No');
                $sheet->mergeCells('B' . $rowIndex . ':I' . $rowIndex);
                $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex);  // Merging columns J to L for "Responses"
                $sheet->setCellValue('J' . $rowIndex, 'Responses');
                $sheet->setCellValue('M' . $rowIndex, 'Total Score');
                $sheet->setCellValue('N' . $rowIndex, 'App Score');
                $sheet->setCellValue('O' . $rowIndex, 'Ach Score');

                // Apply styles
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'], // White text color
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'A9A9A9', // Royal Blue background color
                        ],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'A9A9A9'], // Grey border color
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true, // Wrap text to ensure padding is visible
                    ],
                ]);

                // Adjust row height
                $sheet->getRowDimension($rowIndex)->setRowHeight(40); // Adjust row height to accommodate larger text

                // Apply padding
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->getAlignment()->setIndent(1); // Add padding


                $rowIndex++; // Move to next row for questions
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border
                        ],
                    ],
                ]);

                $i = 1;
                // Initialize total variables for the section
                $totalAchieved = 0;
                $totalApplicable = 0;
                $totalAchievedValue = 0;

                foreach ($section['questions'] as $question) {
                    // Question Row
                    $rowIndex++;
                    $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'], // White border
                            ],
                        ],
                    ]);
                    // Set the value and merge cells for the question name
                    $sheet->setCellValue('A' . $rowIndex, $i . ')');
                    $sheet->mergeCells('B' . $rowIndex . ':I' . $rowIndex);
                    $sheet->setCellValue('B' . $rowIndex, $question['question_name']);

                    // Apply styles
                    $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'], // Grey border color
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, // Align text to left
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center vertically
                            'wrapText' => true, // Enable text wrapping
                            'indent' => 1, // Add padding (indentation)
                        ],
                    ]);

                    // Adjust row height based on the content
                    $cellText = $question['question_name'];
                    $wrappedLines = ceil(strlen($cellText) / 80); // Approximate number of lines required (adjust the divisor as needed)
                    $baseRowHeight = 20; // Base height (adjust as needed)
                    $sheet->getRowDimension($rowIndex)->setRowHeight($baseRowHeight * $wrappedLines);
                    $rowIndex++;
                    // Move to the next row for shopper comments
                    $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'], // White border
                            ],
                        ],
                    ]);
                    $rowIndex++;
                    $sheet->mergeCells('B' . $rowIndex . ':I' . $rowIndex);

                    // Set the value in the cell with shopper comments
                    $sheet->setCellValue('B' . $rowIndex, 'Shopper Comment: ' . implode(", ", $question['comments']));

                    // Apply lemon color formatting and border
                    $sheet->getStyle('B' . $rowIndex)->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => 'FFFACD', // Lemon color (light yellow)
                            ],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'], // Black border
                            ],
                        ],
                    ]);

                    // Data Row
                    $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex);  // Merging columns J and K

                    $sheet->setCellValue('J' . $rowIndex, $question['response']);
                    $sheet->setCellValue('M' . $rowIndex, $question['total']);
                    $sheet->setCellValue('N' . $rowIndex, $question['applicable']);
                    $sheet->setCellValue('O' . $rowIndex, $question['achieved']);
                    // Apply styles to the data cells
                    $dataCells = ['J', 'M', 'N', 'O'];
                    foreach ($dataCells as $cell) {
                        $sheet->getStyle($cell . $rowIndex)->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => ['rgb' => 'FFFFFF'], // White border
                                ],
                            ],
                        ]);
                    }
                    // Move to next row for the next question
                    $rowIndex++;
                    $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'], // White border
                            ],
                        ],
                    ]);
                    $i++;

                    // Accumulate totals
                    $totalAchieved += $question['achieved'];
                    $totalApplicable += $question['applicable'];
                    $totalAchievedValue += $question['total'];
                }

                // Section Totals Row
                // Merge cells for "Section Total" row
                $sheet->mergeCells('A' . $rowIndex . ':I' . $rowIndex);

                // Set the values for the section total
                $sheet->setCellValue('A' . $rowIndex, 'Section Total');
                $sheet->setCellValue('M' . $rowIndex, $totalAchieved);
                $sheet->setCellValue('N' . $rowIndex, $totalApplicable);
                $sheet->setCellValue('O' . $rowIndex, $totalAchievedValue);

                // Apply styles to the "Section Total" row
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
                        'wrapText' => true, // Enable text wrapping
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'D3D3D3'], // Grey border color
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D3D3D3', // Grey background color
                        ],
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000'], // Black text color
                        'bold' => true, // Bold text
                    ],
                ]);

                // Optionally, set the height for the row
                $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust the height as needed

                // Optionally, set padding by adjusting the column width
                $sheet->getColumnDimension('A')->setWidth(20); // Adjust column width for padding
                $sheet->getColumnDimension('B')->setWidth(30); // Adjust column width for padding
                $sheet->getColumnDimension('C')->setWidth(20); // Adjust column width for padding
                // Repeat for other columns as needed


                // Move to the next row for the next section
                $rowIndex++;
            }



            // Save to temporary file
            $tempExcelFile = tempnam(sys_get_temp_dir(), 'xls');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempExcelFile);

            // Add file to zip
            $zip->addFile($tempExcelFile, $firstLocationName . '.xlsx');
        }

        $zip->close();

        // Return the zip file as response
        return response()->stream(function () use ($tempFile) {
            readfile($tempFile);
            unlink($tempFile); // Clean up the temp file
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment;filename="' . $zipFileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
    public function downloadExcel1(Request $request)
    {
        // dd($request->all());
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Extract parameters from the request
        $levelName = $request->input('levelName');
        $questionData = json_decode($request->input('questionData'), true);
        $sectionsData = json_decode($request->input('sectionsData'), true);
        $overAll1 = $request->input('overAll1');
        $overallreport = $request->input('overallreport');
        $allScores = json_decode($request->input('allScores'), associative: true);
        $overall = json_decode($request->input('overall'), associative: true);
        $overallreport1 = json_decode($request->input('overallreport1'), associative: true);

        // Add headers
        $columnIndex = 'A';
        // Set the value for the first cell in the range (A1)
        // Set the cell value
        $sheet->setCellValue($columnIndex . '1', $levelName);

        // Define the cell range
        $cellRange = $columnIndex . '1';

        // Apply styles to the cell(s)
        $sheet->getStyle($cellRange)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0072C6'] // Background color (blue)
            ],
            'font' => [
                'bold' => true, // Font weight
                'color' => ['rgb' => 'FFFFFF'], // Font color (white)
                'size' => 12 // Font size
            ],
            'alignment' => [
                'wrapText' => true, // Enable text wrapping
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Horizontal alignment
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Vertical alignment
            ]
        ]);

        $columnIndex++;

        // Start column index for header merging
        $startColumnIndex = $columnIndex;
        $columnIndex = 2; // Starting from B column

        if (!empty($questionData)) {
            foreach ($questionData as $question) {
                // Determine the cell coordinates
                $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 2);
                $cellRange = $startColumn . '1:' . $endColumn . '1';

                // Set the cell value and merge cells
                $sheet->setCellValue($startColumn . '1', $question['question_name']);
                $sheet->mergeCells($cellRange);

                // Apply styles to the merged cells
                $sheet->getStyle($cellRange)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0072C6'] // Background color (blue)
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // Font color (white)
                        'size' => 12 // Font size
                    ],
                    'alignment' => [
                        'wrapText' => true,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Move to the next set of columns
                $columnIndex += 3; // Adjust column width as needed
            }
        }

        // Add section headers if sectionsData is not empty
        if (empty($questionData) && !empty($sectionsData)) {
            foreach ($sectionsData as $section) {
                // Determine the cell coordinates for merging
                $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 2);
                $cellRange = $startColumn . '1:' . $endColumn . '1';

                // Set the cell value and merge the columns
                $sheet->setCellValue($startColumn . '1', $section['section_name']);
                $sheet->mergeCells($cellRange);

                // Apply styles to the merged cells
                $sheet->getStyle($cellRange)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0072C6'] // Background color (blue)
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // Font color (white)
                        'size' => 12 // Font size
                    ],
                    'alignment' => [
                        'wrapText' => true,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Move to the next set of columns
                $columnIndex += 3; // Adjust column width as needed
            }
        }

        // Add overall headers if overAll1 is not empty
        if (!empty($overAll1) && empty($sectionsData) && empty($questionData)) {
            $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 2); // Merge across 3 columns
            $cellRange = $startColumn . '1:' . $endColumn . '1';

            // Set the cell value and merge cells
            $sheet->setCellValue($startColumn . '1', 'Overall');
            $sheet->mergeCells($cellRange);

            // Apply styles
            $sheet->getStyle($cellRange)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0072C6'] // Background color (blue)
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'], // Font color (white)
                    'size' => 12 // Font size
                ],
                'alignment' => [
                    'wrapText' => true,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ]);

            // Move to the next column
            $columnIndex += 3;
        }

        // Add "Overall" header if overallreport is not empty
        if (!empty($overallreport1)) {
            $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 2); // Merge across 3 columns
            $cellRange = $startColumn . '1:' . $endColumn . '1';

            // Set the cell value and merge cells
            $sheet->setCellValue($startColumn . '1', 'Overall');
            $sheet->mergeCells($cellRange);

            // Apply styles
            $sheet->getStyle($cellRange)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0072C6'] // Background color (blue)
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'], // Font color (white)
                    'size' => 12 // Font size
                ],
                'alignment' => [
                    'wrapText' => true,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ]);

            // Move to the next column
            $columnIndex += 3;
        }


        // Add second row headers (ach, App, %)
        $columnIndex = 2; // Reset to start from column B for the second row
        $sheet->setCellValue('A2', ''); // Empty cell for level name in the second row
        if (!empty($questionData)) {
            foreach ($questionData as $question) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . '2', 'ach');
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . '2', 'App');
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 2) . '2', '%');
                // Apply alignment
                $cellRange = Coordinate::stringFromColumnIndex($columnIndex) . '2:' . Coordinate::stringFromColumnIndex($columnIndex + 2) . '2';
                $sheet->getStyle($cellRange)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);
                $columnIndex += 3; // Move to the next group of columns
            }
        } elseif (!empty($sectionsData)) {
            foreach ($sectionsData as $section) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . '2', 'ach');
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . '2', 'App');
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 2) . '2', '%');
                // Apply alignment
                $cellRange = Coordinate::stringFromColumnIndex($columnIndex) . '2:' . Coordinate::stringFromColumnIndex($columnIndex + 2) . '2';
                $sheet->getStyle($cellRange)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                $columnIndex += 3; // Move to the next group of columns
            }
        }

        if (!empty($overAll1) || !empty($overallreport1)) {

            $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 2); // Merge across 3 columns
            $cellRange = $startColumn . '1:' . $endColumn . '1';

            // Set the cell value and merge cells
            $sheet->setCellValue($startColumn . '1', 'Overall');
            $sheet->mergeCells($cellRange);

            // Apply styles
            $sheet->getStyle($cellRange)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0072C6']
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 15
                ],
                'alignment' => [
                    'wrapText' => true,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ]);

            // Add "ach", "App", and "%" under Overall header
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . '2', 'ach');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . '2', 'App');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 2) . '2', '%');
            // Apply alignment
            $cellRange = Coordinate::stringFromColumnIndex($columnIndex) . '2:' . Coordinate::stringFromColumnIndex($columnIndex + 2) . '2';
            $sheet->getStyle($cellRange)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ]);
            // Move to the next column set
            $columnIndex += 3;
        }



        // Add data rows
        $rowIndex = 3;


        if (!empty($overallreport1) && !empty($overall)) {
            foreach ($overallreport1 as $locationName => $scores) {
                $columnIndex = 'A'; // Reset column index for each location
                $sheet->setCellValue($columnIndex . $rowIndex, $locationName);

                foreach ($scores as $score) {
                    $achieved = $score['achievedscore'];
                    $applicable = $score['applicablescore'];
                    if ($achieved == 0 && $applicable == 0) {
                        $percentage = 'N/A';
                    } else {
                        // Calculate percentage if applicablescore is greater than 0
                        $percentage = $applicable > 0 ? number_format(($achieved / $applicable) * 100, 2) . '%' : '0%';
                    }
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $achieved);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $applicable);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $percentage);

                    $sheet->setCellValue(++$columnIndex . $rowIndex, $achieved);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $applicable);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $percentage);
                }

                $rowIndex++;
            }
        } elseif (empty($overallreport1) && !empty($allScores)) {
            foreach ($allScores as $locationName => $scores) {
                $columnIndex = 'A'; // Reset column index for each location
                $sheet->setCellValue($columnIndex . $rowIndex, $locationName);

                foreach ($scores as $score) {
                    $achieved = $score['achievedscore'];
                    $applicable = $score['applicablescore'];
                    if ($achieved == 0 && $applicable == 0) {
                        $percentage = 'N/A';
                    } else {
                        // Calculate percentage if applicablescore is greater than 0
                        $percentage = $applicable > 0 ? number_format(($achieved / $applicable) * 100, 2) . '%' : '0%';
                    }
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $achieved);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $applicable);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $percentage);
                }

                $rowIndex++;
            }
        } elseif (!empty($overallreport1) && !empty($allScores)) {
            foreach ($allScores as $locationName => $scores) {
                $columnIndex = 'A'; // Reset column index for each location
                $sheet->setCellValue($columnIndex . $rowIndex, $locationName);
                // Initialize variables to sum achieved and applicable scores
                $totalAchieved = 0;
                $totalApplicable = 0;
                foreach ($scores as $score) {
                    $achieved = $score['achievedscore'];
                    $applicable = $score['applicablescore'];
                    if ($achieved == 0 && $applicable == 0) {
                        $percentage = 'N/A';
                    } else {
                        // Calculate percentage if applicablescore is greater than 0
                        $percentage = $applicable > 0 ? number_format(($achieved / $applicable) * 100, 2) . '%' : '0%';
                    }
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $achieved);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $applicable);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $percentage);
                    // Update totals
                    $totalAchieved += $achieved;
                    $totalApplicable += $applicable;
                }
                $sheet->setCellValue(++$columnIndex . $rowIndex, $totalAchieved);
                $sheet->setCellValue(++$columnIndex . $rowIndex, $totalApplicable);
                $sheet->setCellValue(++$columnIndex . $rowIndex, $totalApplicable > 0 ? number_format(($totalAchieved / $totalApplicable) * 100, 2) . '%' : '0%');
                $rowIndex++;
            }
        } elseif (!empty($overallreport1)) {
            foreach ($overallreport1 as $locationName => $scores) {
                $columnIndex = 'A'; // Reset column index for each location
                $sheet->setCellValue($columnIndex . $rowIndex, $locationName);

                foreach ($scores as $score) {
                    $achieved = $score['achievedscore'];
                    $applicable = $score['applicablescore'];
                    if ($achieved == 0 && $applicable == 0) {
                        $percentage = 'N/A';
                    } else {
                        // Calculate percentage if applicablescore is greater than 0
                        $percentage = $applicable > 0 ? number_format(($achieved / $applicable) * 100, 2) . '%' : '0%';
                    }
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $achieved);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $applicable);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $percentage);
                }

                $rowIndex++;
            }
        } elseif (!empty($overall)) {
            foreach ($overall as $locationName => $scores) {
                $columnIndex = 'A'; // Reset column index for each location
                $sheet->setCellValue($columnIndex . $rowIndex, $locationName);

                foreach ($scores as $score) {
                    $achieved = $score['achievedscore'];
                    $applicable = $score['applicablescore'];
                    if ($achieved == 0 && $applicable == 0) {
                        $percentage = 'N/A';
                    } else {
                        // Calculate percentage if applicablescore is greater than 0
                        $percentage = $applicable > 0 ? number_format(($achieved / $applicable) * 100, 2) . '%' : '0%';
                    }
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $achieved);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $applicable);
                    $sheet->setCellValue(++$columnIndex . $rowIndex, $percentage);
                }

                $rowIndex++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="report.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }



    public function bulkPdfDownload(Request $request)
    {
        $shopIds = $request->query('ids'); // Retrieve IDs from the query string
        $idsArray = array_map('intval', explode(',', $shopIds)); // Convert IDs to integers

        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');

        if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Unable to create ZIP file'], 500);
        }

        foreach ($idsArray as $shopID) {
            // Fetch shop details
            $shopDetails = DB::table('assignshops')->find($shopID);
            if (!$shopDetails) continue;

            $hierarchyLevels = $this->fetchHierarchyLevels($shopDetails->location_id);
            $firstLocationName = '';
            if (!empty($hierarchyLevels)) {
                $firstLocationName = $hierarchyLevels[0]->location_name;
            }
            $overallScore = DB::table('scoreanalysics')
                ->selectRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overallscore')
                ->where('shop_id', $shopID)
                ->value('overallscore');

            $sectionScores = Scoreanalysics::select(
                DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),
                DB::raw('SUM(achieved) as achieved'),
                DB::raw('SUM(applicable) as applicable'),
                DB::raw('SUM(total) as total'),
                'section_name',
                'section_id'
            )->where('shop_id', $shopID)->groupBy('section_id', 'section_name')->get();

            $overallresult = $this->prepareOverallResult($shopID, $sectionScores);

            // Generate and add PDF to ZIP
            $pdf = PDFDom::loadView('client.report.pdfdownload', [
                'hierarchyLevels' => $hierarchyLevels,
                'time' => $shopDetails->timeIn,
                'date' => $shopDetails->date,
                'overAllScore' => $overallScore,
                'sectionScores' => $sectionScores,
                'overallresult' => $overallresult,
            ])->setPaper('a4', 'portrait');

            $pdfFileName = "{$firstLocationName}.pdf";
            $pdfContent = $pdf->output();

            $zip->addFromString($pdfFileName, $pdfContent);
        }

        $zip->close();

        return response()->stream(function () use ($tempFile) {
            readfile($tempFile);
            unlink($tempFile);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="shop_reports.zip"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function fetchHierarchyLevels($initialHierarchyId)
    {
        $query = "WITH RECURSIVE HierarchyCTE AS (
            SELECT h.id AS hierarchy_id, h.levelID AS level_id, hl.hierarchylavelname AS level_name,
                   hl.level AS level, hl.HID AS hid, l.locationname AS location_name, h.parentID AS parent_id
            FROM hierarchies h
            INNER JOIN hierarchylevels hl ON h.levelID = hl.id
            INNER JOIN locations l ON h.LID = l.id
            WHERE h.id = :initialHierarchyId
            UNION ALL
            SELECT h.id AS hierarchy_id, h.levelID AS level_id, hl.hierarchylavelname AS level_name, hl.level AS level,
                   hl.HID AS hid, l.locationname AS location_name, h.parentID AS parent_id
            FROM hierarchies h
            INNER JOIN hierarchylevels hl ON h.levelID = hl.id
            INNER JOIN locations l ON h.LID = l.id
            INNER JOIN HierarchyCTE hc ON hc.parent_id = h.id
        )
        SELECT hierarchy_id, level_id, level_name, level, hid, location_name
        FROM HierarchyCTE;";

        return DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);
    }

    private function prepareOverallResult($shopID, $sectionScores)
    {
        $overallresult = [];
        foreach ($sectionScores as $sectionScore) {
            $questions = Scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')
                ->where('scoreanalysics.section_id', $sectionScore->section_id)
                ->where('scoreanalysics.shop_id', $shopID)
                ->orderBy('questions.orderby')
                ->get(['scoreanalysics.*', 'questions.*']);

            $questionsData = [];
            foreach ($questions as $question) {
                $comments = Comments::where('question_id', $question->question_id)
                    ->where('shop_id', $shopID)
                    ->pluck('comments')
                    ->toArray();

                $questionsData[] = [
                    'question_id' => $question->question_id,
                    'question_name' => $question->question_name,
                    'response' => $question->response,
                    'achieved' => $question->achieved,
                    'applicable' => $question->applicable,
                    'total' => $question->total,
                    'comments' => $comments
                ];
            }

            $overallresult[] = [
                'section_id' => $sectionScore->section_id,
                'section_name' => $sectionScore->section_name,
                'section_score' => $sectionScore->sectionScore,
                'questions' => $questionsData
            ];
        }

        return $overallresult;
    }


    public function visitreport(Request $request)

    {

        session::put('title', "Visit Report");

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        $format = DB::table('formats')

            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')

            ->where('formats.id',  $format_id)

            ->select('formats.*', 'hierarchylevels.*')

            ->get();

        // $format = DB::table('formats')

        //     ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')

        //     ->join('waves', 'formats.id', '=', 'waves.format_id')

        //     ->where('formats.id', $format_id)

        //     ->where('waves.id', $wave_id)

        //     ->select('formats.*', 'hierarchylevels.*', 'waves.*')

        //     ->get();

        // dd($format);

        // Retrieve all shops for the client with the given format_id and wave_id

        $shopDetailsList = DB::table('assignshops')

            ->where('format_id',  $format_id)

            ->where('wave_id', $wave_id1)

            ->where('status', "submit to client")

            ->get();



        $allHierarchyLevels = [];



        foreach ($shopDetailsList as $shopDetails) {

            $locationID = $shopDetails->location_id;
            $shopID = $shopDetails->id;

            // Recursive query to fetch hierarchical data for each shop

            $query = "

                WITH RECURSIVE HierarchyCTE AS (

                    SELECT 

                        h.id AS hierarchy_id,

                        h.levelID AS level_id,

                        hl.hierarchylavelname AS level_name,

                        hl.level AS level,

                        hl.HID AS hid,

                        l.locationname AS location_name,

                        h.branch_code AS branch_code,  -- Added location_code

                        h.address AS address,              -- Added address

                        h.parentID AS parent_id

                    FROM 

                        hierarchies h

                    INNER JOIN 

                        hierarchylevels hl ON h.levelID = hl.id

                    INNER JOIN 

                        locations l ON h.LID = l.id

                    WHERE 

                        h.id = :initialHierarchyId

            

                    UNION ALL

            

                    SELECT 

                        h.id AS hierarchy_id,

                        h.levelID AS level_id,

                        hl.hierarchylavelname AS level_name,

                        hl.level AS level,

                        hl.HID AS hid,

                        l.locationname AS location_name,

                        h.branch_code AS branch_code,  -- Added location_code

                        h.address AS address,              -- Added address

                        h.parentID AS parent_id

                    FROM 

                        hierarchies h

                    INNER JOIN 

                        hierarchylevels hl ON h.levelID = hl.id

                    INNER JOIN 

                        locations l ON h.LID = l.id

                    INNER JOIN 

                        HierarchyCTE hc ON hc.parent_id = h.id

                )

                SELECT 

                    hierarchy_id,

                    level_id,

                    level_name,

                    level,

                    hid,

                    location_name,

                    branch_code,  -- Include location_code in the result

                    address          -- Include address in the result

                FROM 

                    HierarchyCTE;

                ";



            // Execute query and fetch results for the current shop

            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $locationID]);

            $hierarchyLevels = array_reverse($hierarchyLevels);



            // Collect the results for all shops with locationID included

            $allHierarchyLevels[] = [

                'locationID' => $locationID,
                'hierarchyLevels' => $hierarchyLevels,
                'shopID' =>  $shopID

            ];
        }

        // dd($allHierarchyLevels);

        return view('client.visitRepport', [

            'headerName' => $format,

            'reports' => $allHierarchyLevels,

            // Add other variables to pass to the view as needed

        ]);
    }



    public function viewReport($id)

    {

        // echo $id;

        // exit();

        $shopID = $id;

        session::put('title', "Visit Report");

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        $shopDetails = DB::table('assignshops')

            ->where('id', $shopID)

            ->first();

        // dd($shopDetails);

        // $status1 = $shopDetails->status;

        $status1 =  $shopDetails->status;

        $formatID = $shopDetails->format_id;

        $waveID =  $shopDetails->wave_id;

        $initialHierarchyId = $shopDetails->location_id;



        $time =  $shopDetails->timeIn;

        $date = $shopDetails->date;

        // Recursive query to fetch hierarchical data

        $query = "

    WITH RECURSIVE HierarchyCTE AS (

        SELECT 

            h.id AS hierarchy_id,

            h.levelID AS level_id,

            hl.hierarchylavelname AS level_name,

            hl.level AS level,

            hl.HID AS hid,

            l.locationname AS location_name,

            h.parentID AS parent_id

        FROM 

            hierarchies h

        INNER JOIN 

            hierarchylevels hl ON h.levelID = hl.id

        INNER JOIN 

            locations l ON h.LID = l.id

        WHERE 

            h.id = :initialHierarchyId



        UNION ALL



        SELECT 

            h.id AS hierarchy_id,

            h.levelID AS level_id,

            hl.hierarchylavelname AS level_name,

            hl.level AS level,

            hl.HID AS hid,

            l.locationname AS location_name,

            h.parentID AS parent_id

        FROM 

            hierarchies h

        INNER JOIN 

            hierarchylevels hl ON h.levelID = hl.id

        INNER JOIN 

            locations l ON h.LID = l.id

        INNER JOIN 

            HierarchyCTE hc ON hc.parent_id = h.id

    )

    SELECT 

        hierarchy_id,

        level_id,

        level_name,

        level,

        hid,

        location_name

    FROM 

        HierarchyCTE;";



        // Execute query and fetch results

        $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);



        $shopoverall = branchCalculations::where('shop_id', $shopID)->get();

        $criterias = Criteria::where('format_id', $format_id)->get();



        foreach ($shopoverall as $calculation) {

            $overAllScore = $calculation->overAllScore;
        }

        $conditionLabel = ""; // Initialize a variable to store the condition label



        foreach ($criterias as $criteria) {

            $operator = $criteria->operator;

            $range1 = $criteria->range1;

            $range2 = $criteria->range2;



            switch ($operator) {

                case ">":

                    if ($overAllScore > $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case ">=":

                    if ($overAllScore >= $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "<":

                    if ($overAllScore < $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "<=":

                    if ($overAllScore <= $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "b/w":

                    if ($overAllScore > $range1 && $overAllScore < $range2) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                case "==":

                    if ($overAllScore == $range1) {

                        $conditionLabel = $criteria->label;
                    }

                    break;

                    // Add other cases here...

            }



            // If we found a matching condition, break the loop to avoid unnecessary iterations

            if ($conditionLabel != "") {

                break;
            }
        }

        $sectionScores = scoreanalysics::select(

            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),

            'section_name',

            'section_id'

        )

            ->where('shop_id', $shopID)

            ->groupBy('section_id', 'section_name')

            ->get();



        $overallresult = [];



        foreach ($sectionScores as $sectionScore) {

            $sectionID = $sectionScore->section_id;

            $sectionName = $sectionScore->section_name;



            $questions = scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')

                ->where('scoreanalysics.section_id', $sectionID)

                ->where('scoreanalysics.shop_id', $shopID)

                ->orderBy('questions.orderby')

                ->get(['scoreanalysics.*', 'questions.*']);



            $questionsData = [];



            foreach ($questions as $question) {

                $question_id = $question->question_id;

                $question_name = $question->question_name;

                $response = $question->response;

                $achieved = $question->achieved;

                $applicable = $question->applicable;

                $total = $question->total;
                $subquestionaname = $question->subquestionaname;
               $subqID = $question->subqID;


                $comments = comments::where('question_id', $question_id)

                    ->where('shop_id', $shopID)

                    ->get()

                    ->pluck('comments')

                    ->toArray();



                $questionsData[] = [

                    'question_id' => $question_id,

                    'question_name' => $question_name,

                    'response' => $response,

                    'achieved' => $achieved,

                    'applicable' => $applicable,

                    'total' => $total,

                    'comments' => $comments,
                    'subquestionaname'  => $subquestionaname,
                    'subqID'  => $subqID,



                ];
            }



            $overallresult[] = [

                'section_id' => $sectionID,

                'section_name' => $sectionName,

                'section_score' => $sectionScore->sectionScore,
                 'shopID' =>  $shopID,
                'questions' => $questionsData

            ];
        }





        $visitAudioRecords = VisitAudioRecord::where('shop_id', $shopID)->get();

        // dd($visitAudioRecords);

        $embedvideo = ''; // Variable to store video URL

        $audioUrls = []; // Array to store audio URLs

        $receiptUrls = []; // Array to store receipt image URLs



        foreach ($visitAudioRecords as $key => $row) {

            $fileType = $row['type'];

            $fileName = $row['attachmentname'];

            $fileUrl = "public/uploads/$fileName";



            switch ($fileType) {

                case 'audio':

                    // Store audio URL

                    $audioUrls[] = $fileUrl;

                    break;

                case 'image':

                    // Store receipt image URL

                    $receiptUrls[] = $fileUrl;

                    break;

                case 'video':

                    // Store video URL for embedding

                    $embedvideo = $fileName;

                    break;

                default:

                    // Handle unknown file types or log an error

                    error_log("Unknown file type: $fileType");

                    break;
            }
        }

        // dd($audioUrls);

        // $lostopertunity = scoreanalysics::join('comments', 'scoreanalysics.question_id', '=', 'comments.question_id')

        //     ->where('scoreanalysics.shop_id', $shopID)

        //     // ->where('comments.shop_id', $shopID)

        //     ->whereColumn('scoreanalysics.achieved', '<', 'scoreanalysics.applicable')

        //     ->where('scoreanalysics.applicable', '>', 0)

        //     ->get();

        // $lostopertunity = DB::table('scoreanalysics')

        // ->select('section_name', 'question_name')  // Specify the columns you want to select

        // ->where('achieved', '<', 'applicable')

        // ->where('applicable', '>', 0)

        // ->where('shop_id', 2)

        // ->get();

        $lostopertunity = scoreanalysics::where('shop_id', $shopID)

            ->where('applicable', '>', 0)  // Ensure applicable score is greater than 0

            ->whereColumn('achieved', '<', 'applicable')  // Compare achieved and applicable scores

            ->get();

        // dd($lostopertunity);


        // $contests = contests::where('shop_id',  $shopID)->get();
        $contests = DB::table('contests')
            ->join('users', 'contests.client_id', '=', 'users.id') // Assuming client_id is the foreign key
            ->where('contests.shop_id', $shopID)
            ->select(
                'contests.contest as comment',
                'contests.comentby as sendto',
                DB::raw('DATE_FORMAT(contests.created_at, "%Y-%m-%d %H:%i:%s") as datetime'), // Format datetime in the query
                'contests.branchName',
                'users.name as clientname'
            )
            ->get();
        $result = [];

        foreach ($contests as $contest) {
            $datetime = $contest->datetime; // No need to format again since it's already formatted
            $branchName = $contest->branchName;
            $sendto = $contest->sendto;
            $clientName = $contest->clientname;

            // Check if branchName and sendto are the same
            if ($branchName === $sendto) {
                // Both are the same, so include branch name
                $result[] = [
                    'comment' => $contest->comment,
                    'datetime' => $datetime,
                    'from' => $branchName,
                    'TO' => "Startex Admin, $clientName",
                ];
            } elseif ($branchName !== $sendto && $sendto !== "Startex Admin") {
                // Different, include client name instead
                $result[] = [
                    'comment' => $contest->comment,
                    'datetime' => $datetime,
                    'from' => $clientName,
                    'TO' => "Startex Admin, $branchName",
                ];
            } else {
                // When sendto is "Startex Admin"
                $result[] = [
                    'comment' => $contest->comment,
                    'datetime' => $datetime,
                    'from' => "Startex Admin",
                    'TO' => "$branchName, $clientName",
                ];
            }
        }
        // dd($result);

        return view('client.report.viewReport', [

            'hierarchyLevels' => $hierarchyLevels,

            'time' => $time,

            'date' =>  $date,

            'overAllScore' => $overAllScore,

            'conditionLabel' => $conditionLabel,

            'sectionScores' =>  $sectionScores,

            'embedvideo' => $embedvideo,

            'audioUrls' => $audioUrls,

            'receiptUrls' => $receiptUrls,

            'lostopertunity' => $lostopertunity,

            'overallresult' => $overallresult,

            'shopID' => $shopID,
            'contests' => $result,

        ]);
    }



    public function reportdashboard($id)

    {

        // dd($id);

        $shopID = $id;

        $shopDetails = DB::table('assignshops')

            ->where('id', $shopID)

            ->first();

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        session::put('title', "Dashboard");

        $criterias = Criteria::where('format_id', $format_id)->get();
        $strengthRange = $criterias->first();
        $weaknessRange = $criterias->last();
        $strenghtcriteria = $strengthRange->range1;
        $weaknesscriteria = $weaknessRange->range1;


        $strenghtAndWeekness = scoreanalysics::select(DB::raw('question_name as QuestionName'), DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as score'))

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '=', $wave_id1)

            ->where('scoreanalysics.shop_id', '=', $shopID)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('scoreanalysics.question_id', 'scoreanalysics.question_name')

            ->get();

        // echo  $strengthRange->range1;

        // echo  $weaknessRange->range1;

        $strengths = [];

        $weaknesses = [];



        foreach ($strenghtAndWeekness  as $result) {

            if ($result->score >= $strengthRange->range1) { // Assuming strength criteria uses range1

                $strengths[] = $result;
            } elseif ($result->score <= $weaknessRange->range1) { // Assuming weakness criteria uses range2

                $weaknesses[] = $result;
            }
        }





        //trend start

        $trend = DB::table('scoreanalysics')

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

            ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '<=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.status', "submit to client")

            ->groupBy('scoreanalysics.wave_id', 'waves.name')

            ->get();

        $res = [];



        foreach ($trend as $value) {

            $indexLabel = $value->wave_score . " ";

            $res[] = array(

                "y" => $value->wave_score,

                "indexLabel" => $indexLabel,

                "label" => $value->waveName

            );
        }

        $values = array_reverse($res);

        $currentWaveScore = $values[0]['indexLabel'] ?? null;

        $previousWaveScore = $values[1]['indexLabel'] ?? null;





        $tredresult = $res;

        $completereport =     DB::table('scoreanalysics')

            ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

            ->join('waves', 'scoreanalysics.wave_id', '=', 'waves.id')

            ->selectRaw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) AS wave_score, scoreanalysics.wave_id, waves.name AS waveName')

            ->where('scoreanalysics.format_id', $format_id)

            ->where('scoreanalysics.wave_id', '=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.status', "submit to client")

            ->groupBy('scoreanalysics.wave_id', 'waves.name')

            ->get();

        $waves = DB::table('waves')

            ->where('format_id', $format_id)

            ->orderBy('id', 'desc')

            ->get();



        $previousWaveId = null;

        foreach ($waves as $index => $value) {

            $waveId = $value->id;

            if ($waveId == $wave_id1 && isset($waves[$index + 1])) {

                $previousWaveId = $waves[$index + 1]->id;

                break;
            }
        }

        // Fetch current sections with scores

        $curentsections = Scoreanalysics::selectRaw(

            'ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overallscore, scoreanalysics.section_name as sectionName'

        )

            ->join('assignshops', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->where('scoreanalysics.wave_id', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('scoreanalysics.section_id', 'scoreanalysics.section_name')

            ->get();



        // Fetch previous sections with scores

        $previoussections = Scoreanalysics::selectRaw(

            'ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overallscore, scoreanalysics.section_name as sectionName'

        )

            ->join('assignshops', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->where('scoreanalysics.wave_id', $previousWaveId)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('scoreanalysics.section_id', 'scoreanalysics.section_name')

            ->get();



        // Convert collections to associative arrays for easier merging

        $currentSectionsArray = $curentsections->keyBy('sectionName')->toArray();

        $previousSectionsArray = $previoussections->keyBy('sectionName')->toArray();



        // Combine results

        $combinedSections = [];



        foreach ($currentSectionsArray as $sectionName => $currentSection) {

            $combinedSections[$sectionName] = [

                'sectionName' => $sectionName,

                'current' => $currentSection['overallscore'],

                'previous' => $previousSectionsArray[$sectionName]['overallscore'] ?? null, // Use null if not present

            ];
        }

        $vistreports = branchCalculations::select(

            'assignshops.id as shop_id',

            'assignshops.wave_id as wave_id',

            'branch_calculations.overAllScore as overallscore',

            'waves.name as waveName'

        )

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('waves', 'assignshops.wave_id', '=', 'waves.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->where('assignshops.wave_id', '<=', $wave_id1)

            ->get();

        // dd($vistreports);

        $shopCount = assignshops::where('location_id', $shopDetails->location_id)

            ->where('status', 'submit to client')

            ->where('wave_id', '<=', $wave_id1)

            ->where('format_id', '=', $format_id)

            ->count();

        $percentage = BranchCalculations::select(DB::raw('SUM(branch_calculations.overAllScore) AS percentage'))

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('assignshops.location_id', $shopDetails->location_id)

            ->groupBy('branch_calculations.format_id')

            ->value('percentage'); // Retrieves the calculated percentage



        $total = $shopCount * 100;

        $ytd = round($percentage / $total * 100);

        $ids = assignshops::where('location_id', $shopDetails->location_id)

            ->where('status', 'submit to client')

            ->where('wave_id', '<=', $wave_id1)

            ->pluck('id'); // Retrieve only the 'id' column

        $totalShopCount = $ids->count();

        $halfShopCount = ($totalShopCount / 2);

        // dd($halfShopCount);

        $recuruing = [];

        foreach ($ids as $id) {

            $shopid = $id; // Since $id is already the ID, no need for alias

            $records = Scoreanalysics::where('shop_id',  $shopid)->get();

            foreach ($records  as $record) {

                if ($record->achieved == 0 && $record->applicable > 0) {

                    $recuruing[] = [

                        'section_name' => $record->section_name,

                        'question_name' => $record->question_name,

                        'question_id' => $record->question_id

                    ];
                }
            }
        }

        // Initialize an array to store the counts along with section and question names

        $questionIdData = [];



        // Process each record to count occurrences and store additional info

        foreach ($recuruing as $item) {

            $questionId = $item['question_id'];



            if (!isset($questionIdData[$questionId])) {

                $questionIdData[$questionId] = [

                    'section_name' => $item['section_name'],

                    'question_name' => $item['question_name'],

                    'count' => 0

                ];
            }



            // Increment the count

            $questionIdData[$questionId]['count']++;
        }





        // dd($questionIdData);

        // dd($recuruing);

        // $repotsscore = branchCalculations::where('format_id', $format_id)

        //     ->where('ave_id', '=', $wave_id1)

        //     ->orderBy('overAllScore', 'desc')  // Sort by score in descending order

        //     ->get();

        $repotsscore = branchCalculations::join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', $wave_id1)

            ->where('assignshops.status', 'submit to client')

            ->orderBy('branch_calculations.overAllScore', 'desc')

            ->get(['branch_calculations.*', 'assignshops.*']);  // Adjust the selected columns as needed



        // dd($repotsscore);

        // Initialize an array to hold formatted data with ranks

        $rankedData = [];

        $currentRank = 1;

        $lastScore = null;

        $lastRank = 1;

        $targetLocationRank = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore === $item->overAllScore) {

                $rank = $lastRank;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank;  // Assign the current rank

                $lastRank = $currentRank;  // Update last rank

            }



            $rankedData[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore = $item->overAllScore;

            $currentRank++;
        }



        // Debug output to see the array with ranks

        // dd($rankedData);

        // dd($targetLocationRank);





        $repotsscore2 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('assignshops.status', "submit to client")

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData2 = [];

        $currentRank2 = 1;

        $lastScore2 = null;

        $lastRank2 = 1;

        $targetLocationRank2 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore2 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore2 === $item->overAllScore) {

                $rank = $lastRank2;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank2;  // Assign the current rank

                $lastRank = $currentRank2;  // Update last rank

            }



            $rankedData2[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank2 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore2 = $item->overAllScore;

            $currentRank2++;
        }

        // Debug output to see the results

        // dd($repotsscore2);

        $regionId = branchCalculations::where('shop_id', $shopID)->pluck('region_id');

        // dd($regionId);

        // exit();

        $repotsscore3 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '<=', $wave_id1)

            ->where('branch_calculations.region_id', '=', $regionId)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData3 = [];

        $currentRank3 = 1;

        $lastScore3 = null;

        $lastRank3 = 1;

        $targetLocationRank3 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore3 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore3 === $item->overAllScore) {

                $rank = $lastRank3;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank3;  // Assign the current rank

                $lastRank = $currentRank3;  // Update last rank

            }



            $rankedData3[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank3 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore3 = $item->overAllScore;

            $currentRank3++;
        }

        $repotsscore4 = DB::table('branch_calculations')

            ->join('assignshops', 'branch_calculations.shop_id', '=', 'assignshops.id')

            ->join('scoreanalysics', 'assignshops.id', '=', 'scoreanalysics.shop_id')

            ->select(

                DB::raw('ROUND(SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable) * 100) as overAllScore'),

                'branch_calculations.branchName',

                'assignshops.location_id'

            )

            ->where('branch_calculations.format_id', $format_id)

            ->where('branch_calculations.wave_id', '=', $wave_id1)

            ->where('branch_calculations.region_id', '=', $regionId)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('assignshops.location_id', 'branch_calculations.branchName')

            ->orderBy('overAllScore', 'desc')

            ->get();



        $rankedData4 = [];

        $currentRank4 = 1;

        $lastScore4 = null;

        $lastRank4 = 1;

        $targetLocationRank4 = null;

        // Iterate through the results and assign ranks

        foreach ($repotsscore4 as $index => $item) {

            // Check if the score is the same as the last score

            if ($lastScore4 === $item->overAllScore) {

                $rank = $lastRank4;  // Assign the same rank as the last score

            } else {

                $rank = $currentRank4;  // Assign the current rank

                $lastRank = $currentRank4;  // Update last rank

            }



            $rankedData4[] = [

                'locationID' => $item->location_id,

                'location' => $item->branchName,

                'overall_score' => $item->overAllScore,

                'rank' => $rank,

            ];

            if ($item->location_id == $shopDetails->location_id) {

                $targetLocationRank4 = $rank; // Store the rank of the target location

            }

            // Update lastScore and increment rank counter

            $lastScore4 = $item->overAllScore;

            $currentRank4++;
        }

        return view('client.report.reportdashboard', [

            'tredresult' => $tredresult,

            'strengths' =>   $strengths,

            'weaknesses' => $weaknesses,

            'completereport' => $completereport,

            'curentsections' =>  $curentsections,

            'previoussections' =>  $previoussections,

            'combinedSections' => $combinedSections,

            'vistreports' => $vistreports,

            'shopCount' => $shopCount,

            'ytd' => $ytd,

            'currentWaveScore' =>  $currentWaveScore,

            'previousWaveScore' =>   $previousWaveScore,

            'questionIdData' => $questionIdData,

            'halfShopCount' => $halfShopCount,

            'targetLocationRank' => $targetLocationRank,

            'targetLocationRank2' => $targetLocationRank2,

            'targetLocationRank3' => $targetLocationRank3,

            'targetLocationRank4' => $targetLocationRank4,
            'strenghtcriteria' => $strenghtcriteria,
            'weaknesscriteria' =>  $weaknesscriteria,

        ]);
    }





    public function exceldownload($id)

    {
        $shopID = $id;
        session::put('title', "Visit Report");
        $format_id =  session::get('format_id');
        $wave_id1 = session::get('wave_id1');
        $wave_id = session::get('wave_id');
        $ytd = Session::get('YTD');
        $shopDetails = DB::table('assignshops')->where('id', $shopID)->first();
        // dd($shopDetails);
        // $status1 = $shopDetails->status;
        $status1 =  $shopDetails->status;
        $formatID = $shopDetails->format_id;
        $waveID =  $shopDetails->wave_id;
        $initialHierarchyId = $shopDetails->location_id;
        $time =  $shopDetails->timeIn;
        $date = $shopDetails->date;
        // cursive query to fetch hierarchical data
        $query = "WITH RECURSIVE HierarchyCTE AS (  SELECT  h.id AS hierarchy_id,  h.levelID AS level_id, hl.hierarchylavelname AS level_name,
                 hl.level AS level, hl.HID AS hid, l.locationname AS location_name,h.parentID AS parent_id FROM hierarchies h INNER JOIN 
            hierarchylevels hl ON h.levelID = hl.id  INNER JOIN  locations l ON h.LID = l.id WHERE  h.id = :initialHierarchyId
             UNION ALL SELECT   h.id AS hierarchy_id,h.levelID AS level_id, hl.hierarchylavelname AS level_name, hl.level AS level,
            hl.HID AS hid, l.locationname AS location_name,h.parentID AS parent_id FROM 
            hierarchies h INNER JOIN 
            hierarchylevels hl ON h.levelID = hl.id
        INNER JOIN 
            locations l ON h.LID = l.id
        INNER JOIN 
            HierarchyCTE hc ON hc.parent_id = h.id ) SELECT  hierarchy_id,  level_id,  level_name,level, hid,location_name FROM  HierarchyCTE;";
        // Execute query and fetch results
        $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $initialHierarchyId]);
        // $shopoverall = branchCalculations::where('shop_id', $shopID)->get();
        $overallScore = DB::table('scoreanalysics')
            ->selectRaw('ROUND(SUM(achieved) / SUM(applicable) * 100) as overallscore')
            ->where('shop_id', $shopID)
            ->pluck('overallscore')
            ->first();
        $criterias = Criteria::where('format_id', $format_id)->get();
        // dd($overallScore);
        $overAllScore = $overallScore;
        $conditionLabel = ""; // Initialize a variable to store the condition label
        $sectionScores = scoreanalysics::select(
            DB::raw('ROUND(SUM(achieved) / SUM(applicable) * 100) as sectionScore'),
            DB::raw('SUM(achieved) as achieved'),
            DB::raw('SUM(applicable) as applicable'),
            DB::raw('SUM(total) as total'),
            'section_name',
            'section_id'
        )->where('shop_id', $shopID)
            ->groupBy('section_id', 'section_name')
            ->get();
        $overallresult = [];
        foreach ($sectionScores as $sectionScore) {
            $sectionID = $sectionScore->section_id;
            $sectionName = $sectionScore->section_name;
            $questions = scoreanalysics::join('questions', 'scoreanalysics.question_id', '=', 'questions.id')
                ->where('scoreanalysics.section_id', $sectionID)
                ->where('scoreanalysics.shop_id', $shopID)
                ->orderBy('questions.orderby')
                ->get(['scoreanalysics.*', 'questions.*']);
            $questionsData = [];
            foreach ($questions as $question) {
                $question_id = $question->question_id;
                $question_name = $question->question_name;
                $response = $question->response;
                $achieved = $question->achieved;
                $applicable = $question->applicable;
                $total = $question->total;
                $comments = comments::where('question_id', $question_id)
                    ->where('shop_id', $shopID)
                    ->get()
                    ->pluck('comments')
                    ->toArray();
                $questionsData[] = [
                    'question_id' => $question_id,
                    'question_name' => $question_name,
                    'response' => $response,
                    'achieved' => $achieved,
                    'applicable' => $applicable,
                    'total' => $total,
                    'comments' => $comments
                ];
            }
            $overallresult[] = [
                'section_id' => $sectionID,
                'section_name' => $sectionName,
                'section_score' => $sectionScore->sectionScore,
                'questions' => $questionsData
            ];
        }
        $firstLocationName = '';
        if (!empty($hierarchyLevels)) {
            $firstLocationName = $hierarchyLevels[0]->location_name;
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setShowGridlines(false);

        $headerStyleArray = [
            'font' => [
                'bold' => true, // Bold text
                'size' => 20,   // Font size
                'color' => ['rgb' => '000000'], // Black text color
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF808080', // Grey background color
                ],
            ],
        ];
        // Apply the header style to the "MYSTERY SHOPPING REPORT" cell
        $sheet->setCellValue('A1', 'MYSTERY SHOPPING REPORT');
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->applyFromArray($headerStyleArray);
        // Set row height for the header row
        $sheet->getRowDimension(1)->setRowHeight(40); // Adjust the height as needed
        // Optionally, adjust column width to add padding
        $sheet->getColumnDimension('A')->setWidth(20); // Adjust width for padding
        // Add the score row
        $sheet->setCellValue('A2', 'This Visit Score: ' . $overAllScore . '%');
        $sheet->mergeCells('A2:O2');
        // Apply styling to the score row
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true, // Bold text
                'size' => 18,   // Font size
                'color' => ['rgb' => '000000'], // Black text color
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFD3D3D3', // Yellow background color
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                    'color' => ['rgb' => 'FFD3D3D3'], // Black borders
                ],
            ],
        ]);

        // Add hierarchy levels and visit details

        $rowIndex = 4;
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);
        foreach ($hierarchyLevels as $level) {
            $sheet->setCellValue('A' . $rowIndex, $level->level_name);
            $sheet->setCellValue('B' . $rowIndex, $level->location_name);
            $sheet->mergeCells('C' . $rowIndex . ':O' . $rowIndex); // Merge Section Name header across A to L
            $sheet->setCellValue('C' . $rowIndex, '');
            // Apply styling to hierarchy level rows
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                        'color' => ['rgb' => 'FFFFFF'], // White borders
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFFFFF', // Optional: White background color
                    ],
                ],
            ]);

            // Optionally, adjust row height for padding
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed
            // Optionally, adjust column width for padding
            $sheet->getColumnDimension('A')->setWidth(25); // Adjust column width for padding
            $sheet->getColumnDimension('B')->setWidth(30); // Adjust column width for padding
            $rowIndex++;
        }
        $sheet->setCellValue('A' . $rowIndex, 'Visit Date');
        $sheet->setCellValue('B' . $rowIndex, $date);
        $sheet->mergeCells('C' . $rowIndex . ':O' . $rowIndex); // Merge Section Name header across A to L
        $sheet->setCellValue('C' . $rowIndex, '');
        // Apply styling to visit date row
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                    'color' => ['rgb' => 'FFFFFF'], // White borders
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFFFF', // Optional: White background color
                ],
            ],
        ]);
        // Optionally, adjust row height for padding
        $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed
        // Optionally, adjust column width for padding
        $sheet->getColumnDimension('A')->setWidth(25); // Adjust column width for padding
        $sheet->getColumnDimension('B')->setWidth(30); // Adjust column width for padding
        $rowIndex++;
        $sheet->setCellValue('A' . $rowIndex, 'Visit Time');
        $sheet->setCellValue('B' . $rowIndex, $time);
        $sheet->mergeCells('C' . $rowIndex . ':O' . $rowIndex); // Merge Section Name header across A to L
        $sheet->setCellValue('C' . $rowIndex, '');
        // Apply styling to visit date row
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                    'color' => ['rgb' => 'FFFFFF'], // White borders
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFFFF', // Optional: White background color
                ],
            ],
        ]);
        // Optionally, adjust row height for padding
        $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed

        // Apply styling to visit time row
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // Thin borders
                    'color' => ['rgb' => 'FFFFFF'], // Black borders
                ],
            ],
        ]);
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);
        $rowIndex += 1;

        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);
        // Add Section Summary Header
        $sheet->setCellValue('A' . $rowIndex, 'Section Summary');
        $sheet->mergeCells('A' . $rowIndex . ':O' . $rowIndex);
        $sheet->getStyle('A' . $rowIndex)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFD3D3D3', // Light grey background
                ],
            ],
        ]);
        // Adjust row height for padding
        $sheet->getRowDimension($rowIndex)->setRowHeight(30); // Adjust height as needed
        $rowIndex++;
        // Set headers for the section summary
        $sheet->setCellValue('A' . $rowIndex, 'Section Name');
        $sheet->mergeCells('A' . $rowIndex . ':G' . $rowIndex); // Merge Section Name header across A to L
        $sheet->setCellValue('H' . $rowIndex, 'Total');
        $sheet->mergeCells('H' . $rowIndex . ':I' . $rowIndex);
        $sheet->setCellValue('J' . $rowIndex, 'Applicable');
        $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex);
        $sheet->setCellValue('L' . $rowIndex, 'Achieved');
        $sheet->mergeCells('L' . $rowIndex . ':M' . $rowIndex);
        $sheet->setCellValue('N' . $rowIndex, 'Score (%)');
        $sheet->mergeCells('N' . $rowIndex . ':O' . $rowIndex); // Merge Score header across M to O
        // Apply styles to the header row
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFE6E6E6', // Lighter grey for headers
                ],
            ],
        ]);

        // Adjust row height for padding
        $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);
        $rowIndex++;

        // Apply border styles to the section summary rows

        // Apply border styles to the section summary rows
        foreach ($sectionScores as $score) {
            // Set the section name and score
            $sheet->setCellValue('A' . $rowIndex, $score->section_name);
            $sheet->mergeCells('A' . $rowIndex . ':G' . $rowIndex); // Merge Section Name across A to L


            $sheet->setCellValue('H' . $rowIndex, $score->sectionScore !== null ? $score->total : 'NA');
            $sheet->mergeCells('H' . $rowIndex . ':I' . $rowIndex); // Merge Score across M to O
            $sheet->setCellValue('J' . $rowIndex, $score->sectionScore !== null ? $score->applicable : 'NA');
            $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex); // Merge Score across M to O
            $sheet->setCellValue('L' . $rowIndex, $score->sectionScore !== null ? $score->achieved : 'NA');
            $sheet->mergeCells('L' . $rowIndex . ':M' . $rowIndex); // Merge Score across M to O


            $sheet->setCellValue('N' . $rowIndex, $score->sectionScore !== null ? $score->sectionScore . '%' : 'NA');
            $sheet->mergeCells('N' . $rowIndex . ':O' . $rowIndex); // Merge Score across M to O

            // Apply styles to the section summary rows
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border color
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFFFFF', // White background color (optional, if needed)
                    ],
                ],
            ]);

            // Optionally, adjust row height for better padding
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust height as needed

            $rowIndex++;
        }
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);
        $rowIndex += 1; // Add some space after the Section Summary

        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);
        $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'], // White border
                ],
            ],
        ]);

        // Add Overall Result and Questions
        foreach ($overallresult as $section) {
            // Section Name and Score Row
            $sheet->setCellValue('A' . $rowIndex, $section['section_name']);
            $sheet->setCellValue('M' . $rowIndex, $section['section_score'] . '%');
            $sheet->mergeCells('A' . $rowIndex . ':I' . $rowIndex);
            $sheet->mergeCells('M' . $rowIndex . ':O' . $rowIndex);

            // Apply styles
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'], // White font color
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '4169E1', // Royal Blue background color
                    ],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Optional: Center vertically
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '4169E1'], // Black border color
                    ],
                ],
            ]);

            // Adjust row height if needed (height in points)
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Set the desired row height
            $rowIndex++; // Move to next row for headers
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            $rowIndex++;
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);
            // Headers Row
            // Header Row for Questions
            $sheet->setCellValue('A' . $rowIndex, 'Sr. No');
            $sheet->mergeCells('B' . $rowIndex . ':I' . $rowIndex);
            $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex);  // Merging columns J to L for "Responses"
            $sheet->setCellValue('J' . $rowIndex, 'Responses');
            $sheet->setCellValue('M' . $rowIndex, 'Total Score');
            $sheet->setCellValue('N' . $rowIndex, 'App Score');
            $sheet->setCellValue('O' . $rowIndex, 'Ach Score');

            // Apply styles
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'], // White text color
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'A9A9A9', // Royal Blue background color
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'A9A9A9'], // Grey border color
                    ],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true, // Wrap text to ensure padding is visible
                ],
            ]);

            // Adjust row height
            $sheet->getRowDimension($rowIndex)->setRowHeight(40); // Adjust row height to accommodate larger text

            // Apply padding
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->getAlignment()->setIndent(1); // Add padding


            $rowIndex++; // Move to next row for questions
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'], // White border
                    ],
                ],
            ]);

            $i = 1;
            // Initialize total variables for the section
            $totalAchieved = 0;
            $totalApplicable = 0;
            $totalAchievedValue = 0;

            foreach ($section['questions'] as $question) {
                // Question Row
                $rowIndex++;
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border
                        ],
                    ],
                ]);
                // Set the value and merge cells for the question name
                $sheet->setCellValue('A' . $rowIndex, $i . ')');
                $sheet->mergeCells('B' . $rowIndex . ':I' . $rowIndex);
                $sheet->setCellValue('B' . $rowIndex, $question['question_name']);

                // Apply styles
                $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // Grey border color
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, // Align text to left
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center vertically
                        'wrapText' => true, // Enable text wrapping
                        'indent' => 1, // Add padding (indentation)
                    ],
                ]);

                // Adjust row height based on the content
                $cellText = $question['question_name'];
                $wrappedLines = ceil(strlen($cellText) / 80); // Approximate number of lines required (adjust the divisor as needed)
                $baseRowHeight = 20; // Base height (adjust as needed)
                $sheet->getRowDimension($rowIndex)->setRowHeight($baseRowHeight * $wrappedLines);
                $rowIndex++;
                // Move to the next row for shopper comments
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border
                        ],
                    ],
                ]);
                $rowIndex++;
                $sheet->mergeCells('B' . $rowIndex . ':I' . $rowIndex);

                // Set the value in the cell with shopper comments
                $sheet->setCellValue('B' . $rowIndex, 'Shopper Comment: ' . implode(", ", $question['comments']));

                // Apply lemon color formatting and border
                $sheet->getStyle('B' . $rowIndex)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFFACD', // Lemon color (light yellow)
                        ],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // Black border
                        ],
                    ],
                ]);

                // Data Row
                $sheet->mergeCells('J' . $rowIndex . ':K' . $rowIndex);  // Merging columns J and K

                $sheet->setCellValue('J' . $rowIndex, $question['response']);
                $sheet->setCellValue('M' . $rowIndex, $question['total']);
                $sheet->setCellValue('N' . $rowIndex, $question['applicable']);
                $sheet->setCellValue('O' . $rowIndex, $question['achieved']);
                // Apply styles to the data cells
                $dataCells = ['J', 'M', 'N', 'O'];
                foreach ($dataCells as $cell) {
                    $sheet->getStyle($cell . $rowIndex)->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'], // White border
                            ],
                        ],
                    ]);
                }
                // Move to next row for the next question
                $rowIndex++;
                $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'], // White border
                        ],
                    ],
                ]);
                $i++;

                // Accumulate totals
                $totalAchieved += $question['achieved'];
                $totalApplicable += $question['applicable'];
                $totalAchievedValue += $question['total'];
            }

            // Section Totals Row
            // Merge cells for "Section Total" row
            $sheet->mergeCells('A' . $rowIndex . ':I' . $rowIndex);

            // Set the values for the section total
            $sheet->setCellValue('A' . $rowIndex, 'Section Total');
            $sheet->setCellValue('M' . $rowIndex, $totalAchieved);
            $sheet->setCellValue('N' . $rowIndex, $totalApplicable);
            $sheet->setCellValue('O' . $rowIndex, $totalAchievedValue);

            // Apply styles to the "Section Total" row
            $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->applyFromArray([
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center align horizontally
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center align vertically
                    'wrapText' => true, // Enable text wrapping
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D3D3D3'], // Grey border color
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'D3D3D3', // Grey background color
                    ],
                ],
                'font' => [
                    'color' => ['rgb' => '000000'], // Black text color
                    'bold' => true, // Bold text
                ],
            ]);

            // Optionally, set the height for the row
            $sheet->getRowDimension($rowIndex)->setRowHeight(25); // Adjust the height as needed

            // Optionally, set padding by adjusting the column width
            $sheet->getColumnDimension('A')->setWidth(20); // Adjust column width for padding
            $sheet->getColumnDimension('B')->setWidth(30); // Adjust column width for padding
            $sheet->getColumnDimension('C')->setWidth(20); // Adjust column width for padding
            // Repeat for other columns as needed


            // Move to the next row for the next section
            $rowIndex++;
        }



        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $firstLocationName . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function customreport()
    {

        session::put('title', "Custom Report");

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');

        $format = DB::table('formats')

            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')

            ->where('formats.id',  $format_id)

            ->select('formats.*', 'hierarchylevels.*')

            ->get();

        foreach ($format as $formats) {

            $HID = $formats->HID;
        }

        //  echo  $HID;

        $HID = session::put('HID', $HID);
        $shopDetailsList = DB::table('assignshops')

            ->where('format_id',  $format_id)

            ->where('wave_id', $wave_id1)

            ->where('status', "submit to client")

            ->get();
        $allHierarchyLevels = [];
        foreach ($shopDetailsList as $shopDetails) {
            $locationID = $shopDetails->location_id; // Assuming you have location_id in shop details
            // Recursive query to fetch hierarchical data for each shop
            $query = "

                WITH RECURSIVE HierarchyCTE AS (

                    SELECT 

                        h.id AS hierarchy_id,

                        h.levelID AS level_id,

                        hl.hierarchylavelname AS level_name,

                        hl.level AS level,

                        hl.HID AS hid,

                        l.locationname AS location_name,

                        h.branch_code AS branch_code,  -- Added location_code

                        h.address AS address,              -- Added address

                        h.parentID AS parent_id

                    FROM 

                        hierarchies h

                    INNER JOIN 

                        hierarchylevels hl ON h.levelID = hl.id

                    INNER JOIN 

                        locations l ON h.LID = l.id

                    WHERE 

                        h.id = :initialHierarchyId

            

                    UNION ALL

            

                    SELECT 

                        h.id AS hierarchy_id,

                        h.levelID AS level_id,

                        hl.hierarchylavelname AS level_name,

                        hl.level AS level,

                        hl.HID AS hid,

                        l.locationname AS location_name,

                        h.branch_code AS branch_code,  -- Added location_code

                        h.address AS address,              -- Added address

                        h.parentID AS parent_id

                    FROM 

                        hierarchies h

                    INNER JOIN 

                        hierarchylevels hl ON h.levelID = hl.id

                    INNER JOIN 

                        locations l ON h.LID = l.id

                    INNER JOIN 

                        HierarchyCTE hc ON hc.parent_id = h.id

                )

                SELECT 

                    hierarchy_id,

                    level_id,

                    level_name,

                    level,

                    hid,

                    location_name,

                    branch_code,  -- Include location_code in the result

                    address          -- Include address in the result

                FROM 

                    HierarchyCTE;

                ";



            // Execute query and fetch results for the current shop

            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $locationID]);

            $hierarchyLevels = array_reverse($hierarchyLevels);



            // Collect the results for all shops with locationID included

            $allHierarchyLevels[] = [

                'locationID' => $locationID,

                'hierarchyLevels' => $hierarchyLevels

            ];
        }

        // dd($allHierarchyLevels);

        $waves = waves::select('waves.id as id', 'waves.name as waveName')

            ->join('assignshops', 'waves.id', '=', 'assignshops.wave_id')

            ->where('waves.format_id', $format_id)

            ->where('assignshops.status', 'submit to client')

            ->groupBy('waves.id', 'waves.name')

            ->orderBy('waves.id', 'asc')

            ->get();

        return view('client.report.customrepport', [

            'headerName' => $format,

            'reports' => $allHierarchyLevels,

            'format_id' => $format_id,

            'wave_id1' => $wave_id1,

            'waves' => $waves,

        ]);
    }



    public function fetchResults(Request $request)
    {

        //DD($request->ALL());

        // Retrieve the results based on the selected hierarchy level and format ID

        $results = DB::table('hierarchylevels')

            ->join('formats', 'hierarchylevels.HID', '=', 'formats.assignHID')

            ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.levelID')

            ->join('locations', 'hierarchies.LID', '=', 'locations.id')

            ->where('hierarchylevels.level', $request->hierarchyLevel)

            ->where('formats.id', $request->format_id)

            ->select('hierarchies.id', 'locations.locationname')

            ->get();



        // Check if results are found

        if ($results->isEmpty()) {

            return '<p>No results found for the selected hierarchy level.</p>';
        }

        // Generate the HTML content for the results as checkboxes with a "Check All" option

        $output = '<div class="form-check">';

        $output .= '<input class="form-check-input" type="checkbox" id="checkAllLocations">';

        $output .= '<label class="form-check-label" for="checkAll">Check All</label>';

        $output .= '</div>';

        foreach ($results as $result) {

            $output .= '<div class="form-check">';

            $output .= '<input class="form-check-input location-checkbox" type="checkbox" name="locations[]" value="' . $result->id . '" id="location' . $result->id . '">';

            $output .= '<label class="form-check-label" for="location' . $result->id . '">';

            $output .= $result->locationname;

            $output .= '</label>';

            $output .= '</div>';
        }

        $output .= '</div>';



        // Return the results as a response

        return $output;
    }



    public function fetchFeatureResults(Request $request)

    {

        $feature = $request->input('feature');

        $format_id = $request->input('format_id');



        if ($feature === 'section') {





            $results = Section::where('format_id', $format_id)

                ->orderBy('orderby')

                ->get();

            if ($results->isEmpty()) {

                return '<p>No results found for the selected hierarchy level.</p>';
            }

            // Generate the HTML content for the results as checkboxes with a "Check All" option

            $output = '<div class="form-check">';

            $output .= '<input class="form-check-input" type="checkbox" id="checkAllSections">';

            $output .= '<label class="form-check-label" for="checkAll">Check All</label>';

            $output .= '</div>';

            foreach ($results as $result) {

                $output .= '<div class="form-check">';

                $output .= '<input class="form-check-input section-checkbox" type="checkbox" name="sections[]" value="' . $result->id . '" id="section' . $result->id . '">';

                $output .= '<label class="form-check-label" for="section' . $result->id . '">';

                $output .= $result->section_name;

                $output .= '</label>';

                $output .= '</div>';
            }

            $output .= '</div>';



            // Return the results as a response

            return $output;
        } elseif ($feature === 'question') {

            $results =  DB::table('sections')

                ->join('questions', 'sections.id', '=', 'questions.section_id')

                ->select('sections.id as section_id', 'sections.section_name as section_name', 'questions.id as question_id', 'questions.question_name as question_name', 'questions.orderby')

                ->where('sections.format_id',  $format_id)

                ->orderBy('questions.orderby')

                ->get();

            if ($results->isEmpty()) {

                return '<p>No results found for the selected hierarchy level.</p>';
            }

            // Generate the HTML content for the results as checkboxes with a "Check All" option

            $output = '<div class="form-check">';

            $output .= '<input class="form-check-input" type="checkbox" id="checkAllQuestions">';

            $output .= '<label class="form-check-label" for="checkAll">Check All</label>';

            $output .= '</div>';

            foreach ($results as $result) {

                $output .= '<div class="form-check">';

                $output .= '<input class="form-check-input question-checkbox" type="checkbox" name="questions[]" value="' . $result->question_id . '" id="question' . $result->question_id . '">';

                $output .= '<label class="form-check-label" for="question' . $result->question_id . '">';

                $output .= $result->question_name;

                $output .= '</label>';

                $output .= '</div>';
            }

            $output .= '</div>';



            // Return the results as a response

            return $output;
        } elseif ($feature === 'overall') {

            $output = '<div>';

            $output .= '</div>';

            return $output;
        }



        return response()->json(['error' => 'Invalid feature selected'], 400);
    }

    public function generateReport(Request $request)

    {

        // dd($request->all());

        $format_id =  session::get('format_id');

        $Hid = session::get('HID');

        $waveID = $request->waveId;

        $waveID = intval($waveID);

        // dd(  $waveID);

        //    DD($Hid);

        $levelIDs = (int)$request->hierarchyLevel; // Cast to integer



        $levelname = Hierarchylevels::where('level', $levelIDs)

            ->where('HID',  $Hid)

            ->first();

        // dd($levelname->id);  

        $levelCount = Hierarchylevels::where('HID', $Hid)

            ->count();

        //heaader

        // dd($levelCount);

        // dd($levelID);

        $levelID = $levelname->id;

        $levelName = $levelname->hierarchylavelname;

        $sectionsData = [];

        $questionData = [];

        $overallreport = null;

        $overallreport1 = [];

        $allScores = [];

        $overAll1 = NULL;

        $overall = [];

        $overallreport  = $request->input('overallreport');

        // echo   $overallreport;

        $locationIds = $request->input('locations', []);



        if ($request->has('sections')) {

            $sections = $request->input('sections');

            $sectionsData = Section::whereIn('id', $sections)

                ->orderBy('orderby', 'asc')

                ->select('section_name', 'id')->get();

            // dd($sectionsData)

        } elseif ($request->has('questions')) {

            $question = $request->input('questions');

            $questionData = Question::whereIn('id', $question)

                ->orderBy('section_id', 'asc')

                ->orderBy('orderby', 'asc')

                ->select('question_name', 'id')->get();



            // dd($questionData);



        } elseif ($request->input('feature') === 'overall') {

            // echo 1;

            $overAll1 = "Over All";

            //  dd(overAll);

        }

        //header end



        //data



        // echo $levelIDs .'<br>'. $levelCount;

        if ($levelIDs == $levelCount) {

            $locationName  = DB::table('hierarchylevels')

                ->join('hierarchies', 'hierarchylevels.id', '=', 'hierarchies.levelID')

                ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                ->join('assignshops', 'hierarchies.id', '=', 'assignshops.location_id')

                ->where('hierarchylevels.HID', $Hid)

                ->where('hierarchylevels.level', $levelIDs)

                ->where('assignshops.status', 'submit to client')

                ->where('assignshops.wave_id', $waveID)

                ->whereIn('hierarchies.id', $locationIds)

                ->select('hierarchylevels.*', 'hierarchies.*', 'locations.*', 'assignshops.*')

                ->get();

            $assignShopIds = [];



            foreach ($locationName as $location) {

                $assignshopId = $location->id; // Adjust the field name as needed

                // $assignShopIds[] = $assignshopId;

            }



            if (!empty($overallreport)) {





                foreach ($locationName as $location) {

                    $assignshopId = $location->id; // Adjust the field name as needed

                    $scores   = DB::table('scoreanalysics')

                        ->select(

                            'locations.locationname as locationName',

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore'),

                            DB::raw('ROUND((SUM(scoreanalysics.achieved)/SUM(scoreanalysics.applicable))*100) as overall')

                        )

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')

                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                        ->where('scoreanalysics.shop_id', $assignshopId)

                        ->groupBy('locations.locationname')

                        ->get();

                    $overallreport1[$location->locationname] = $scores;
                }

                // dd($overallreport1);

                // echo 1;

            }

            if ($request->has('sections')) {

                $sections = $request->input('sections');

                $sectionsData = Section::whereIn('id', $sections)

                    ->orderBy('orderby', 'asc')

                    ->select('section_name', 'id')->get();



                foreach ($locationName as $location) {

                    $assignshopId = $location->id; // Adjust the field name as needed

                    $scores = DB::table('sections')

                        ->join('scoreanalysics', 'sections.id', '=', 'scoreanalysics.section_id')

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')

                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                        ->select(

                            'locations.locationname as locationName',

                            'sections.section_name as section_name',

                            'sections.id',

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore')

                        )

                        ->whereIn('sections.id', $sections)

                        ->where('scoreanalysics.shop_id', $assignshopId)

                        ->groupBy('sections.id', 'sections.section_name', 'locations.locationname')



                        ->orderBy('sections.orderby', 'asc')

                        ->get();

                    $allScores[$location->locationname] = $scores;
                }



                // dd($sectionsData)

            } elseif ($request->has('questions')) {

                $questions = $request->input('questions'); // Use the correct variable name



                foreach ($locationName as $location) {

                    $assignshopId = $location->id; // Adjust the field name as needed

                    $scores = DB::table('questions')

                        ->join('scoreanalysics', 'questions.id', '=', 'scoreanalysics.question_id')

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')

                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                        ->select(

                            'locations.locationname as locationName',

                            'questions.question_name as question_name',

                            'questions.id',

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore')

                        )

                        ->whereIn('questions.id', $questions)

                        ->where('scoreanalysics.shop_id', $assignshopId)

                        ->groupBy('questions.id', 'questions.question_name', 'locations.locationname')

                        ->orderBy('questions.section_id', 'asc')

                        ->orderBy('questions.orderby', 'asc')

                        ->get();

                    $allScores[$location->locationname] = $scores;
                }





                // dd( $allScores);

            } elseif ($request->input('feature') === 'overall') {

                $overAll = [];



                foreach ($locationName as $location) {

                    $assignshopId = $location->id; // Adjust the field name as needed

                    $scores   = DB::table('scoreanalysics')

                        ->select(

                            'locations.locationname as locationName',

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore'),

                            DB::raw('ROUND((SUM(scoreanalysics.achieved)/SUM(scoreanalysics.applicable))*100) as overall')

                        )

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')

                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                        ->where('scoreanalysics.shop_id', $assignshopId)

                        ->groupBy('locations.locationname')

                        ->get();

                    $overall[$location->locationname] = $scores;
                }



                // dd($location->locationname); // Ensure this is an object and has the expected properties

                // dd($overall);  // Check what data is being returned





            }
        } else {

            // echo 1;

            if (is_array($locationIds)) {

                // Convert array to comma-separated string

                $locationIds = implode(',', $locationIds);
            }







            // Define the recursive CTE query with placeholders

            // $recursiveCTE = "

            //         WITH RECURSIVE NodeHierarchy AS (

            //             SELECT id, parentID, levelID, LID,  id AS RootID

            //             FROM hierarchies

            //             WHERE id IN ($locationIds)

            //             UNION ALL

            //             SELECT h.id, h.parentID, h.levelID, h.LID,   nh.RootID

            //             FROM hierarchies h

            //             INNER JOIN NodeHierarchy nh ON h.parentID = nh.id

            //         )

            //         SELECT nh.id, nh.parentID, nh.LID,    nh.RootID,loc.locationname

            //         FROM NodeHierarchy nh

            //         LEFT JOIN locations loc ON nh.LID = loc.id

            //         WHERE nh.id NOT IN (

            //             SELECT DISTINCT parentID

            //             FROM hierarchies

            //             WHERE parentID IS NOT NULL

            //         )

            //         ORDER BY nh.levelID;

            //     ";



            // // Execute the raw SQL query with bindings
            $recursiveCTE = "WITH RECURSIVE NodeHierarchy AS 
            ( -- Base case: select initial nodes 
            SELECT id,
             parentID, levelID, LID, id AS RootID FROM hierarchies 
             WHERE id IN ($locationIds) UNION ALL -- Recursive case: join with parent nodes 
             SELECT h.id, h.parentID, h.levelID, h.LID, nh.RootID FROM hierarchies h 
             INNER JOIN NodeHierarchy nh ON h.parentID = nh.id ), RootNames AS ( -- Get root names 
             SELECT DISTINCT nh.RootID, l256.locationname AS rootname FROM NodeHierarchy nh
              INNER JOIN hierarchies l23 ON l23.id = nh.RootID INNER JOIN locations l256 ON l256.id = l23.LID ) 
              -- Main query: select from the recursive CTE and join with root names
               SELECT nh.id, nh.parentID, nh.LID, nh.RootID, rn.rootname
                FROM NodeHierarchy nh LEFT JOIN RootNames rn ON nh.RootID = rn.RootID 
                LEFT JOIN locations loc ON nh.LID = loc.id WHERE nh.id NOT IN
                 ( SELECT DISTINCT parentID FROM hierarchies WHERE parentID IS NOT NULL )ORDER by rootID";

            $result = DB::select($recursiveCTE);
            // Initialize the categorized results array
            $categorizedResults = [];

            // Step 1: Organize results by rootname
            foreach ($result as $item) {
                $rootname = $item->rootname;
                $id = $item->id;

                // Initialize the rootname array if it doesn't exist
                if (!isset($categorizedResults[$rootname])) {
                    $categorizedResults[$rootname] = [];
                }

                // Add the ID to the array for the corresponding rootname
                $categorizedResults[$rootname][] = $id;
            }

            // Optional: Print or inspect the categorized results
            // dd($categorizedResults);







            if (!empty($overallreport)) {

                $overallreport1 = [];

                $i = 0;

                // dd( $data['assignshops']);

                foreach ($categorizedResults as $rootname => $assignshopIds) {

                    // $i++;


                    // echo 


                    // dd( $rootLocationNames );

                    $scores = DB::table('scoreanalysics')

                        ->select(

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore'),

                            DB::raw('ROUND((SUM(scoreanalysics.achieved)/SUM(scoreanalysics.applicable))*100) as overall')

                        )

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->whereIn('assignshops.location_id', $assignshopIds)
                        ->where('assignshops.wave_id', $waveID)
                        ->where('assignshops.status', "submit to client")

                        ->get();



                    $overallreport1[$rootname] = $scores;
                }



                // dd($overallreport1);

                // Debugging: Print final report

                // dd( $i);



            }



            if ($request->has('sections')) {

                $sections = $request->input('sections');

                $sectionsData = Section::whereIn('id', $sections)

                    ->orderBy('orderby', 'asc')

                    ->select('section_name', 'id')->get();



                foreach ($categorizedResults as $rootname => $assignshopIds) {



                    $scores = DB::table('sections')

                        ->join('scoreanalysics', 'sections.id', '=', 'scoreanalysics.section_id')

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')

                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                        ->select(



                            'sections.section_name as section_name',

                            'sections.id',

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore'),

                            DB::raw('ROUND((SUM(scoreanalysics.achieved)/SUM(scoreanalysics.applicable))*100) as overall')



                        )

                        ->whereIn('sections.id', $sections)
                        ->whereIn('assignshops.location_id', $assignshopIds)
                        ->where('assignshops.wave_id', $waveID)
                        ->where('assignshops.status', "submit to client")
                        ->groupBy('sections.id', 'sections.section_name')
                        ->orderBy('sections.orderby', 'asc')

                        ->get();

                    $allScores[$rootname] = $scores;
                }



                // dd($allScores);

            } elseif ($request->has('questions')) {

                $questions = $request->input('questions'); // Use the correct variable name

                //    echo 1;

                foreach ($categorizedResults as $rootname => $assignshopIds) {



                    $scores = DB::table('questions')

                        ->join('scoreanalysics', 'questions.id', '=', 'scoreanalysics.question_id')

                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')

                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')

                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')

                        ->select(

                            'questions.question_name as question_name',

                            'questions.id',

                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),

                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),

                            DB::raw('SUM(scoreanalysics.total) as totalscore'),



                        )

                        ->whereIn('questions.id', $questions)

                        ->whereIn('assignshops.location_id', $assignshopIds)
                        ->where('assignshops.wave_id', $waveID)
                        ->where('assignshops.status', "submit to client")
                        ->groupBy('questions.id', 'questions.question_name')

                        ->orderBy('questions.section_id', 'asc')

                        ->orderBy('questions.orderby', 'asc')

                        ->get();

                    $allScores[$rootname] = $scores;
                }

                // dd(  $allScores);





                // dd( $allScores);

            } elseif ($request->input('feature') === 'overall') {

                $overAll = [];



                foreach ($categorizedResults as $rootname => $assignshopIds) {
                    $scores   = DB::table('scoreanalysics')
                        ->select(
                            DB::raw('SUM(scoreanalysics.achieved) as achievedscore'),
                            DB::raw('SUM(scoreanalysics.applicable) as applicablescore'),
                            DB::raw('SUM(scoreanalysics.total) as totalscore'),
                            DB::raw('ROUND((SUM(scoreanalysics.achieved)/SUM(scoreanalysics.applicable))*100) as overall')
                        )
                        ->join('assignshops', 'scoreanalysics.shop_id', '=', 'assignshops.id')
                        ->join('hierarchies', 'assignshops.location_id', '=', 'hierarchies.id')
                        ->join('locations', 'hierarchies.LID', '=', 'locations.id')
                        ->whereIn('assignshops.location_id', $assignshopIds)
                        ->where('assignshops.wave_id', $waveID)
                        ->where('assignshops.status', "submit to client")
                        ->get();
                    $overall[$rootname] = $scores;
                }



                // dd($location->locationname); // Ensure this is an object and has the expected properties

                // dd($overall);  // Check what data is being returned





            }



            $locationName = "null";
        }



        // dd( $locationName);

        //datA end

        // dd($locationName);

        //  echo $overAll1;

        // dd($allScores);

        return view('client.report.customreport', [

            'levelName' =>   $levelName,

            'sectionsData' => $sectionsData,

            'questionData' => $questionData,

            'locationName' => $locationName,

            'overAll1' => $overAll1,

            'overall' => $overall,

            'overallreport' => $overallreport,

            'overallreport1' => $overallreport1,

            'allScores' => $allScores,

            // 'scores'=> $scores,



        ]);
    }

    public function summaryreport()
    {
        session::put('title', "Summary Report");

        $format_id =  session::get('format_id');

        $wave_id1 = session::get('wave_id1');

        $wave_id = session::get('wave_id');

        $ytd = Session::get('YTD');
        $format = DB::table('formats')

            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')

            ->where('formats.id',  $format_id)

            ->select('formats.*', 'hierarchylevels.*')

            ->get();

        $format1 = DB::table('formats')
            ->where('id', $format_id)
            ->select('name') // Select only the 'name' column
            ->first();
        $formatname = $format1->name;
        // dd($formatname);
        // Retrieve all shops for the client with the given format_id and wave_id

        $shopDetailsList = DB::table('assignshops')

            ->where('format_id',  $format_id)

            ->where('wave_id', $wave_id1)

            ->where('status', "submit to client")

            ->get();



        $allHierarchyLevels = [];


        foreach ($shopDetailsList as $shopDetails) {

            $locationID = $shopDetails->location_id;
            $shopID = $shopDetails->id;
            $date = $shopDetails->date;
            $timeIn = $shopDetails->timeIn;
            $timeOut = $shopDetails->timeOut;

            // Recursive query to fetch hierarchical data for each shop

            $query = "

            WITH RECURSIVE HierarchyCTE AS (

                SELECT 

                    h.id AS hierarchy_id,

                    h.levelID AS level_id,

                    hl.hierarchylavelname AS level_name,

                    hl.level AS level,

                    hl.HID AS hid,

                    l.locationname AS location_name,

                    h.branch_code AS branch_code,  -- Added location_code

                    h.address AS address,              -- Added address

                    h.parentID AS parent_id

                FROM 

                    hierarchies h

                INNER JOIN 

                    hierarchylevels hl ON h.levelID = hl.id

                INNER JOIN 

                    locations l ON h.LID = l.id

                WHERE 

                    h.id = :initialHierarchyId

        

                UNION ALL

        

                SELECT 

                    h.id AS hierarchy_id,

                    h.levelID AS level_id,

                    hl.hierarchylavelname AS level_name,

                    hl.level AS level,

                    hl.HID AS hid,

                    l.locationname AS location_name,

                    h.branch_code AS branch_code,  -- Added location_code

                    h.address AS address,              -- Added address

                    h.parentID AS parent_id

                FROM 

                    hierarchies h

                INNER JOIN 

                    hierarchylevels hl ON h.levelID = hl.id

                INNER JOIN 

                    locations l ON h.LID = l.id

                INNER JOIN 

                    HierarchyCTE hc ON hc.parent_id = h.id

            )

            SELECT 

                hierarchy_id,

                level_id,

                level_name,

                level,

                hid,

                location_name,

                branch_code,  -- Include location_code in the result

                address          -- Include address in the result

            FROM 

                HierarchyCTE;

            ";



            // Execute query and fetch results for the current shop

            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $locationID]);
            // dd($hierarchyLevels);
            $hierarchyLevels = array_reverse($hierarchyLevels);
            $scoresarray = [];

            $sections = Section::selectRaw('sections.id as section_id, 
            sections.section_name as section_name, 
            IFNULL(SUM(scoreanalysics.achieved), "N/A") as achieved, 
            IFNULL(SUM(scoreanalysics.applicable), "N/A") as applicable, 
            IFNULL(SUM(scoreanalysics.total), "N/A") as total, 
            IFNULL(ROUND((SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable)) * 100), "N/A") as sectionpercentage')
                ->leftJoin('scoreanalysics', function ($join) use ($shopID) {
                    $join->on('sections.id', '=', 'scoreanalysics.section_id')
                        ->where('scoreanalysics.shop_id', $shopID);
                })
                ->where('sections.format_id', $format_id)
                ->groupBy('sections.id', 'sections.section_name')
                ->orderBy('sections.orderby', 'asc')
                ->get();
            foreach ($sections as $section) {
                $sectionID = $section->section_id;
                $questions = DB::table('questions')
                    ->selectRaw('
                    questions.id AS question_id,
                    questions.question_name AS section_name,
                    IFNULL(SUM(scoreanalysics.achieved), "N/A") AS achieved,
                    IFNULL(SUM(scoreanalysics.applicable), "N/A") AS applicable,
                    IFNULL(SUM(scoreanalysics.total), "N/A") AS total,
                    IFNULL(ROUND((SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable)) * 100), "N/A") AS questionpercentage,
                    IFNULL(GROUP_CONCAT(scoreanalysics.response), "N/A") AS response,
                     IFNULL(GROUP_CONCAT(comments.comments), "") AS comments 
                ')
                    ->leftJoin('scoreanalysics', function ($join) use ($shopID, $sectionID) {
                        $join->on('questions.id', '=', 'scoreanalysics.question_id')
                            ->where('scoreanalysics.shop_id', $shopID)
                            ->where('scoreanalysics.section_id', $sectionID);
                    })->leftJoin('comments', function ($join) use ($shopID) {
                        $join->on('questions.id', '=', 'comments.question_id')
                            ->where('comments.shop_id', $shopID); // Join condition for comments based on shop ID
                    })
                    ->where('questions.section_id', $sectionID)
                    ->groupBy('questions.id', 'questions.question_name')
                    ->orderBy('questions.orderby', 'asc')
                    ->get();

                // Prepare section data with questions
                $sectionData = [
                    'section_id' => $section->section_id,
                    'section_name' => $section->section_name,
                    'achieved' => $section->achieved,
                    'applicable' => $section->applicable,
                    'total' => $section->total,
                    'sectionpercentage' => $section->sectionpercentage,
                    'questions' => []
                ];

                // Populate question data
                foreach ($questions as $question) {
                    $sectionData['questions'][] = [
                        'question_id' => $question->question_id,
                        'question_name' => $question->section_name,
                        'achieved' => $question->achieved,
                        'applicable' => $question->applicable,
                        'total' => $question->total,
                        'response' => $question->response,
                        'comments' => $question->comments,
                        'questionpercentage' => $question->questionpercentage,

                    ];
                }
                // dd($sectionData);
                // Add section data to scores array
                $scoresarray[] = $sectionData;
            }
            $resultscore = $scoresarray;
            // dd($scoresarray);
            $shopresult = DB::table('scoreanalysics')
                ->selectRaw('
        ROUND(SUM(achieved) / NULLIF(SUM(applicable), 0) * 100) AS overall,
        SUM(achieved) AS achieved,
        SUM(applicable) AS applicable,
        SUM(total) AS total')
                ->where('shop_id', $shopID)
                ->first();
            // dd($shopresult);
         
            $allHierarchyLevels[] = [

                'locationID' => $locationID,
                'hierarchyLevels' => $hierarchyLevels,
                'shopID' =>  $shopID,
               'date'=> $date,
              'timeIn'=> $timeIn,
             'timeOut'=> $timeOut,
                'shopresult' => $shopresult,
                'scoresarray' =>  $resultscore,

            ];
        }
        $header2 = DB::table('sections')
            ->join('questions', 'sections.id', '=', 'questions.section_id')
            ->where('sections.format_id', $format_id)
            ->orderBy('sections.orderby', 'ASC')
            ->orderBy('questions.orderby', 'ASC')
            ->select('sections.id as section_id', 'sections.section_name as section_name', 'questions.id as question_id', 'questions.question_name as question_name')
            ->get();

        // dd($header2);

        // dd($allHierarchyLevels);
        return view('client.report.summaryreport', [

            'headerName' => $format,
            'formatname' => $formatname,
            'reports' => $allHierarchyLevels,
            'header2' => $header2,
        ]);
    }
    public function excelsummaryreport()
    {
        session::put('title', "Summary Report");
        $format_id =  session::get('format_id');
        $wave_id1 = session::get('wave_id1');
        $format = DB::table('formats')
            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')
            ->where('formats.id',  $format_id)
            ->select('formats.*', 'hierarchylevels.*')
            ->get();
        $format1 = DB::table('formats')
            ->where('id', $format_id)
            ->select('name') // Select only the 'name' column
            ->first();
        $formatname1 = $format1->name;
        $shopDetailsList = DB::table('assignshops')
            ->where('format_id',  $format_id)
            ->where('wave_id', $wave_id1)
            ->where('status', "submit to client")
            ->get();
        $allHierarchyLevels = [];
        foreach ($shopDetailsList as $shopDetails) {
            $locationID = $shopDetails->location_id;
            $shopID = $shopDetails->id;
            $query = "
            WITH RECURSIVE HierarchyCTE AS (
                SELECT 
                    h.id AS hierarchy_id,
                    h.levelID AS level_id,
                    hl.hierarchylavelname AS level_name,
                    hl.level AS level,
                    hl.HID AS hid,
                    l.locationname AS location_name,
                    h.branch_code AS branch_code,  -- Added location_code
                    h.address AS address,          -- Added address
                    h.parentID AS parent_id
                FROM 
                    hierarchies h
                INNER JOIN 
                    hierarchylevels hl ON h.levelID = hl.id
                INNER JOIN 
                    locations l ON h.LID = l.id
                WHERE 
                    h.id = :initialHierarchyId
        
                UNION ALL
        
                SELECT 
                    h.id AS hierarchy_id,
                    h.levelID AS level_id,
                    hl.hierarchylavelname AS level_name,
                    hl.level AS level,
                    hl.HID AS hid,
                    l.locationname AS location_name,
                    h.branch_code AS branch_code,  -- Added location_code
                    h.address AS address,          -- Added address
                    h.parentID AS parent_id
                FROM 
                    hierarchies h
                INNER JOIN 
                    hierarchylevels hl ON h.levelID = hl.id
                INNER JOIN 
                    locations l ON h.LID = l.id
                INNER JOIN 
                    HierarchyCTE hc ON hc.parent_id = h.id
            )
        
            SELECT 
                hierarchy_id,
                level_id,
                level_name,
                level,
                hid,
                location_name,
                branch_code,  -- Include location_code in the result
                address       -- Include address in the result
            FROM 
                HierarchyCTE;
        ";
            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $locationID]);
            $hierarchyLevels = array_reverse($hierarchyLevels);
            $scoresarray = [];
            $sections = Section::selectRaw('sections.id as section_id, 
            sections.section_name as section_name, 
            IFNULL(SUM(scoreanalysics.achieved), "N/A") as achieved, 
            IFNULL(SUM(scoreanalysics.applicable), "N/A") as applicable, 
            IFNULL(SUM(scoreanalysics.total), "N/A") as total, 
            IFNULL(ROUND((SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable)) * 100), "N/A") as sectionpercentage')
                ->leftJoin('scoreanalysics', function ($join) use ($shopID) {
                    $join->on('sections.id', '=', 'scoreanalysics.section_id')
                        ->where('scoreanalysics.shop_id', $shopID);
                })
                ->where('sections.format_id', $format_id)
                ->groupBy('sections.id', 'sections.section_name')
                ->orderBy('sections.orderby', 'asc')
                ->get();
            foreach ($sections as $section) {
                $sectionID = $section->section_id;
                $questions = DB::table('questions')
                    ->selectRaw('
                    questions.id AS question_id,
                    questions.question_name AS section_name,
                    IFNULL(SUM(scoreanalysics.achieved), "N/A") AS achieved,
                    IFNULL(SUM(scoreanalysics.applicable), "N/A") AS applicable,
                    IFNULL(SUM(scoreanalysics.total), "N/A") AS total,
                    IFNULL(ROUND((SUM(scoreanalysics.achieved) / SUM(scoreanalysics.applicable)) * 100), "N/A") AS questionpercentage,
                    IFNULL(GROUP_CONCAT(scoreanalysics.response), "N/A") AS response,
                     IFNULL(GROUP_CONCAT(comments.comments), "") AS comments 
                ')
                    ->leftJoin('scoreanalysics', function ($join) use ($shopID, $sectionID) {
                        $join->on('questions.id', '=', 'scoreanalysics.question_id')
                            ->where('scoreanalysics.shop_id', $shopID)
                            ->where('scoreanalysics.section_id', $sectionID);
                    })->leftJoin('comments', function ($join) use ($shopID) {
                        $join->on('questions.id', '=', 'comments.question_id')
                            ->where('comments.shop_id', $shopID); // Join condition for comments based on shop ID
                    })
                    ->where('questions.section_id', $sectionID)
                    ->groupBy('questions.id', 'questions.question_name')
                    ->orderBy('questions.orderby', 'asc')
                    ->get();
                $sectionData = [
                    'section_id' => $section->section_id,
                    'section_name' => $section->section_name,
                    'achieved' => $section->achieved,
                    'applicable' => $section->applicable,
                    'total' => $section->total,
                    'sectionpercentage' => $section->sectionpercentage,
                    'questions' => []
                ];
                foreach ($questions as $question) {
                    $sectionData['questions'][] = [
                        'question_id' => $question->question_id,
                        'question_name' => $question->section_name,
                        'achieved' => $question->achieved,
                        'applicable' => $question->applicable,
                        'total' => $question->total,
                        'response' => $question->response,
                        'comments' => $question->comments,
                        'questionpercentage' => $question->questionpercentage,

                    ];
                }
                // dd($sectionData);
                // Add section data to scores array
                $scoresarray[] = $sectionData;
            }
            $resultscore = $scoresarray;
            // dd($scoresarray);
            $shopresult = DB::table('scoreanalysics')
                ->selectRaw('
        ROUND(SUM(achieved) / NULLIF(SUM(applicable), 0) * 100) AS overall,
        SUM(achieved) AS achieved,
        SUM(applicable) AS applicable,
        SUM(total) AS total')
                ->where('shop_id', $shopID)
                ->first();
            // dd($shopresult);
            $allHierarchyLevels[] = [

                'locationID' => $locationID,
                'hierarchyLevels' => $hierarchyLevels,
                'shopID' =>  $shopID,
                'shopresult' => $shopresult,
                'scoresarray' =>  $resultscore,

            ];
        }
        $header1 = DB::table('sections')
            ->join('questions', 'sections.id', '=', 'questions.section_id')
            ->where('sections.format_id', $format_id)
            ->orderBy('sections.orderby', 'ASC')
            ->orderBy('questions.orderby', 'ASC')
            ->select('sections.id as section_id', 'sections.section_name as section_name', 'questions.id as question_id', 'questions.question_name as question_name')
            ->get();
        $headerName = $format;
        $formatname = $formatname1;
        $reports = $allHierarchyLevels;
        $header2 = $header1;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Row 1
        $sheet->setCellValue('A1', 'Sr.No'); // Static header

        // Add dynamic headers based on $headerName
        if ($headerName->isNotEmpty()) {
            $columnIndex = 2; // Start from column index 2 (B) for dynamic headers
            // Reverse the headerName collection and skip the first item
            foreach ($headerName->reverse() as $index => $header) {
                if ($index > 0) { // Skip the first item
                    $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                    $sheet->setCellValue("{$columnLetter}1", $header->hierarchylavelname);
                    $columnIndex++; // Move to the next column for the next header
                }
            }
        }

        // Add additional static headers in the next available columns
        $branchCodeColumn = Coordinate::stringFromColumnIndex($columnIndex); // Next available column for 'Branch Code'
        $formatNameColumn = Coordinate::stringFromColumnIndex($columnIndex + 1); // Next column for 'Format Name'
        $sheet->setCellValue("{$branchCodeColumn}1", 'Branch Code'); // Set value in the next column
        $sheet->setCellValue("{$formatNameColumn}1", 'Format Name'); // Set value in the next column
        $columnIndex += 2; // Move to the next available column after setting static headers

        $currentSectionId = null; // Initialize variable to keep track of the current section
        $currentSectionName = ''; // Initialize variable for the current section name

        // Iterate through $header2 to handle sections and questions
        foreach ($header2 as $row) {
            // Check if a new section starts
            if ($currentSectionId !== $row->section_id) {
                if ($currentSectionId !== null) {
                    // Merge the last 6 columns for the previous section
                    $mergeStartColumn = Coordinate::stringFromColumnIndex($columnIndex - 6); // Start merging from the last question
                    $mergeEndColumn = Coordinate::stringFromColumnIndex($columnIndex - 1); // End at the last used column
                    $sheet->mergeCells("{$mergeStartColumn}1:{$mergeEndColumn}1"); // Merge cells for section header
                    $sheet->setCellValue("{$mergeStartColumn}1", $currentSectionName); // Set section name in merged cells
                }

                // Update the section information
                $currentSectionId = $row->section_id;
                $currentSectionName = $row->section_name;
            }

            // Merge cells and set question name for the current section
            $mergeStartColumn = Coordinate::stringFromColumnIndex($columnIndex); // Starting column for question
            $mergeEndColumn = Coordinate::stringFromColumnIndex($columnIndex + 5); // Merge next 5 columns (6 total)
            $sheet->mergeCells("{$mergeStartColumn}1:{$mergeEndColumn}1"); // Merge cells for question
            $sheet->setCellValue("{$mergeStartColumn}1", $row->question_name); // Set the question name in the merged cells

            $columnIndex += 6; // Move the column index forward by 6 for the next question
        }

        // Finalize the last section's merging if there are any sections processed
        if ($currentSectionId !== null) {
            $mergeStartColumn = Coordinate::stringFromColumnIndex($columnIndex - 6); // Start merging for the last section
            $mergeEndColumn = Coordinate::stringFromColumnIndex($columnIndex - 1); // End at the last used column
            $sheet->mergeCells("{$mergeStartColumn}1:{$mergeEndColumn}1"); // Merge cells for the last section
            $sheet->setCellValue("{$mergeStartColumn}1", $currentSectionName); // Set the last section name in merged cells
        }

        // Add an overall score section (final static section)
        $overallScoreStart = Coordinate::stringFromColumnIndex($columnIndex); // Next column after the last question
        $overallScoreEnd = Coordinate::stringFromColumnIndex($columnIndex + 5); // Merge the next 6 columns for "Overall Score"
        $sheet->mergeCells("{$overallScoreStart}1:{$overallScoreEnd}1");
        $sheet->setCellValue("{$overallScoreStart}1", "Overall Score");

        // Ensure to set column index for the second row
        $columnIndex = 2; // Starting from column B (index 2, as Sr.No is in column A)

        // Set Static Header for Second Row
        $sheet->setCellValue('A2', ''); // Static header for second row

        // Add dynamic headers based on $headerName for the second row
        if ($headerName->isNotEmpty()) {
            $row = "2"; // Change to row 2 for the second row
            // Reverse the headerName collection and skip the first item
            foreach ($headerName->reverse() as $index => $header) {
                if ($index > 0) { // Skip the first item
                    $columnLetter = Coordinate::stringFromColumnIndex($columnIndex); // Convert index to letter
                    $sheet->setCellValue("{$columnLetter}2", ""); // Set dynamic header for second row
                    $columnIndex++; // Move to the next column for the next header
                }
            }
        }

        // Add additional static headers in the next available columns for the second row
        $branchCodeColumn = Coordinate::stringFromColumnIndex($columnIndex); // Column for Branch Code
        $formatNameColumn = Coordinate::stringFromColumnIndex($columnIndex + 1); // Column for Format Name
        $sheet->setCellValue("{$branchCodeColumn}2", ''); // Set value in E2
        $sheet->setCellValue("{$formatNameColumn}2", ''); // Set value in F2
        $columnIndex += 2; // Move to the next available column after setting static headers

        $currentSectionId = null; // Initialize variable to keep track of the current section
        $currentSectionName = ''; // Initialize variable for the current section name

        // Iterate through $header2 to handle sections and questions
        foreach ($header2 as $row) {
            // Check if a new section starts
            if ($currentSectionId !== $row->section_id) {
                if ($currentSectionId !== null) {
                    // Merge the last 6 columns for the previous section
                    $mergeStartColumn = Coordinate::stringFromColumnIndex($columnIndex - 6); // Start merging from the last question
                    $mergeEndColumn = Coordinate::stringFromColumnIndex($columnIndex - 1); // End at the last used column
                    $sheet->mergeCells("{$mergeStartColumn}2:{$mergeEndColumn}2"); // Merge cells for section header
                    $sheet->setCellValue("{$mergeStartColumn}2", $currentSectionName); // Set section name in merged cells
                }

                // Update the section information
                $currentSectionId = $row->section_id;
                $currentSectionName = $row->section_name;
            }

            // Merge cells and set question name for the current section
            // Assuming $columnIndex is defined and pointing to the starting column for the question
            // $columnIndex

            // Now set the values in the corresponding columns for the current question
            // Assuming you have the values for each respective column from your data
            $sheet->setCellValue("{$columnIndex}3", "Response");           // Set Response in row 3
            $sheet->setCellValue("{$columnIndex}4", "Ach Score");                // Set Ach in row 4
            $sheet->setCellValue("{$columnIndex}5", "App Score");                // Set App in row 5
            $sheet->setCellValue("{$columnIndex}6", "Total Score");              // Set Total in row 6
            $sheet->setCellValue("{$columnIndex}7", "%");          // Set Percentage in row 7
            $sheet->setCellValue("{$columnIndex}8", "Shopper Comment");
            $columnIndex += 6; // Move the column index forward by 6 for the next question
        }

        // Finalize the last section's merging if there are any sections processed
        if ($currentSectionId !== null) {
            $mergeStartColumn = Coordinate::stringFromColumnIndex($columnIndex - 6); // Start merging for the last section
            $mergeEndColumn = Coordinate::stringFromColumnIndex($columnIndex - 1); // End at the last used column
            $sheet->mergeCells("{$mergeStartColumn}2:{$mergeEndColumn}2"); // Merge cells for the last section
            $sheet->setCellValue("{$mergeStartColumn}2", $currentSectionName); // Set the last section name in merged cells
        }

        // Add an overall score section (final static section)
        $overallScoreStart = Coordinate::stringFromColumnIndex($columnIndex); // Next column after the last question
        $overallScoreEnd = Coordinate::stringFromColumnIndex($columnIndex + 5); // Merge the next 6 columns for "Overall Score"
        $sheet->mergeCells("{$overallScoreStart}2:{$overallScoreEnd}2");
        $sheet->setCellValue("{$overallScoreStart}2", "Overall Score");

        // Apply styling to the second row
        $secondRowStyle = [
            'font' => [
                'color' => ['rgb' => '000000'], // Set font color to black
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC'], // Set fill color to grey
            ],
        ];

        $sheet->getStyle("A2:{$overallScoreEnd}2")->applyFromArray($secondRowStyle);
        // Set styles for the first row
        $firstRowStyle = [
            'font' => [
                'color' => ['rgb' => 'FFFFFF'], // White text color
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '31869B'], // Background color "#31869b"
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER, // Center horizontally
                'vertical' => Alignment::VERTICAL_CENTER, // Center vertically
                'wrapText' => true, // Enable text wrapping
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'], // Black border
                ],
            ],
        ];

        // Apply styles to the first row
        $sheet->getStyle("A1:{$overallScoreEnd}1")->applyFromArray($firstRowStyle);
        // Apply black borders to the range from A1 to Z1100
        $sheet->getStyle('A1:Z1100')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'], // Black border
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER, // Center horizontally
                'vertical' => Alignment::VERTICAL_CENTER, // Center vertically
                'wrapText' => true, // Enable text wrapping
            ],
        ]);


        // Create a writer instance
        $writer = new Xlsx($spreadsheet);

        // Stream the file to the browser
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="report.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
