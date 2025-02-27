<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\formatController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\clientDashboardController;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; // Import the Hash facade
use App\Http\Controllers\HierarchyController;
use App\Http\Controllers\HierarchynameController;
use App\Http\Controllers\shopperController;
use App\Http\Controllers\reportController;
use App\Http\Controllers\managerController;
use App\Http\Controllers\overallviewController;
use App\Http\Controllers\sectiondashboardController;
use App\Http\Controllers\visitReportController;
use App\Http\Controllers\commentanalysisController;
use App\Http\Controllers\contestController;
use App\Http\Controllers\locationDashboardController;
use App\Http\Controllers\hirechylevelController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\stepbystepController;
use App\Http\Controllers\survayController;
use App\Http\Controllers\DepartmentController;
// use PDF;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Http\Controllers\ProcessController; 

//comment


// Route::get('/', [HomeController::class, 'index']);
Route::get('/',  [AuthController::class, 'login'])->name('login');
Route::get('login', [AuthController::class, 'login'])->name('login');
Route::post('login_post', [AuthController::class, 'login_post'])->name('login_post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');


Route::get('/runcmd', function () {
    $artisanCommands = [
        'route:clear',
        'config:clear',
        'cache:clear',
        'view:clear'
    ];

    $output = '';
    foreach ($artisanCommands as $command) {
        Artisan::call($command);
        $output .= "<pre>" . Artisan::output() . "</pre>";
    }

    return $output;
});

