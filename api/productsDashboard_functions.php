<?php

// =========================================================
//  productsDashboard_functions.php — Products Dashboard Functions
//  Contains all functions for product CRUD operations.
//  Used by productsDashboard_api.php
// =========================================================




// =========================================================
//  FUNCTION: get_all_products($conn)
//  Fetches all products from the database.
//  Joins with categories to return the category name.
// =========================================================
function get_all_products($conn) {

    // Join products with categories to get the readable category name
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock,
            p.status,
            p.image_url,
            p.collections,
            c.category_name,
            c.category_id
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.product_id DESC
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [];
    }

    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = [
            "product_id"    => (int) $row["product_id"],
            "product_name"  => $row["product_name"],
            "category_id"   => (int) $row["category_id"],
            "category_name" => $row["category_name"],
            "collections"   => $row["collections"],
            "price"         => number_format((float) $row["price"], 2),
            "stock"         => (int) $row["stock"],
            "status"        => $row["status"],
            "description"   => $row["description"],
            "image_url"     => $row["image_url"]
        ];
    }

    return $products;
}


// =========================================================
//  FUNCTION: add_product($conn, $data, $image_url)
//  Inserts a new product into the database.
//  $data = array of product fields from the request
//  $image_url = the saved image path (or null if no image)
// =========================================================
function add_product($conn, $data, $image_url) {

    // --- Validate required fields ---
    if (empty($data["product_name"]) || empty($data["price"])) {
        return [
            "success" => false,
            "message" => "Product name and price are required."
        ];
    }

    // --- Pull and sanitize each field ---
    $product_name = trim($data["product_name"]);
    $category_id  = !empty($data["category_id"]) ? (int) $data["category_id"] : null;
    $collections  = !empty($data["collections"]) ? trim($data["collections"]) : null;
    $price        = (float) $data["price"];
    $stock        = isset($data["stock"]) ? (int) $data["stock"] : 0;
    $description  = !empty($data["description"]) ? trim($data["description"]) : null;

    // --- Auto-set status based on stock quantity ---
    // This keeps the status consistent with the stock value
    if ($stock <= 0) {
        $status = "Out of Stock";
    } elseif ($stock <= 5) {
        $status = "Low Stock";
    } else {
        $status = "In Stock";
    }

    // --- Override with manual status if provided ---
    if (!empty($data["status"])) {
        $allowed = ["In Stock", "Low Stock", "Out of Stock"];
        if (in_array($data["status"], $allowed)) {
            $status = $data["status"];
        }
    }

    // --- Prepare the INSERT statement ---
    $stmt = mysqli_prepare($conn,
        "INSERT INTO products (category_id, collections, product_name, description, price, stock, status, image_url)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return [
            "success" => false,
            "message" => "Failed to prepare statement: " . mysqli_error($conn)
        ];
    }

    // --- Bind parameters (i=int, s=string, d=double/float) ---
    mysqli_stmt_bind_param($stmt, "isssdiss",
        $category_id,
        $collections,
        $product_name,
        $description,
        $price,
        $stock,
        $status,
        $image_url
    );

    $executed = mysqli_stmt_execute($stmt);

    if (!$executed) {
        return [
            "success" => false,
            "message" => "Failed to add product: " . mysqli_stmt_error($stmt)
        ];
    }

    // Get the ID of the newly inserted product
    $new_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    return [
        "success"    => true,
        "message"    => "Product added successfully.",
        "product_id" => $new_id
    ];
}


