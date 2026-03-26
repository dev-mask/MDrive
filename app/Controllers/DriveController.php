<?php
namespace App\Controllers;

use App\Services\GoogleAuthService;
use App\Services\GoogleDriveService;
use App\Services\TokenService;
use App\Middleware\AuthMiddleware;
use App\Models\User;

class DriveController {
    private array $config;
    private User $userModel;
    private TokenService $tokenService;

    public function __construct(array $config) {
        $this->config = $config;
        $this->userModel = new User();
        $this->tokenService = new TokenService();
    }

    /**
     * Get an authenticated Google Drive service
     */
    private function getDriveService(): GoogleDriveService {
        $userId = AuthMiddleware::getUserId();
        $user = $this->userModel->findById($userId);
        
        if (!$user || !$user['access_token']) {
            $this->jsonError('No valid token found. Please re-login.', 401);
        }

        $authService = new GoogleAuthService($this->config);
        
        try {
            // Decrypt token data
            $tokenData = $this->tokenService->decryptTokenData($user['access_token']);
            $authService->setAccessToken($tokenData);

            // Check if token is expired, try to refresh
            if ($authService->isTokenExpired()) {
                if (!$user['refresh_token']) {
                    $this->jsonError('Token expired and no refresh token. Please re-login.', 401);
                }

                $refreshToken = $this->tokenService->decrypt($user['refresh_token']);
                $newToken = $authService->refreshToken($refreshToken);
                
                // Save new token
                $encryptedToken = $this->tokenService->encryptTokenData($newToken);
                $expiresAt = isset($newToken['expires_in']) 
                    ? date('Y-m-d H:i:s', time() + $newToken['expires_in']) 
                    : null;
                
                $this->userModel->updateTokens($userId, $encryptedToken, null, $expiresAt);
                
                // Update session
                $_SESSION['token_data'] = $newToken;
            }

            return new GoogleDriveService($authService->getClient());

        } catch (\Exception $e) {
            error_log('MDrive Token Error: ' . $e->getMessage());
            $this->jsonError('Authentication error. Please re-login.', 401);
        }
    }

    /**
     * List files
     */
    public function listFiles(): void {
        try {
            $drive = $this->getDriveService();
            $folderId = $_GET['folderId'] ?? null;
            $pageToken = $_GET['pageToken'] ?? null;
            $pageSize = min((int)($_GET['pageSize'] ?? 30), 100);
            $orderBy = $_GET['orderBy'] ?? null;

            $result = $drive->listFiles($folderId, $pageToken, $pageSize, $orderBy);
            
            // Add star info
            $userId = AuthMiddleware::getUserId();
            $starredIds = $this->userModel->getStarredFileIds($userId);
            foreach ($result['files'] as &$file) {
                $file['isStarred'] = in_array($file['id'], $starredIds);
            }

            $this->json($result);
        } catch (\Exception $e) {
            $this->jsonError('Failed to list files: ' . $e->getMessage());
        }
    }

    /**
     * Upload file
     */
    public function uploadFile(): void {
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonError('No file uploaded or upload error');
            }

            $drive = $this->getDriveService();
            $file = $_FILES['file'];
            $folderId = $_POST['folderId'] ?? null;

            $result = $drive->uploadFile(
                $file['name'],
                $file['tmp_name'],
                $file['type'] ?: 'application/octet-stream',
                $folderId
            );

