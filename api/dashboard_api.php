<?php

// =========================================================
//  dashboard_api.php — Dashboard API
// =========================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once '../config/db.php';
require_once "dashboard_functions.php";


// =========================================================
//  GET → Return dashboard overview
//  Endpoint:
//  GET /dashboard_api.php
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $orders = get_recent_orders($conn);

    echo json_encode([
        "success" => true,
        "data" => [
            "recent_orders" => $orders
        ]
    ]);

    exit();
}


// =========================================================
//  PUT → Update order status
//  Endpoint:
//  PUT /dashboard_api.php?order_id=5
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "PUT") {

    // Get order_id from URL
    $order_id = isset($_GET["order_id"])
        ? (int) $_GET["order_id"]
        : 0;

    // Read JSON body
    $body = file_get_contents("php://input");
    $data = json_decode($body, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON body."
        ]);
        exit();
    }

    // Validate status
    if (empty($data["status"])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Status is required."
        ]);
        exit();
    }

    // Update order status
    $result = update_order_status(
        $conn,
        $order_id,
        $data["status"]
    );

    if (!$result["success"]) {
        http_response_code(400);
    }

    echo json_encode($result);

    exit();
}


// =========================================================
//  Invalid Method
// =========================================================
http_response_code(405);

echo json_encode([
    "success" => false,
    "message" => "Method not allowed."
]);
