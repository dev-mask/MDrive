<?php
namespace App\Middleware;

class AuthMiddleware {
    /**
     * Check if user is authenticated
     * @param bool $isApi If true, returns JSON 401 instead of redirecting
     */
    public static function handle(bool $isApi = false): void {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user'])) {
            if ($isApi) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized', 'redirect' => '/MDrive/login']);
                exit;
            }
            header('Location: /MDrive/login');
            exit;
        }
    }

    /**
     * Generate a CSRF token
     */
    public static function generateCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token
     */
    public static function validateCsrf(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get authenticated user from session
     */
    public static function getUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get authenticated user ID
     */
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
}
