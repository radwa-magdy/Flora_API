<?php
// ============================================================
// config/cors.php
// ============================================================

function set_cors_headers(): void {

    // Allow all origins (for development)
    header('Access-Control-Allow-Origin: *');

    // Allowed methods
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

    // Allowed headers
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Allow credentials (optional — remove if not using cookies)
    header('Access-Control-Allow-Credentials: true');

    // Handle preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
