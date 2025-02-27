<?php

namespace App\Services;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $folder_id;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google-credentials/reporting-449406-8d19d955d271.json'));
        $this->client->addScope(Google_Service_Drive::DRIVE);
        
        $this->service = new Google_Service_Drive($this->client);
        
        // Root folder ID from your config
        $this->folder_id = config('services.google.folder_id');
    }

    public function uploadFile($file, $folderPath)
    {
        try {
            // Create folder structure if it doesn't exist
            $currentFolderId = $this->folder_id;
            $folders = explode('/', $folderPath);
            
            foreach ($folders as $folder) {
                $currentFolderId = $this->createOrGetFolder($folder, $currentFolderId);
            }

            // Upload file
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $file->getClientOriginalName(),
                'parents' => [$currentFolderId]
            ]);

            $content = file_get_contents($file->getRealPath());
            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id,webViewLink'
            ]);

            return [
                'file_id' => $file->id,
                'view_link' => $file->webViewLink
            ];

        } catch (\Exception $e) {
            \Log::error('Google Drive upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function deleteFile($fileId)
    {
        try {
            $this->service->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Drive delete failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    protected function createOrGetFolder($folderName, $parentId)
    {
        // Check if folder exists
        $response = $this->service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.folder' and name='$folderName' and '$parentId' in parents and trashed=false",
            'spaces' => 'drive'
        ]);

        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }

        // Create new folder
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);

        $folder = $this->service->files->create($folderMetadata, [
            'fields' => 'id'
        ]);

        return $folder->getId();
    }

    public function verifyConnection()
    {
        try {
            // Debug info
            \Log::info('Attempting to connect to Google Drive', [
                'folder_id' => $this->folder_id,
                'service_email' => $this->client->getClientId()
            ]);

            // Basic test - just list files without folder filter first
            $response = $this->service->files->listFiles([
                'pageSize' => 1
            ]);

            return [
                'success' => true,
                'folder_id' => $this->folder_id,
                'service_email' => $this->client->getClientId(),
                'files_count' => count($response->getFiles())
            ];

        } catch (\Exception $e) {
            \Log::error('Google Drive connection verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'folder_id' => $this->folder_id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'folder_id' => $this->folder_id
            ];
        }
    }

    public function fetchFile($fileId)
    {
        try {
            \Log::info('Fetching file from Google Drive', [
                'file_id' => $fileId
            ]);

            // Get file metadata
            $file = $this->service->files->get($fileId, [
                'fields' => 'id, name, mimeType, webViewLink, webContentLink'
            ]);

            return [
                'file_id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mimeType,
                'view_link' => $file->webViewLink,
                'download_link' => $file->webContentLink ?? null
            ];

        } catch (\Exception $e) {
            \Log::error('Google Drive fetch failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    public function listFolderContents($folderId = null)
    {
        try {
            $folderId = $folderId ?? $this->folder_id;
            
            \Log::info('Listing folder contents', [
                'folder_id' => $folderId
            ]);

            $response = $this->service->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id, name, mimeType, webViewLink, webContentLink)',
                'orderBy' => 'name'
            ]);

            $files = [];
            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'file_id' => $file->id,
                    'name' => $file->name,
                    'mime_type' => $file->mimeType,
                    'view_link' => $file->webViewLink,
                    'download_link' => $file->webContentLink ?? null,
                    'is_folder' => $file->mimeType === 'application/vnd.google-apps.folder'
                ];
            }

            return $files;

        } catch (\Exception $e) {
            \Log::error('Google Drive list contents failed', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId
            ]);
            throw $e;
        }
    }
} 