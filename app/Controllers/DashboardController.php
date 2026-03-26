<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;

class DashboardController {
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Render the dashboard SPA shell
     */
    public function index(): void {
        $user = AuthMiddleware::getUser();
        $csrfToken = AuthMiddleware::generateCsrf();
        require __DIR__ . '/../../views/dashboard.php';
    }
}
