<?php
namespace App\Controllers;

use App\Services\GoogleAuthService;
use App\Services\TokenService;
use App\Models\User;
use Database;

class AuthController {
    private array $config;
    private ?GoogleAuthService $authService = null;
    private ?TokenService $tokenService = null;
    private ?User $userModel = null;

    public function __construct(array $config) {
        $this->config = $config;
    }

    private function getAuthService(): GoogleAuthService {
        if (!$this->authService) $this->authService = new GoogleAuthService($this->config);
        return $this->authService;
    }

    private function getTokenService(): TokenService {
        if (!$this->tokenService) $this->tokenService = new TokenService();
        return $this->tokenService;
    }

    private function getUserModel(): User {
        if (!$this->userModel) $this->userModel = new User();
        return $this->userModel;
    }

    /**
     * Show login page
     */
    public function login(): void {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: /MDrive/dashboard');
            exit;
        }
        
        require __DIR__ . '/../../views/login.php';
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirect(): void {
        $authUrl = $this->getAuthService()->getAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(): void {
        if (isset($_GET['error'])) {
            header('Location: /MDrive/login?error=' . urlencode($_GET['error']));
            exit;
        }

        if (!isset($_GET['code'])) {
            header('Location: /MDrive/login?error=no_code');
            exit;
        }

        try {
            // Initialize database tables if needed
            Database::migrate();

            // Exchange code for tokens
            $token = $this->getAuthService()->exchangeCode($_GET['code']);
            
            // Get user profile
            $profile = $this->getAuthService()->getUserProfile();

            // Calculate token expiry
            $expiresAt = null;
            if (isset($token['expires_in'])) {
                $expiresAt = date('Y-m-d H:i:s', time() + $token['expires_in']);
            }

            // Encrypt tokens
            $encryptedAccessToken = $this->getTokenService()->encryptTokenData($token);
            $encryptedRefreshToken = isset($token['refresh_token']) 
                ? $this->getTokenService()->encrypt($token['refresh_token']) 
                : null;

            // Create or update user
            $userId = $this->getUserModel()->upsert([
                'google_id' => $profile['google_id'],
                'name' => $profile['name'],
                'email' => $profile['email'],
                'profile_picture' => $profile['profile_picture'],
                'access_token' => $encryptedAccessToken,
                'refresh_token' => $encryptedRefreshToken,
                'token_expires_at' => $expiresAt,
            ]);

            // Set session
            $_SESSION['user_id'] = $userId;
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $profile['name'],
                'email' => $profile['email'],
                'profile_picture' => $profile['profile_picture'],
            ];
            $_SESSION['token_data'] = $token;

            // Log activity
            $this->getUserModel()->logActivity($userId, 'login', null, null, 'User logged in');

            header('Location: /MDrive/dashboard');
            exit;

        } catch (\Exception $e) {
            error_log('MDrive Auth Error: ' . $e->getMessage());
            header('Location: /MDrive/login?error=' . urlencode('Authentication failed. Please try again.'));
            exit;
        }
    }

    /**
     * Logout
     */
    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            try {
                $this->getUserModel()->logActivity($_SESSION['user_id'], 'logout', null, null, 'User logged out');
            } catch (\Exception $e) {
                // DB might not be available, that's ok for logout
            }
        }
        
        session_destroy();
        header('Location: /MDrive/login');
        exit;
    }
}
