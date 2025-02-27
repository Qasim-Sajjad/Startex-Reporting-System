<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\newAuthController;
use App\Http\Controllers\EndUserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskMonitoringController;
use App\Http\Controllers\ProcessController; 
use App\Http\Controllers\HeirarachyController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\GuidelineController;
use App\Http\Controllers\clientDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;


// Sanctum authenticated user retrieval
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes for client login and registration
Route::post('/clients/login', [EndUserController::class, 'login']);
Route::post('/clients/logout', [EndUserController::class, 'logout']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/clients/create', [ClientController::class, 'store']);
    Route::post('/clients/createuser', [ClientController::class, 'createDepartmentUser']);
    Route::get('/clients/department-users', [ClientController::class, 'getClientDepartmentUsers']);
    Route::get('/clients/get-departments', [ClientController::class, 'getDepartments']);
    Route::get('clients/get-all-processes', [ProcessController::class, 'getAllProcesses']);


    //Task
    Route::post('clients/create-task', [ClientController::class, 'createTask']);
    Route::put('clients/tasks/{id}/update-task-status', [ClientController::class, 'updateTaskStatus']);
    Route::get('clients/tasks/overdue', [TaskMonitoringController::class, 'getOverdueTasks']);
    Route::get('tasks/overdue', [TaskMonitoringController::class, 'getUserOverdueTasks']);
     //Process
    Route::post('/assign-process', [ProcessController::class, 'assignProcess']);
    Route::get('/hierarchies', [HeirarachyController::class, 'getHierarchies']);
    Route::get('/hierarchies{id}', [HeirarachyController::class, 'getHierarchyById']);
    Route::get('/process/end-locations', [ReportController::class, 'getProcessEndLocations']);
    Route::get('/location/reports', [ReportController::class, 'getLocationReports']);
    Route::get('/report/detailed', [ReportController::class, 'getDetailedReport']);
    Route::get('/report/analytics', [ReportController::class, 'getReportAnalytics']);
    Route::get('/process/analytics', [ReportController::class, 'getProcessAnalytics']);

  
  
  //aiza add 
 // Route::get('/waves/{processId}', [clientDashboardController::class, 'getWaves']);
  Route::get('/get-waves/{processId}', [clientDashboardController::class, 'getWavesAPI'])->name('getWaves');
  Route::get('process-details/{process_id}/{wave_id}', [clientDashboardController::class, 'dashboard'])->name('process.details');



});

// Public routes for generic user registration and login
Route::post('/register', [newAuthController::class, 'register']);
Route::post('/login', [newAuthController::class, 'login']);

// Sanctum-protected generic user logout
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [newAuthController::class, 'logout']);
});

// End-User Authentication and Management APIs
Route::post('/users/login', [EndUserController::class, 'login']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users/profile', [EndUserController::class, 'profile']);
    Route::put('/users/update', [EndUserController::class, 'update']);
    Route::post('/users/logout', [EndUserController::class, 'logout']);
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/fetch-dept-users', [TaskController::class, 'fetchDepartmentUsers']);
    Route::post('/tasks', [TaskController::class, 'createTask']);
    Route::get('/tasks/assigned', [TaskController::class, 'fetchAssignedTasks']);
    Route::put('/tasks/{id}/status', [TaskController::class, 'updateTaskStatus']);
    Route::get('/tasks/filtered', [TaskController::class, 'getFilteredTasks']);
    

    Route::post('/tasks/{id}/add_comment', [TaskController::class, 'addComment']);
    Route::get('/tasks/{id}/get_comment', [TaskController::class, 'getComments']);

    Route::post('/upload-attachments', [AttachmentController::class, 'upload']);
    Route::get('/fetch-attachments', [AttachmentController::class, 'fetchAttachments']);
    Route::post('/rules-attachments', [AttachmentController::class, 'setAttachmentRules']);

    Route::post('/upload-guidelines', [GuidelineController::class, 'uploadGuideline']);
    Route::get('/fetch-guidelines', [GuidelineController::class, 'fetchGuidelines']);

    Route::get('/users-processes', [ProcessController::class, 'fetchUserProcesses']);
    Route::get('/users-processes-details', [ProcessController::class, 'fetchProcessDetails']);
    Route::post('/users-submit-responses', [ProcessController::class, 'submitResponses']);

    Route::get('/upcoming-checklists', [ProcessController::class, 'getUpcomingChecklists']);

    // Route::delete('remove-attachments/{id}', [AttachmentController::class, 'delete']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications', [NotificationController::class, 'fetchNotifications']);

});

  Route::post('/forgot-password', [EndUserController::class, 'forgotPassword']);
  Route::post('/reset-password', [EndUserController::class, 'resetPassword']);
  
  // For web link (GET request)
  Route::get('/reset-password', [EndUserController::class, 'showResetForm']);