// =========================================================
//  FUNCTION: edit_product($conn, $product_id, $data, $image_url)
//  Updates an existing product in the database.
//  If no new image is uploaded, the old image_url is kept.
// =========================================================
function edit_product($conn, $product_id, $data, $image_url = null) {

    $product_id = (int) $product_id;

    if ($product_id <= 0) {
        return [
            "success" => false,
            "message" => "Invalid product ID."
        ];
    }

    // -----------------------------------------------------
    // Build dynamic update fields
    // -----------------------------------------------------
    $fields = [];
    $types  = "";
    $values = [];

    // product_name
    if (isset($data["product_name"])) {
        $fields[] = "product_name = ?";
        $types .= "s";
        $values[] = trim($data["product_name"]);
    }

    // category_id
    if (isset($data["category_id"])) {
        $fields[] = "category_id = ?";
        $types .= "i";
        $values[] = (int) $data["category_id"];
    }

    // collections
    if (isset($data["collections"])) {
        $fields[] = "collections = ?";
        $types .= "s";
        $values[] = trim($data["collections"]);
    }

    // price
    if (isset($data["price"])) {
        $fields[] = "price = ?";
        $types .= "d";
        $values[] = (float) $data["price"];
    }

    // stock
    if (isset($data["stock"])) {

        $stock = (int) $data["stock"];

        $fields[] = "stock = ?";
        $types .= "i";
        $values[] = $stock;

        // Auto-generate status if status wasn't sent
        if (!isset($data["status"])) {

            if ($stock <= 0) {
                $status = "Out of Stock";
            } elseif ($stock <= 5) {
                $status = "Low Stock";
            } else {
                $status = "In Stock";
            }

            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $status;
        }
    }

    // manual status
    if (isset($data["status"])) {

        $allowed = ["In Stock", "Low Stock", "Out of Stock"];

        if (!in_array($data["status"], $allowed)) {
            return [
                "success" => false,
                "message" => "Invalid status."
            ];
        }

        $fields[] = "status = ?";
        $types .= "s";
        $values[] = $data["status"];
    }

    // description
    if (isset($data["description"])) {
        $fields[] = "description = ?";
        $types .= "s";
        $values[] = trim($data["description"]);
    }

    // image
    if ($image_url !== null) {
        $fields[] = "image_url = ?";
        $types .= "s";
        $values[] = $image_url;
    }

    // Nothing to update
    if (empty($fields)) {
        return [
            "success" => false,
            "message" => "No fields provided for update."
        ];
    }

    // -----------------------------------------------------
    // Build SQL dynamically
    // -----------------------------------------------------
    $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE product_id = ?";

    $types .= "i";
    $values[] = $product_id;

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return [
            "success" => false,
            "message" => "Failed to prepare statement."
        ];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$values);

    $executed = mysqli_stmt_execute($stmt);

    if (!$executed) {
        return [
            "success" => false,
            "message" => "Failed to update product."
        ];
    }

    mysqli_stmt_close($stmt);

    return [
        "success" => true,
        "message" => "Product updated successfully.",
        "product_id" => $product_id
    ];
}
// =========================================================
//  FUNCTION: delete_product($conn, $product_id)
//  Deletes a product from the database by its ID.
//  Also returns the image path so the API can delete the file.
// =========================================================
function delete_product($conn, $product_id) {

    $product_id = (int) $product_id;

    if ($product_id <= 0) {
        return [
            "success" => false,
            "message" => "Invalid product ID."
        ];
    }

    // --- First, get the image_url so we can delete the file after ---
    $check = mysqli_prepare($conn, "SELECT image_url FROM products WHERE product_id = ?");
    mysqli_stmt_bind_param($check, "i", $product_id);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    $product = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check);

    if (!$product) {
        return [
            "success" => false,
            "message" => "Product not found."
        ];
    }

    $old_image = $product["image_url"];

    // --- Now delete the product row ---
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    $executed = mysqli_stmt_execute($stmt);

    if (!$executed) {
        return [
            "success" => false,
            "message" => "Failed to delete product: " . mysqli_stmt_error($stmt)
        ];
    }

    mysqli_stmt_close($stmt);

    // --- Delete the image file from the server if it exists ---
    if ($old_image && file_exists($old_image)) {
        unlink($old_image);
    }

    return [
        "success" => true,
        "message" => "Product deleted successfully.",
        "product_id" => $product_id
    ];
}


// =========================================================
//  FUNCTION: handle_image_upload($file)
//  Handles the uploaded product image.
//  $file = the entry from $_FILES (e.g. $_FILES["product_image"])
//  Returns the saved file path, or null if no file was uploaded.
// =========================================================
function handle_image_upload($file) {

    // If no file was sent or upload had an error, return null
    if (!isset($file) || $file["error"] !== UPLOAD_ERR_OK) {
        return null;
    }

    // --- Allowed image types ---
    $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    if (!in_array($file["type"], $allowed_types)) {
        return null; // Reject non-image files
    }

    // --- Max file size: 5MB ---
    if ($file["size"] > 5 * 1024 * 1024) {
        return null;
    }

    // --- Create the uploads folder if it doesn't exist ---
    $upload_dir = "uploads/products/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // --- Generate a unique file name to avoid overwriting existing files ---
    $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $new_filename = "product_" . time() . "_" . uniqid() . "." . $extension;
    $destination  = $upload_dir . $new_filename;

    // --- Move the file from the temp location to our uploads folder ---
    if (move_uploaded_file($file["tmp_name"], $destination)) {
        return $destination; // Return the path to save in the database
    }

    return null; // Upload failed
}


// =========================================================
//  FUNCTION: get_categories($conn)
//  Returns all available product categories.
//  Used to populate the category dropdown in the add/edit forms.
// =========================================================
function get_categories($conn) {

    $result = mysqli_query($conn, "SELECT category_id, category_name, display_name FROM categories ORDER BY category_id ASC");

    if (!$result) {
        return [];
    }

    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = [
            "category_id"   => (int) $row["category_id"],
            "category_name" => $row["category_name"],
            "display_name"  => $row["display_name"]
        ];
    }

    return $categories;
}