Route::group(['middleware' => 'admin'], function () {
    Route::get('admin/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    // Route::get('superadmin/dashboard', [UserManagementController::class, 'usermanagement'])->name('usermanagement');
    Route::get('admin/view', [DashboardController::class, 'dashboard1'])->name('dashboard1');

    //for supperadmin roles


    Route::get('admin/usermanagement', [UserManagementController::class, 'usermanagement'])->name('usermanagement');
    Route::post('createuser', [AuthController::class, 'createuser'])->name('createuser');
    Route::get('clientedit', [UserManagementController::class, 'clientedit'])->name('clientedit');
    // In web.php
  
    Route::post('/savenewdepartment', [DepartmentController::class, 'savenewdepartment']);
    Route::post('saveuser', [DepartmentController::class, 'saveUser']);

    Route::post('createuser3', [AuthController::class, 'createuser3'])->name('createuser3');
    Route::delete('admin/users/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::post('admin//update-user', [UserManagementController::class, 'update'])->name('updateUser');
    Route::get('admin/HierarchyUsers', [UserManagementController::class, 'HierarchyUsers'])->name('HierarchyUsers');
    Route::post('admin/postLevelFormat', [UserManagementController::class, 'postLevelFormat'])->name('postLevelFormat');
    Route::post('admin/saveChanges', [UserManagementController::class, 'saveChanges']);

    //end supperadmin roles
    Route::post('/saveHierarchy', [FormatController::class, 'saveHierarchy']);
    Route::post('/update-mandatory-field', [FormatController::class, 'updateMandatoryField']);
    Route::post('/update-section-mandatory-fields', [FormatController::class, 'updateSectionMandatoryFields']);

    //format
    Route::post('admin/formats/store', [FormatController::class, 'store'])->name('formats.store');

    Route::get('admin/stepbystep', [stepbystepController::class, 'stepbystep'])->name('stepbystep');
    Route::post('/copy-format', [FormatController::class, 'copyFormat'])->name('copyFormat');

    Route::get('admin/createformat', [formatController::class, 'createformat'])->name('createformat');
    Route::post('admin/createformatname', [formatController::class, 'createformatname'])->name('createformatname');
    Route::post('fetchFormats', [formatController::class, 'fetchFormats'])->name('fetchFormats');
    Route::post('admin/deleteFormat', [formatController::class, 'deleteFormat'])->name('deleteFormat');
    // Route to fetch the format data for editing
    Route::get('/admin/edit/{id}', [formatController::class, 'fetchFormatData'])->name('superadmin.edit');
    Route::get('/admin/{id}/editformat', [FormatController::class, 'editformat'])->name('editformat');
    Route::delete('/sections/{id}', [SectionController::class, 'destroy']);
    Route::delete('/questions/{id}', [SectionController::class, 'destroyquestion']);

    // Route to update the format data
    Route::post('admin/update', [FormatController::class, 'update'])->name('update');
    Route::get('admin/formatcreatepage/{user_id}', [FormatController::class, 'create'])->name('formatcreatepage');
    Route::post('admin/updateFormatName', [FormatController::class, 'updateFormatName'])->name('updateFormatName');
    Route::post('admin/savesectionsandquestions', [FormatController::class, 'savesectionsandquestions'])->name('savesectionsandquestions');
    Route::post('admin/editsavesectionsandquestions', [FormatController::class, 'editsavesectionsandquestions'])->name('editsavesectionsandquestions');

    Route::get('/admin/{id}/reorderFormat', [FormatController::class, 'reorderFormat'])->name('reorderFormat');
    Route::post('/admin/{id}/updateOrder', [FormatController::class, 'updateOrder'])->name('updateOrder');

    // In web.php
    Route::post('admin/save-format', [formatController::class, 'saveFormat'])->name('save.format');
    Route::post('admin/save-section', [formatController::class, 'saveSection'])->name('save.section');
    Route::post('admin/save-question', [formatController::class, 'saveQuestion'])->name('save.question');
    Route::post('admin/save-option', [formatController::class, 'saveOption'])->name('save.option');
    Route::post('admin/save-keyword', [formatController::class, 'savekeyword'])->name('save.keyword');
    Route::get('admin/viewdashboard', [clientDashboardController::class, 'viewdashboard'])->name('viewdashboard');

    Route::delete('/options/{id}', [formatController::class, 'destroy']);

    Route::post('admin/updateSectionOrder', [FormatController::class, 'updateSectionOrder'])->name('updateSectionOrder');

    // Route to update question order
    Route::post('admin/updateQuestionOrder', [FormatController::class, 'updateQuestionOrder'])->name('updateQuestionOrder');
    //end format
    //for wave
    Route::get('admin/createwave', [formatController::class, 'createwave'])->name('createwave');
    Route::get('admin/getformat', [formatController::class, 'getUserFormat']);
    Route::post('admin/storedata', [formatController::class, 'storedata'])->name('storedata');
    Route::post('admin/storedata1', [formatController::class, 'storedata1'])->name('storedata1');
    Route::post('/update-wave', [formatController::class, 'updateWave'])->name('updateWave');


    // In routes/web.php
    Route::post('/admin/save-process', [ProcessController::class, 'saveProcess'])->name('saveProcess');
    Route::get('/admin/createProcess', [ProcessController::class, 'createProcess'])->name('createProcess');
    Route::post('/admin/processFrequency', [ProcessController::class, 'processFrequency'])->name('processFrequency');

    // MAIN CONTROLER TO WORK WITH.
    //........................................................
    Route::get('process-details/{process_id}/{wave_id}', [clientDashboardController::class, 'show'])->name('process.detailss');
    Route::get('/get-waves/{processId}', [clientDashboardController::class, 'getWaves'])->name('getWaves');
    //........................................................

    Route::post('/update-selection', [clientDashboardController::class, 'updateSelection'])->name('updateSelection');
    //end wave

    //criteria
    Route::get('admin/createcriteria', [CriteriaController::class, 'index'])->name('createcriteria');
    Route::post('admin/storeCriteria', [CriteriaController::class, 'storeCriteria'])->name('storeCriteria');
    Route::get('admin/getCriteria', [CriteriaController::class, 'getCriteria'])->name('getCriteria');
    Route::post('admin/updateCriteria', [CriteriaController::class, 'updateCriteria'])->name('updateCriteria');
    Route::delete('admin/deleteCriteria', [CriteriaController::class, 'deleteCriteria'])->name('deleteCriteria');
    //end criteria

    //create hierarchy
    // routes/web.php
    Route::get('admin/createhierarchy', [HierarchynameController::class, 'createhierarchy'])->name('createhierarchy');
    Route::post('/processdata', [HierarchynameController::class, 'processData'])->name('processdata');

    Route::get('admin/assignHierarchy', [HierarchynameController::class, 'assignHierarchy'])->name('assignHierarchy');
    Route::get('superadmin/getFormatsAndHierarchies', [HierarchynameController::class, 'getFormatsAndHierarchies']);
    //end hierarchy
    //asign
    Route::post('admin/assignformat', [HierarchynameController::class, 'assignformat'])->name('assignformat');
    Route::get('admin/department-users', [DepartmentController::class, 'getClientDepartmentUsers']);
    // Route::get('admin/DepartmentUser', [DepartmentController::class, 'getDepartments'])->name('DepartmentUser');
    Route::get('admin/DepartmentUser', [DepartmentController::class, 'getDepartments'])->name('DepartmentUser');

    Route::post('/departments/create', [DepartmentController::class, 'createDepartment'])->name('create.department');
    Route::post('/get-levels', [HierarchynameController::class, 'getLevels'])->name('get.levels');
    Route::post('/get-data-by-level', [HierarchynameController::class, 'getDataByLevel'])->name('get.data.by.level');
    Route::post('userDepartment', [DepartmentController::class, 'userDepartment'])->name('userDepartment');


    Route::get('superadmin/assignproject', [HierarchynameController::class, 'assignproject'])->name('assignproject');
    Route::post('superadmin/assignprojecttomanager', [HierarchynameController::class, 'assignprojecttomanager'])->name('assignprojecttomanager');
    Route::post('superadmin/assignprojecttomanager1', [HierarchynameController::class, 'assignprojecttomanager1'])->name('assignprojecttomanager1');

    Route::get('superadmin/assignshops', [HierarchynameController::class, 'assignshops'])->name('assignshops');
    Route::get('superadmin/getwave', [HierarchynameController::class, 'getwave']);
    Route::get('superadmin/getwave1', [HierarchynameController::class, 'getwave1']);

    Route::get('superadmin/getshops', [HierarchynameController::class, 'getshops']);
    Route::post('superadmin/assignlocation', [HierarchynameController::class, 'assignlocation'])->name('assignlocation');
    Route::post('superadmin/assignlocation1', [HierarchynameController::class, 'assignlocation1'])->name('assignlocation1');

    //end assign
    //overall work
    Route::get('superadmin/overallperformance', [overallviewController::class, 'overallperformance'])->name('overallperformance');
    Route::get('superadmin/getreport', [overallviewController::class, 'getreport'])->name('getregetreportort');
    Route::get('superadmin/getreport1', [overallviewController::class, 'getreport1'])->name('getregetreportort1');


    //end overall view

    //cotest
    Route::post('superadmin/submit-commentadmin', [contestController::class, 'submitComment'])->name('submit.commentadmin');
    Route::get('superadmin/contestlist1', [contestController::class, 'contestlistadmin'])->name('contestlist1');
    Route::get('superadmin/contestadmin/{shopId}/{waveid}', [contestController::class, 'contestadmin'])->name('contestadmin');
    Route::post('superadmin/update-comment-statusadmin', [contestController::class, 'updateCommentStatusadmin'])->name('updateCommentStatusadmin');
    Route::post('superadmin/submitResponseadmin', [contestController::class, 'submitResponseadmin'])->name('submitResponseadmin');
    Route::get('superadmin/contestshow', [contestController::class, 'contestshow'])->name('contestshow');
});
Route::group(['middleware' => 'superadmin'], function () {
    Route::get('superadmin/dashboard', [DashboardController::class, 'dashboard']);
    Route::get('clientedit', [UserManagementController::class, 'clientedit'])->name('clientedit');
    Route::post('createuser', [AuthController::class, 'createuser'])->name('createuser');
    Route::post('update-user', [UserManagementController::class, 'update1'])->name('updateUser1');
    Route::delete('users/{id}', [UserManagementController::class, 'destroy1'])->name('users.destroy1');
});
Route::group(['middleware' => 'branch'], function () {
    Route::get('branch/dashboard', [locationDashboardController::class, 'reportdashboard']);
    Route::get('branch/viewReport1/{id}', [locationDashboardController::class, 'viewReport1'])->name('viewReport1');
    Route::post('branch/submit-comment1', [contestController::class, 'submitComment1'])->name('submit.comment1');

    // Route::get('client/reportdashboard/{id}', [visitReportController::class, 'reportdashboard'])->name('reportdashboard');

});
Route::group(['middleware' => 'level'], function () {
    Route::get('hierarchylevel/viewdashboard/{locationName}', [hirechylevelController::class, 'viewdashboard'])->name('viewdashboard1');
    Route::get('hierarchylevel/get-waves/{formatId}', [DashboardController::class, 'getWaves'])->name('getWaves');
    Route::post('hierarchylevel/session1', [clientDashboardController::class, 'session1'])->name('session1');
    Route::get('hierarchylevel/visit-report1', [locationDashboardController::class, 'visitreport1'])->name('visit-report1');
    Route::get('hierarchylevel/reportdashboard1/{id}', [locationDashboardController::class, 'reportdashboard1'])->name('reportdashboard1');
    Route::get('hierarchylevel/viewReport2/{id}', [locationDashboardController::class, 'viewReport2'])->name('viewReport2');

    Route::get('hierarchylevel/pdfdownload/{id}', [visitReportController::class, 'pdfdownload'])->name('pdfdownload1');
    Route::get('hierarchylevel/exceldownload/{id}', [visitReportController::class, 'exceldownload'])->name('exceldownload1');


    Route::get('hierarchylevel/download-pdf1', [visitReportController::class, 'bulkPdfDownload'])->name('pdfbulkdownload1');

    Route::get('hierarchylevel/download-excel', [visitReportController::class, 'bulkExcelDownload'])->name('excelbulkdownload1');

    // Route::get('client/reportdashboard/{id}', [visitReportController::class, 'reportdashboard'])->name('reportdashboard');

});
Route::group(['middleware' => 'manager'], function () {
    // Route::get('manager/dashboard', [DashboardController::class, 'dashboard']);
    Route::get('manager/dashboard', [managerController::class, 'mainmanager'])->name('mainmanager');
    // Route::get('manager/mainmanager', [managerController::class, 'mainmanager'])->name('mainmanager');
    Route::get('manager/getformat', [managerController::class, 'getUserFormat']);
    Route::get('manager/getwave', [shopperController::class, 'getwave']);
    Route::get('manager/getreport', [managerController::class, 'getreport']);
});
Route::group(['middleware' => 'shopper'], function () {
    Route::get('shopper/dashboard', [DashboardController::class, 'dashboard']);
    //shopper end start
    Route::post('shopper/getdata', [ShopperController::class, 'getdata'])->name('getdata');
    // Route::get('shopper/mainshopper', [shopperController::class, 'mainshopper'])->name('mainshopper');
    Route::get('shopper/getformat', [shopperController::class, 'getUserFormat']);
    Route::get('shopper/getwave', [shopperController::class, 'getwave']);
    Route::get('shopper/getshops', [shopperController::class, 'getshops']);
    //shopper end end
});

Route::group(['middleware' => 'client'], function () {
    Route::get('client/dashboard', [DashboardController::class, 'dashboard']);
    Route::get('client/get-waves/{formatId}', [DashboardController::class, 'getWaves']);
    Route::post('client/session', [clientDashboardController::class, 'session'])->name('session');
    Route::get('client/viewdashboard', [clientDashboardController::class, 'viewdashboard'])->name('viewdashboard');
    Route::get('client/sectionDashboard', [sectiondashboardController::class, 'sectionDashboard'])->name('sectionDashboard');
    Route::get('client/visit-report', [visitReportController::class, 'visitreport'])->name('visit-report');
    Route::get('client/comment-analysis', [commentanalysisController::class, 'commentanalysis'])->name('comment-analysis');
    Route::get('client/commentanalysis/{questionID}', [CommentAnalysisController::class, 'getCommentAnalysis'])->name('commentanalysis');
    Route::get('client/keyword/{questionID}', [CommentAnalysisController::class, 'getKeywords'])->name('keyword');
    Route::get('client/viewReport/{id}', [visitReportController::class, 'viewReport'])->name('viewReport');
    Route::get('client/pdfdownload/{id}', [visitReportController::class, 'pdfdownload'])->name('pdfdownload');
    Route::get('client/exceldownload/{id}', [visitReportController::class, 'exceldownload'])->name('exceldownload');
    Route::get('client/summaryreport', [visitReportController::class, 'summaryreport'])->name('summaryreport');
    Route::get('client/excelsummaryreport', [visitReportController::class, 'excelsummaryreport'])->name('excelsummaryreport');

    Route::post('client/download-excel1', [visitReportController::class, 'downloadExcel1'])->name('download.excel1');

    Route::get('client/download-pdf', [visitReportController::class, 'bulkPdfDownload'])->name('pdfbulkdownload');

    Route::get('client/download-excel', [visitReportController::class, 'bulkExcelDownload'])->name('excelbulkdownload');
    Route::get('client/reportdashboard/{id}', [visitReportController::class, 'reportdashboard'])->name('reportdashboard');
    Route::get('client/customreport', [visitReportController::class, 'customreport'])->name('customreport');
    // In routes/web.php
    Route::get('client/fetch-results', [visitReportController::class, 'fetchResults'])->name('fetchResults');
    Route::get('client/fetchFeatureResults', [visitReportController::class, 'fetchFeatureResults'])->name('fetchFeatureResults');
    Route::post('client/generate-report', [visitReportController::class, 'generateReport'])->name('generateReport');
    Route::post('client/submit-comment', [contestController::class, 'submitComment'])->name('submit.comment');
    Route::get('client/contestlist', [contestController::class, 'contestlist'])->name('contestlist');
    Route::get('client/contest/{shopId}/{waveid}', [contestController::class, 'contest'])->name('contest');
    Route::post('client/update-comment-status', [contestController::class, 'updateCommentStatus'])->name('updateCommentStatus');
    Route::post('client/submitResponse', [contestController::class, 'submitResponse'])->name('submitResponse');
});
// Route::get('/runcmd', function () {
//     // List of Artisan commands to run
//     $artisanCommands = [
//         'route:clear',
//         'config:clear',
//         'cache:clear',
//         'view:clear'
//     ];

//     $output = '';
//     foreach ($artisanCommands as $command) {
//         Artisan::call($command);
//         $output .= "<pre>" . Artisan::output() . "</pre>";
//     }

//     return $output;
// });
//for report page

Route::group(['middleware' => 'survayuser'], function () {
    Route::get('/survayuser/survay', [survayController::class, 'survay'])->name('survay');
    Route::post('/survey/search', [survayController::class, 'search'])->name('survey.search');
    // In your web.php or api.php routes file
    Route::post('/survey/submit', [survayController::class, 'submitSurvey'])->name('survey.submit');
    // Route::post('/survey/uploadAttachment', [survayController::class, 'uploadAttachment'])->name('survey.uploadAttachment');



    //shopper end end
});
// Route::get('/survay', [survayController::class, 'survay'])->name('survay');
Route::get('/surveydata', [survayController::class, 'surveydata'])->name('surveydata');
Route::get('/BranchBanking', [survayController::class, 'BranchBanking'])->name('BranchBanking');
Route::get('/Banca', [survayController::class, 'Banca'])->name('Banca');
Route::get('/Consumer', [survayController::class, 'Consumer'])->name('Consumer');
Route::get('/MobileBanking', [survayController::class, 'MobileBanking'])->name('MobileBanking');
Route::get('/Remittance', [survayController::class, 'Remittance'])->name('Remittance');
Route::get('/ClosedAccounts', [survayController::class, 'ClosedAccounts'])->name('ClosedAccounts');

Route::get('/viewreport', [reportController::class, 'viewreport'])->name('viewreport');
Route::post('/store', [reportController::class, 'store'])->name('store');
Route::post('/proceedback', [reportController::class, 'proceedback'])->name('proceedback');
Route::post('/storefile', [reportController::class, 'storefile'])->name('storefile');
Route::post('/save-option', [reportController::class, 'saveOption'])->name('saveOption');
Route::post('/save-keyword', [reportController::class, 'savekeyword'])->name('savekeyword');
Route::post('/save-comment', [reportController::class, 'saveComment'])->name('saveComment');
Route::post('/save-shop-data', [reportController::class, 'saveShopData']);
Route::post('/save', [reportController::class, 'save'])->name('save');

Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');

//end report