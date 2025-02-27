<?php
namespace App\Http\Controllers;

use App\Models\Guideline;
use App\Models\Attachment;
use App\Models\AttachmentEntity;
use App\Models\AttachmentRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ClientDatabaseManager;
use App\Services\GoogleDriveService;

class GuidelineController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function uploadGuideline(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:jpeg,png,mp4,doc,docx,pdf|max:20480', // 20 MB max
                'entity_id' => 'nullable|integer',
                'entity_type' => 'nullable|string|in:App\\Models\\Process,App\\Models\\Format,App\\Models\\Question,App\\Models\\Section',
                'description' => 'nullable|string',
            ]);
    
            $file = $request->file('file');
            $user = $request->user();
    
            // Dynamically switch to the client's database
            ClientDatabaseManager::setConnection($user->database_name);
    
            // Create folder path for Google Drive
            $clientFolder = "client_{$user->id}";
            $guidelineFolder = $validated['entity_type'] ? class_basename($validated['entity_type']) : 'general';
            $folderPath = "guidelines/{$clientFolder}/{$guidelineFolder}";
    
            // Upload to Google Drive
            $driveFile = $this->driveService->uploadFile($file, $folderPath);
    
            // Save the guideline details
            $guideline = Guideline::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'guideline_type' => $file->getClientOriginalExtension(),
                'entity_id' => $validated['entity_id'] ?? null,
                'entity_type' => $validated['entity_type'] ?? null,
                'filepath' => $driveFile['file_id'], // Store Drive file ID
                'drive_link' => $driveFile['view_link'], // Store view link
                'uploaded_by' => $user->id,
            ]);
    
            return response()->json([
                'data' => $guideline,
                'status_code' => 201,
                'message' => 'Guideline uploaded successfully',
                'success' => true,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'status_code' => 422,
                'message' => 'Validation failed',
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to upload guideline',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function fetchGuidelines(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_type' => 'nullable|string|in:App\\Models\\Process,App\\Models\\Format,App\\Models\\Section,App\\Models\\Question',
                'entity_id' => 'nullable|integer',
            ]);

            $user = $request->user();
            ClientDatabaseManager::setConnection($user->database_name);

            $query = Guideline::query();

            if (!empty($validated['entity_type'])) {
                $query->where('entity_type', $validated['entity_type']);
            }

            if (!empty($validated['entity_id'])) {
                $query->where('entity_id', $validated['entity_id']);
            }

            $guidelines = $query->get();

            // Fetch Google Drive details for each guideline
            foreach ($guidelines as $guideline) {
                try {
                    $driveFile = $this->driveService->fetchFile($guideline->filepath);
                    $guideline->drive_details = $driveFile;
                    $guideline->view_url = $driveFile['view_link'];
                    $guideline->download_url = $driveFile['download_link'];
                } catch (\Exception $e) {
                    \Log::warning('Failed to fetch drive details for guideline', [
                        'guideline_id' => $guideline->id,
                        'error' => $e->getMessage()
                    ]);
                    $guideline->drive_details = null;
                }
            }

            return response()->json([
                'data' => $guidelines,
                'status_code' => 200,
                'message' => 'Guidelines fetched successfully',
                'success' => true,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'status_code' => 422,
                'message' => 'Validation failed',
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch guidelines', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch guidelines',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}