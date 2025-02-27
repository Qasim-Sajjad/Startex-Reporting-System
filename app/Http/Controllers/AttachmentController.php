<?php
namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\AttachmentEntity;
use App\Models\AttachmentRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ClientDatabaseManager;
use App\Services\GoogleDriveService;


class AttachmentController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function upload(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:20480',
                'entity_id' => 'required|integer',
                'entity_type' => 'required|string|in:App\\Models\\Task,App\\Models\\Section,App\\Models\\Question,App\\Models\\Process,App\\Models\\Format',
            ]);
           

            $file = $request->file('file');
            $user = $request->user();

            ClientDatabaseManager::setConnection($user->database_name);

           
            if ($validated['entity_type'] != "App\Models\Task") {
                $this->validateAttachment($request);
            }
         

          
            // Create folder path for Google Drive
            $entityFolder = class_basename($validated['entity_type']);
            $userFolder = "user_{$user->id}";
            $userDatabase = $user->database_name;
            $folderPath = "attachments/{$userDatabase}/{$entityFolder}/{$userFolder}";
           

            // Upload to Google Drive
            $driveFile = $this->driveService->uploadFile($file, $folderPath);
           

            // Save attachment in database
            $attachment = Attachment::create([
                'filename' => $file->getClientOriginalName(),
                'filepath' => $driveFile['file_id'], // Store Google Drive file ID
                'drive_link' => $driveFile['view_link'], // Store view link
                'filetype' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);

            AttachmentEntity::create([
                'attachment_id' => $attachment->id,
                'entity_id' => $validated['entity_id'],
                'entity_type' => $validated['entity_type'],
            ]);

            return response()->json([
                'data' => [
                    'attachment' => $attachment,
                    'entity' => [
                        'entity_id' => $validated['entity_id'],
                        'entity_type' => $validated['entity_type'],
                    ],
                ],
                'status_code' => 201,
                'message' => 'File uploaded successfully',
                'success' => true,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to upload the file',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchAttachments(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'entity_type' => 'nullable|string|in:App\\Models\\Task,App\\Models\\Section,App\\Models\\Question,App\\Models\\Process,App\\Models\\Format',
                'entity_id' => 'nullable|integer',
            ]);

            $user = auth('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'data' => null,
                    'status_code' => 401,
                    'message' => 'Unauthorized',
                    'success' => false,
                ], 401);
            }

            if ($user->role !== 'Client Admin') {
                return response()->json([
                    'data' => null,
                    'status_code' => 403,
                    'message' => 'Forbidden: You do not have the required permissions.',
                    'success' => false,
                ], 403);
            }

            ClientDatabaseManager::setConnection($user->database_name);

            $query = AttachmentEntity::query()
                ->with(['attachment', 'attachment.uploader'])
                ->whereHas('attachment', function ($q) use ($validated) {
                    $q->where('uploaded_by', $validated['user_id']);
                });

            if (!empty($validated['entity_type'])) {
                $query->where('entity_type', $validated['entity_type']);
            }

            if (!empty($validated['entity_id'])) {
                $query->where('entity_id', $validated['entity_id']);
            }

            $attachments = $query->get();

            // Fetch Google Drive details for each attachment
            $attachments->each(function ($attachment) {
                try {
                    $driveFile = $this->driveService->fetchFile($attachment->attachment->filepath);
                    $attachment->attachment->drive_details = $driveFile;
                    $attachment->attachment->view_url = $driveFile['view_link'];
                    $attachment->attachment->download_url = $driveFile['download_link'];
                } catch (\Exception $e) {
                    \Log::warning('Failed to fetch drive details for attachment', [
                        'attachment_id' => $attachment->id,
                        'error' => $e->getMessage()
                    ]);
                    $attachment->attachment->drive_details = null;
                }

                if ($attachment->attachment->uploader) {
                    $attachment->attachment->uploader_name = $attachment->attachment->uploader->name;
                }
            });

            return response()->json([
                'data' => $attachments,
                'status_code' => 200,
                'message' => 'Attachments fetched successfully',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch attachments', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch attachments',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $attachment = Attachment::findOrFail($id);
            
            // Delete from Google Drive
            $this->driveService->deleteFile($attachment->filepath); // filepath contains Drive file ID

            $attachment->entities()->detach();
            $attachment->delete();

            return response()->json([
                'message' => 'File deleted successfully',
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete file',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setAttachmentRules(Request $request)
    {
     
        try {
            $validated = $request->validate([
                'entity_type' => 'required|string|in:App\\Models\\Format,App\\Models\\Section,App\\Models\\Question',
                'entity_id' => 'required|integer',
                'allowed_types' => 'required|string', 
            ]);
    
            // Validate the categories
            $validCategories = ['image', 'video', 'audio', 'file'];
            $allowedCategories = explode(',', $validated['allowed_types']);
            foreach ($allowedCategories as $category) {
                if (!in_array(trim($category), $validCategories)) {
                    return response()->json([
                        'data' => null,
                        'status_code' => 422,
                        'message' => "Invalid allowed type: {$category}. Allowed values are: " . implode(', ', $validCategories),
                        'success' => false,
                    ], 422);
                }
            }
    
            $user = $request->user();
            ClientDatabaseManager::setConnection($user->database_name);
            $rule = AttachmentRule::updateOrCreate(
                [
                    'entity_type' => $validated['entity_type'],
                    'entity_id' => $validated['entity_id'],
                ],
                [
                    'allowed_types' => $validated['allowed_types'],
                ]
            );
    
            return response()->json([
                'data' => $rule,
                'status_code' => 200,
                'message' => 'Attachment rule set successfully',
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }
    
    private function validateAttachment(Request $request)
    {
        $user = $request->user();
          // Set the database connection for the user's specific database
        ClientDatabaseManager::setConnection($user->database_name);

    
        // Get the rule for the entity
        $rule = AttachmentRule::where('entity_type', $request->entity_type)
            ->where('entity_id', $request->entity_id)
            ->first();
    
        if (!$rule) {
            throw new \Exception('No attachment rules found for the specified entity.');
        }
    
        // Map categories to valid extensions
        $categoryExtensions = [
            'image' => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'mkv', 'flv', 'webm'],
            'audio' => ['mp3', 'wav', 'aac', 'ogg', 'flac'],
            'file' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
        ];
    
        // Expand categories into extensions
        $allowedExtensions = [];
        foreach (explode(',', $rule->allowed_types) as $category) {
            $allowedExtensions = array_merge($allowedExtensions, $categoryExtensions[trim($category)]);
        }
    
        // Validate file extension
        $fileExtension = $request->file('file')->getClientOriginalExtension();
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new \Exception("The file type '{$fileExtension}' is not allowed. Allowed types are: " . implode(', ', $allowedExtensions));
        }
    }
    
    

}
