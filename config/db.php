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

try {

    // --- Create PDO connection ---
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $conn = new PDO($dsn, DB_USER, DB_PASSWORD);

    // --- PDO settings ---
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    // --- Return JSON error ---
    header('Content-Type: application/json');
    http_response_code(500);

    die(json_encode([
        'error' => 'Database connection failed'
    ]));
}
?>
