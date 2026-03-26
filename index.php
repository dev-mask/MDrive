<?php
/**
 * MDrive - Front Controller / Router
 * All requests are routed through this file
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/app.php';

// Database (class loaded but NOT connected until first use)
require_once __DIR__ . '/config/database.php';

// Load application classes
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Services/TokenService.php';
require_once __DIR__ . '/app/Services/GoogleAuthService.php';
require_once __DIR__ . '/app/Services/GoogleDriveService.php';
require_once __DIR__ . '/app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/DashboardController.php';
require_once __DIR__ . '/app/Controllers/DriveController.php';

// Start session
session_start();

// Get the route
$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'];

// Lazy controller factory — only instantiate when needed
function getAuth(array $config) {
    static $c; return $c ??= new App\Controllers\AuthController($config);
}
function getDashboard(array $config) {
    static $c; return $c ??= new App\Controllers\DashboardController($config);
}
function getDrive(array $config) {
    static $c; return $c ??= new App\Controllers\DriveController($config);
}

// CSRF validation for non-GET requests to API endpoints
if ($method !== 'GET' && strpos($route, 'api/') === 0) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!App\Middleware\AuthMiddleware::validateCsrf($token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// Route matching
switch (true) {
    // ==================== AUTH ROUTES ====================
    case $route === '' || $route === 'login':
        getAuth($config)->login();
        break;

    case $route === 'auth/redirect':
        getAuth($config)->redirect();
        break;

    case $route === 'auth/callback':
        getAuth($config)->callback();
        break;

    case $route === 'auth/logout':
        getAuth($config)->logout();
        break;

    // ==================== DASHBOARD ====================
    case $route === 'dashboard':
        App\Middleware\AuthMiddleware::handle();
        getDashboard($config)->index();
        break;

    // ==================== API ROUTES (all require auth) ====================
    case $route === 'api/files' && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->listFiles();
        break;

    case $route === 'api/files' && $method === 'POST':
    case $route === 'api/upload' && $method === 'POST':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->uploadFile();
        break;

    case preg_match('#^api/files/([^/]+)/download$#', $route, $m) === 1 && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->downloadFile($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)/preview$#', $route, $m) === 1 && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->previewFile($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)/share$#', $route, $m) === 1 && $method === 'POST':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->shareFile($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)/star$#', $route, $m) === 1 && $method === 'POST':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->toggleStar($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)/restore$#', $route, $m) === 1 && $method === 'POST':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->restoreFile($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)/permanent$#', $route, $m) === 1 && $method === 'DELETE':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->permanentDelete($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)$#', $route, $m) === 1 && $method === 'PATCH':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->renameFile($m[1]);
        break;

    case preg_match('#^api/files/([^/]+)$#', $route, $m) === 1 && $method === 'DELETE':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->deleteFile($m[1]);
        break;

    case $route === 'api/folder' && $method === 'POST':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->createFolder();
        break;

    case $route === 'api/starred' && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->listStarred();
        break;

    case $route === 'api/trash' && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->listTrash();
        break;

    case $route === 'api/recent' && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->listRecent();
        break;

    case $route === 'api/activity' && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->getActivity();
        break;

    case $route === 'api/search' && $method === 'GET':
        App\Middleware\AuthMiddleware::handle(true);
        getDrive($config)->searchFiles();
        break;

    // ==================== 404 ====================
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}
