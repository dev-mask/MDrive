<?php
/**
 * Database Connection Helper
 * Returns a PDO instance for MySQL
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/app.php';
            $db = $config['database'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $db['host'],
                $db['port'],
                $db['database']
            );

            try {
                self::$instance = new PDO($dsn, $db['username'], $db['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$instance;
    }

    /**
     * Initialize database tables if they don't exist
     */
    public static function migrate(): void {
        $pdo = self::getConnection();
        $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
        $pdo->exec($schema);
    }
}
