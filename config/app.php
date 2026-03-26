<?php
/**
 * Application Configuration
 * Loads environment variables and returns config array
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Validate required env vars
$dotenv->required([
    'GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET', 
    'GOOGLE_REDIRECT_URI',
    'DB_HOST',
    'DB_DATABASE',
    'DB_USERNAME'
])->notEmpty();

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'MDrive',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost/MDrive',
        'key' => $_ENV['APP_KEY'] ?? '',
    ],
    'google' => [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
    ],
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],
    'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? '',
];
