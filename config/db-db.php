<?php

    define('DB_HOST', getenv('MYSQLHOST'));
    define('DB_NAME', getenv('MYSQLDATABASE'));
    define('DB_USER', getenv('MYSQLUSER'));
    define('DB_PASS', getenv('MYSQLPASSWORD'));
    define('DB_PORT', getenv('MYSQLPORT') ?: 3306);
    define('DB_CHARSET', 'utf8mb4');

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $dsn = "mysql:host=" . DB_HOST .
               ";port=" . DB_PORT .
               ";dbname=" . DB_NAME .
               ";charset=" . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {

            error_log('DB ERROR: ' . $e->getMessage());

            http_response_code(500);
            header('Content-Type: application/json');

            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection failed.',
                // helpful ONLY for local debugging
                'debug' => $e->getMessage()
            ]);

            exit;
        }
    }

    return $pdo;
}