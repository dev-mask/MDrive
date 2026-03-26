<?php
namespace App\Models;

use Database;

class User {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Find user by Google ID
     */
    public function findByGoogleId(string $googleId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE google_id = :google_id LIMIT 1');
        $stmt->execute(['google_id' => $googleId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create or update user from Google profile
     */
    public function upsert(array $data): int {
        $existing = $this->findByGoogleId($data['google_id']);

        if ($existing) {
            $stmt = $this->db->prepare('
                UPDATE users SET 
                    name = :name, 
                    email = :email, 
                    profile_picture = :profile_picture,
                    access_token = :access_token,
                    refresh_token = COALESCE(:refresh_token, refresh_token),
                    token_expires_at = :token_expires_at,
                    updated_at = NOW()
                WHERE google_id = :google_id
            ');
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'profile_picture' => $data['profile_picture'] ?? null,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_expires_at' => $data['token_expires_at'] ?? null,
                'google_id' => $data['google_id'],
            ]);
            return $existing['id'];
        } else {
            $stmt = $this->db->prepare('
                INSERT INTO users (google_id, name, email, profile_picture, access_token, refresh_token, token_expires_at)
                VALUES (:google_id, :name, :email, :profile_picture, :access_token, :refresh_token, :token_expires_at)
            ');
            $stmt->execute([
                'google_id' => $data['google_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'profile_picture' => $data['profile_picture'] ?? null,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_expires_at' => $data['token_expires_at'] ?? null,
            ]);
            return (int) $this->db->lastInsertId();
        }
    }

    /**
     * Update tokens for a user
     */
    public function updateTokens(int $userId, string $accessToken, ?string $refreshToken, ?string $expiresAt): void {
        $sql = 'UPDATE users SET access_token = :access_token, token_expires_at = :token_expires_at, updated_at = NOW()';
        $params = [
            'access_token' => $accessToken,
            'token_expires_at' => $expiresAt,
            'id' => $userId,
        ];

        if ($refreshToken) {
            $sql .= ', refresh_token = :refresh_token';
            $params['refresh_token'] = $refreshToken;
        }

        $sql .= ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Log an activity
     */
    public function logActivity(int $userId, string $action, ?string $fileName = null, ?string $fileId = null, ?string $details = null): void {
        $stmt = $this->db->prepare('
            INSERT INTO activity_log (user_id, action, file_name, file_id, details)
            VALUES (:user_id, :action, :file_name, :file_id, :details)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'file_name' => $fileName,
            'file_id' => $fileId,
            'details' => $details,
        ]);
    }

    /**
     * Get recent activity for a user
     */
    public function getActivity(int $userId, int $limit = 20): array {
        $stmt = $this->db->prepare('
            SELECT * FROM activity_log 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ');
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Toggle star for a file
     */
    public function toggleStar(int $userId, string $fileId, ?string $fileName = null, ?string $mimeType = null): bool {
        $stmt = $this->db->prepare('SELECT id FROM starred_files WHERE user_id = :user_id AND file_id = :file_id');
        $stmt->execute(['user_id' => $userId, 'file_id' => $fileId]);

        if ($stmt->fetch()) {
            $this->db->prepare('DELETE FROM starred_files WHERE user_id = :user_id AND file_id = :file_id')
                ->execute(['user_id' => $userId, 'file_id' => $fileId]);
            return false; // unstarred
        } else {
            $this->db->prepare('INSERT INTO starred_files (user_id, file_id, file_name, mime_type) VALUES (:user_id, :file_id, :file_name, :mime_type)')
                ->execute(['user_id' => $userId, 'file_id' => $fileId, 'file_name' => $fileName, 'mime_type' => $mimeType]);
            return true; // starred
        }
    }

    /**
     * Get starred file IDs for a user
     */
    public function getStarredFileIds(int $userId): array {
        $stmt = $this->db->prepare('SELECT file_id FROM starred_files WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return array_column($stmt->fetchAll(), 'file_id');
    }

    /**
     * Get starred files for a user
     */
    public function getStarredFiles(int $userId): array {
        $stmt = $this->db->prepare('SELECT * FROM starred_files WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
