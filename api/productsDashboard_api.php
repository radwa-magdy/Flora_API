<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "productsDashboard_functions.php";
require_once '../config/db.php';

// =========================================================
// PARSE ROUTE
// =========================================================
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$parts = explode("/", trim($uri, "/"));

$route = end($parts);


// =========================================================
// GET REQUESTS
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    // -----------------------------------------------------
    // GET PRODUCTS
    // /productsDashboard_api.php/products
    // -----------------------------------------------------
    if ($route === "products") {

        $products = get_all_products($conn);

        echo json_encode([
            "success" => true,
            "count" => count($products),
            "data" => $products
        ]);

        exit();
    }


    // -----------------------------------------------------
    // GET CATEGORIES
    // /productsDashboard_api.php/categories
    // -----------------------------------------------------
    if ($route === "categories") {

        echo json_encode([
            "success" => true,
            "data" => get_categories($conn)
        ]);

        exit();
    }
}


// =========================================================
// POST REQUESTS
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -----------------------------------------------------
    // ADD PRODUCT
    // /productsDashboard_api.php/add
    // -----------------------------------------------------
    if ($route === "add") {

        $image_url = null;

        if (!empty($_FILES["product_image"])) {
            $image_url = handle_image_upload($_FILES["product_image"]);
        }

        $result = add_product($conn, $_POST, $image_url);

        if (!$result["success"]) {
            http_response_code(400);
        }

        echo json_encode($result);
        exit();
    }


    // -----------------------------------------------------
    // EDIT PRODUCT
    // /productsDashboard_api.php/edit?id=5
    // -----------------------------------------------------
    if ($route === "edit") {

        $product_id = isset($_GET["id"])
            ? (int) $_GET["id"]
            : 0;

        if ($product_id <= 0) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Invalid product ID."
            ]);

            exit();
        }

        $image_url = null;

        if (!empty($_FILES["product_image"])) {
            $image_url = handle_image_upload($_FILES["product_image"]);
        }

       $data = $_POST;

        // If JSON body was sent
        if (empty($data)) {

        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        if (!$data) {
        $data = [];
        }
}

$result = edit_product(
    $conn,
    $product_id,
    $data,
    $image_url
);
        if (!$result["success"]) {
            http_response_code(400);
        }

        echo json_encode($result);
        exit();
    }
}


// =========================================================
// DELETE REQUEST
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    // -----------------------------------------------------
    // DELETE PRODUCT
    // /productsDashboard_api.php/delete?id=5
    // -----------------------------------------------------
    if ($route === "delete") {

        $product_id = isset($_GET["id"])
            ? (int) $_GET["id"]
            : 0;

        $result = delete_product($conn, $product_id);

        if (!$result["success"]) {
            http_response_code(400);
        }

        echo json_encode($result);
        exit();
    }
}


// =========================================================
// INVALID ROUTE
// =========================================================
http_response_code(404);

echo json_encode([
    "success" => false,
    "message" => "Route not found."
]);
