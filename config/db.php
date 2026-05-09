<?php
// ============================================================
// db.php — Database Connection
// ============================================================

// --- Database settings ---
define('DB_HOST', getenv('MYSQLHOST'));
define('DB_NAME', getenv('MYSQLDATABASE'));
define('DB_USER', getenv('MYSQLUSER'));
define('DB_PASSWORD', getenv('MYSQLPASSWORD'));
define('DB_PORT', getenv('MYSQLPORT') ?: 3306);

// --- Create MySQLi connection ---
$conn = mysqli_connect(
    DB_HOST,
    DB_USER,
    DB_PASSWORD,
    DB_NAME,
    DB_PORT
);

// --- Check connection ---
if (!$conn) {

    header('Content-Type: application/json');
    http_response_code(500);

    die(json_encode([
        'error' => 'Database connection failed'
    ]));
}

// --- Set charset ---
mysqli_set_charset($conn, 'utf8mb4');
?>
