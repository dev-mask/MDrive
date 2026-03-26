<?php
namespace App\Services;

use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Http\MediaFileUpload;

class GoogleDriveService {
    private GoogleDrive $driveService;
    
    // Map of Google Workspace MIME types to export formats
    private const EXPORT_TYPES = [
        'application/vnd.google-apps.document' => [
            'mimeType' => 'application/pdf',
            'extension' => '.pdf'
        ],
        'application/vnd.google-apps.spreadsheet' => [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => '.xlsx'
        ],
        'application/vnd.google-apps.presentation' => [
            'mimeType' => 'application/pdf',
            'extension' => '.pdf'
        ],
        'application/vnd.google-apps.drawing' => [
            'mimeType' => 'image/png',
            'extension' => '.png'
        ],
    ];

    public function __construct(\Google\Client $client) {
        $this->driveService = new GoogleDrive($client);
    }

    /**
     * List files in a folder (or root)
     */
    public function listFiles(?string $folderId = null, ?string $pageToken = null, int $pageSize = 30, ?string $orderBy = null): array {
        // Default to root folder — this ensures only top-level items show,
        // matching Google Drive's tree structure behavior
        $parentId = $folderId ?: 'root';
        $query = "'{$parentId}' in parents and trashed = false";

        $params = [
            'q' => $query,
            'pageSize' => $pageSize,
            'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, createdTime, iconLink, thumbnailLink, webViewLink, webContentLink, parents, shared, starred)',
            'orderBy' => $orderBy ?: 'folder,name asc',
            'supportsAllDrives' => true,
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $results = $this->driveService->files->listFiles($params);
        
        return [
            'files' => $this->formatFiles($results->getFiles()),
            'nextPageToken' => $results->getNextPageToken(),
        ];
    }

    /**
     * Search files by name
     */
    public function searchFiles(string $query, ?string $pageToken = null, int $pageSize = 30): array {
        $sanitized = str_replace("'", "\\'", $query);
        $q = "name contains '{$sanitized}' and trashed = false";

        $params = [
            'q' => $q,
            'pageSize' => $pageSize,
            'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, createdTime, iconLink, thumbnailLink, webViewLink, webContentLink, parents, shared, starred)',
            'orderBy' => 'modifiedTime desc',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $results = $this->driveService->files->listFiles($params);

        return [
            'files' => $this->formatFiles($results->getFiles()),
            'nextPageToken' => $results->getNextPageToken(),
        ];
    }

    /**
     * List recent files
     */
    public function listRecent(?string $pageToken = null, int $pageSize = 30): array {
        $params = [
            'q' => 'trashed = false',
            'pageSize' => $pageSize,
            'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, createdTime, iconLink, thumbnailLink, webViewLink, webContentLink, parents, shared, starred)',
            'orderBy' => 'viewedByMeTime desc',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $results = $this->driveService->files->listFiles($params);

        return [
            'files' => $this->formatFiles($results->getFiles()),
            'nextPageToken' => $results->getNextPageToken(),
        ];
    }

    /**
     * List trashed files
     */
    public function listTrash(?string $pageToken = null, int $pageSize = 30): array {
        $params = [
            'q' => 'trashed = true',
            'pageSize' => $pageSize,
            'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, createdTime, iconLink, thumbnailLink, webViewLink, webContentLink, parents, shared, starred)',
            'orderBy' => 'modifiedTime desc',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $results = $this->driveService->files->listFiles($params);

        return [
            'files' => $this->formatFiles($results->getFiles()),
            'nextPageToken' => $results->getNextPageToken(),
        ];
    }

    /**
     * Upload a file to Google Drive
     */
    public function uploadFile(string $fileName, string $filePath, string $mimeType, ?string $folderId = null): array {
        $fileMetadata = new DriveFile([
            'name' => $fileName,
        ]);

        if ($folderId) {
            $fileMetadata->setParents([$folderId]);
        }

        $content = file_get_contents($filePath);

        $file = $this->driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, name, mimeType, size, modifiedTime, createdTime, iconLink, thumbnailLink, webViewLink, webContentLink',
        ]);

        return $this->formatFile($file);
    }

    /**
     * Create a folder
     */
    public function createFolder(string $name, ?string $parentId = null): array {
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        if ($parentId) {
            $fileMetadata->setParents([$parentId]);
        }

        $file = $this->driveService->files->create($fileMetadata, [
            'fields' => 'id, name, mimeType, size, modifiedTime, createdTime, iconLink, thumbnailLink, webViewLink',
        ]);

        return $this->formatFile($file);
    }

    /**
     * Rename a file
     */
    public function renameFile(string $fileId, string $newName): array {
        $fileMetadata = new DriveFile([
            'name' => $newName,
        ]);

        $file = $this->driveService->files->update($fileId, $fileMetadata, [
            'fields' => 'id, name, mimeType, size, modifiedTime',
        ]);

        return $this->formatFile($file);
    }

    /**
     * Trash a file (soft delete)
     */
    public function trashFile(string $fileId): void {
        $fileMetadata = new DriveFile([
            'trashed' => true,
        ]);
        $this->driveService->files->update($fileId, $fileMetadata);
    }

    /**
     * Restore a trashed file
     */
    public function restoreFile(string $fileId): void {
        $fileMetadata = new DriveFile([
            'trashed' => false,
        ]);
        $this->driveService->files->update($fileId, $fileMetadata);
    }

    /**
     * Permanently delete a file
     */
    public function permanentDelete(string $fileId): void {
        $this->driveService->files->delete($fileId);
    }

    /**
     * Download a file
     */
    public function downloadFile(string $fileId): array {
        $file = $this->driveService->files->get($fileId, [
            'fields' => 'id, name, mimeType, size',
        ]);

        $mimeType = $file->getMimeType();
        $fileName = $file->getName();

        // Check if it's a Google Workspace file that needs exporting
        if (isset(self::EXPORT_TYPES[$mimeType])) {
            $exportInfo = self::EXPORT_TYPES[$mimeType];
            $response = $this->driveService->files->export($fileId, $exportInfo['mimeType'], ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            $fileName .= $exportInfo['extension'];
            $mimeType = $exportInfo['mimeType'];
        } else {
            $response = $this->driveService->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
        }

        return [
            'content' => $content,
            'fileName' => $fileName,
            'mimeType' => $mimeType,
            'size' => strlen($content),
        ];
    }

    /**
     * Get file preview info
     */
    public function getPreviewInfo(string $fileId): array {
        $file = $this->driveService->files->get($fileId, [
            'fields' => 'id, name, mimeType, size, modifiedTime, thumbnailLink, webViewLink, webContentLink',
        ]);

        return $this->formatFile($file);
    }

    /**
     * Create a shareable link
     */
    public function shareFile(string $fileId): string {
        $permission = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);

        $this->driveService->permissions->create($fileId, $permission);

        $file = $this->driveService->files->get($fileId, [
            'fields' => 'webViewLink',
        ]);

        return $file->getWebViewLink();
    }

    /**
     * Format a single file object
     */
    private function formatFile($file): array {
        return [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize() ? (int) $file->getSize() : null,
            'modifiedTime' => $file->getModifiedTime(),
            'createdTime' => $file->getCreatedTime(),
            'iconLink' => $file->getIconLink(),
            'thumbnailLink' => $file->getThumbnailLink(),
            'webViewLink' => $file->getWebViewLink(),
            'webContentLink' => $file->getWebContentLink(),
            'isFolder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
            'shared' => $file->getShared(),
            'starred' => $file->getStarred(),
        ];
    }

    /**
     * Format an array of file objects
     */
    private function formatFiles(array $files): array {
        return array_map([$this, 'formatFile'], $files);
    }
}