            // Log activity
            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'upload', $file['name'], $result['id']);

            $this->json(['success' => true, 'file' => $result]);
        } catch (\Exception $e) {
            $this->jsonError('Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Create folder
     */
    public function createFolder(): void {
        try {
            $input = $this->getJsonInput();
            $name = trim($input['name'] ?? '');
            $parentId = $input['parentId'] ?? null;

            if (empty($name)) {
                $this->jsonError('Folder name is required');
            }

            // Sanitize folder name
            $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

            $drive = $this->getDriveService();
            $result = $drive->createFolder($name, $parentId);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'create_folder', $name, $result['id']);

            $this->json(['success' => true, 'folder' => $result]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to create folder: ' . $e->getMessage());
        }
    }

    /**
     * Delete (trash) a file
     */
    public function deleteFile(string $fileId): void {
        try {
            $drive = $this->getDriveService();
            $drive->trashFile($fileId);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'trash', null, $fileId);

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete file: ' . $e->getMessage());
        }
    }

    /**
     * Rename a file
     */
    public function renameFile(string $fileId): void {
        try {
            $input = $this->getJsonInput();
            $name = trim($input['name'] ?? '');

            if (empty($name)) {
                $this->jsonError('New name is required');
            }

            $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

            $drive = $this->getDriveService();
            $result = $drive->renameFile($fileId, $name);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'rename', $name, $fileId);

            $this->json(['success' => true, 'file' => $result]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to rename file: ' . $e->getMessage());
        }
    }

    /**
     * Download a file
     */
    public function downloadFile(string $fileId): void {
        try {
            $drive = $this->getDriveService();
            $result = $drive->downloadFile($fileId);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'download', $result['fileName'], $fileId);

            header('Content-Type: ' . $result['mimeType']);
            header('Content-Disposition: attachment; filename="' . $result['fileName'] . '"');
            header('Content-Length: ' . $result['size']);
            header('Cache-Control: no-cache');
            echo $result['content'];
            exit;
        } catch (\Exception $e) {
            $this->jsonError('Download failed: ' . $e->getMessage());
        }
    }

    /**
     * Get file preview info
     */
    public function previewFile(string $fileId): void {
        try {
            $drive = $this->getDriveService();
            $result = $drive->getPreviewInfo($fileId);
            $this->json($result);
        } catch (\Exception $e) {
            $this->jsonError('Failed to get preview: ' . $e->getMessage());
        }
    }

    /**
     * Share a file
     */
    public function shareFile(string $fileId): void {
        try {
            $drive = $this->getDriveService();
            $link = $drive->shareFile($fileId);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'share', null, $fileId);

            $this->json(['success' => true, 'link' => $link]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to share file: ' . $e->getMessage());
        }
    }

    /**
     * Toggle star on a file
     */
    public function toggleStar(string $fileId): void {
        try {
            $input = $this->getJsonInput();
            $fileName = $input['fileName'] ?? null;
            $mimeType = $input['mimeType'] ?? null;

            $userId = AuthMiddleware::getUserId();
            $isStarred = $this->userModel->toggleStar($userId, $fileId, $fileName, $mimeType);

            $action = $isStarred ? 'star' : 'unstar';
            $this->userModel->logActivity($userId, $action, $fileName, $fileId);

            $this->json(['success' => true, 'starred' => $isStarred]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to toggle star: ' . $e->getMessage());
        }
    }

    /**
     * List starred files
     */
    public function listStarred(): void {
        try {
            $userId = AuthMiddleware::getUserId();
            $starredFiles = $this->userModel->getStarredFiles($userId);
            
            // Get full metadata from Drive for each starred file
            $drive = $this->getDriveService();
            $files = [];
            foreach ($starredFiles as $sf) {
                try {
                    $info = $drive->getPreviewInfo($sf['file_id']);
                    $info['isStarred'] = true;
                    $files[] = $info;
                } catch (\Exception $e) {
                    // File might have been deleted from Drive
                    continue;
                }
            }

            $this->json(['files' => $files, 'nextPageToken' => null]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to list starred: ' . $e->getMessage());
        }
    }

    /**
     * List trash
     */
    public function listTrash(): void {
        try {
            $drive = $this->getDriveService();
            $pageToken = $_GET['pageToken'] ?? null;
            $result = $drive->listTrash($pageToken);
            $this->json($result);
        } catch (\Exception $e) {
            $this->jsonError('Failed to list trash: ' . $e->getMessage());
        }
    }

    /**
     * Restore a file from trash
     */
    public function restoreFile(string $fileId): void {
        try {
            $drive = $this->getDriveService();
            $drive->restoreFile($fileId);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'restore', null, $fileId);

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to restore file: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a file
     */
    public function permanentDelete(string $fileId): void {
        try {
            $drive = $this->getDriveService();
            $drive->permanentDelete($fileId);

            $userId = AuthMiddleware::getUserId();
            $this->userModel->logActivity($userId, 'permanent_delete', null, $fileId);

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to permanently delete: ' . $e->getMessage());
        }
    }

    /**
     * List recent files
     */
    public function listRecent(): void {
        try {
            $drive = $this->getDriveService();
            $pageToken = $_GET['pageToken'] ?? null;
            $result = $drive->listRecent($pageToken);

            $userId = AuthMiddleware::getUserId();
            $starredIds = $this->userModel->getStarredFileIds($userId);
            foreach ($result['files'] as &$file) {
                $file['isStarred'] = in_array($file['id'], $starredIds);
            }

            $this->json($result);
        } catch (\Exception $e) {
            $this->jsonError('Failed to list recent: ' . $e->getMessage());
        }
    }

    /**
     * Get activity log
     */
    public function getActivity(): void {
        try {
            $userId = AuthMiddleware::getUserId();
            $activities = $this->userModel->getActivity($userId);
            $this->json(['activities' => $activities]);
        } catch (\Exception $e) {
            $this->jsonError('Failed to get activity: ' . $e->getMessage());
        }
    }

    /**
     * Search files
     */
    public function searchFiles(): void {
        try {
            $query = trim($_GET['q'] ?? '');
            if (empty($query)) {
                $this->json(['files' => [], 'nextPageToken' => null]);
                return;
            }

            $drive = $this->getDriveService();
            $pageToken = $_GET['pageToken'] ?? null;
            $result = $drive->searchFiles($query, $pageToken);

            $userId = AuthMiddleware::getUserId();
            $starredIds = $this->userModel->getStarredFileIds($userId);
            foreach ($result['files'] as &$file) {
                $file['isStarred'] = in_array($file['id'], $starredIds);
            }

            $this->json($result);
        } catch (\Exception $e) {
            $this->jsonError('Search failed: ' . $e->getMessage());
        }
    }

    // ==================== HELPERS ====================

    private function json(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function jsonError(string $message, int $code = 400): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    private function getJsonInput(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?: [];
    }
}
