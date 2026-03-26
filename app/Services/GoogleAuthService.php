<?php
namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Oauth2;

class GoogleAuthService {
    private GoogleClient $client;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
        $this->client = new GoogleClient();
        $this->client->setClientId($config['google']['client_id']);
        $this->client->setClientSecret($config['google']['client_secret']);
        $this->client->setRedirectUri($config['google']['redirect_uri']);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setIncludeGrantedScopes(true);
        
        // Set scopes
        $this->client->addScope('email');
        $this->client->addScope('profile');
        $this->client->addScope('https://www.googleapis.com/auth/drive');
    }

    /**
     * Get the Google Client instance
     */
    public function getClient(): GoogleClient {
        return $this->client;
    }

    /**
     * Generate the auth URL for redirect
     */
    public function getAuthUrl(): string {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for tokens
     */
    public function exchangeCode(string $code): array {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \RuntimeException('Token exchange failed: ' . ($token['error_description'] ?? $token['error']));
        }

        $this->client->setAccessToken($token);
        return $token;
    }

    /**
     * Get user profile from Google
     */
    public function getUserProfile(): array {
        $oauth2 = new Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();

        return [
            'google_id' => $userInfo->getId(),
            'name' => $userInfo->getName(),
            'email' => $userInfo->getEmail(),
            'profile_picture' => $userInfo->getPicture(),
        ];
    }

    /**
     * Refresh an expired access token
     */
    public function refreshToken(string $refreshToken): array {
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $token = $this->client->getAccessToken();

        if (isset($token['error'])) {
            throw new \RuntimeException('Token refresh failed: ' . ($token['error_description'] ?? $token['error']));
        }

        return $token;
    }

    /**
     * Set access token on the client
     */
    public function setAccessToken(array $token): void {
        $this->client->setAccessToken($token);
    }

    /**
     * Check if the current token is expired
     */
    public function isTokenExpired(): bool {
        return $this->client->isAccessTokenExpired();
    }
}
