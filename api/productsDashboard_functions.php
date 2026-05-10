<?php

// =========================================================
//  productsDashboard_functions.php — Products Dashboard Functions
// =========================================================


// =========================================================
// GET ALL PRODUCTS
// =========================================================
function get_all_products($conn) {

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

    if (!$result) return [];

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
// ADD PRODUCT 
// =========================================================
function add_product($conn, $data) {

    if (empty($data["product_name"]) || empty($data["price"])) {
        return [
            "success" => false,
            "message" => "Product name and price are required."
        ];
    }

    $product_name = trim($data["product_name"]);
    $category_id  = !empty($data["category_id"]) ? (int)$data["category_id"] : null;
    $collections  = !empty($data["collections"]) ? trim($data["collections"]) : null;
    $price        = (float)$data["price"];
    $stock        = isset($data["stock"]) ? (int)$data["stock"] : 0;
    $description  = !empty($data["description"]) ? trim($data["description"]) : null;

    $image_url    = !empty($data["image_url"]) ? trim($data["image_url"]) : null;

    // status auto
    if ($stock <= 0) {
        $status = "Out of Stock";
    } elseif ($stock <= 5) {
        $status = "Low Stock";
    } else {
        $status = "In Stock";
    }

    if (!empty($data["status"])) {
        $allowed = ["In Stock", "Low Stock", "Out of Stock"];
        if (in_array($data["status"], $allowed)) {
            $status = $data["status"];
        }
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO products
        (category_id, collections, product_name, description, price, stock, status, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return ["success" => false, "message" => "DB error"];
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssdiss",
        $category_id,
        $collections,
        $product_name,
        $description,
        $price,
        $stock,
        $status,
        $image_url
    );

    if (!mysqli_stmt_execute($stmt)) {
        return ["success" => false, "message" => "Insert failed"];
    }

    $new_id = mysqli_insert_id($conn);
    // =========================================================
// INSERT INTO INVENTORY TABLE
// =========================================================

$unit = !empty($data["unit"])
    ? trim($data["unit"])
    : "pcs";

$inventory_stmt = mysqli_prepare(
    $conn,
    "INSERT INTO inventory (product_id, quantity, unit)
     VALUES (?, ?, ?)"
);

mysqli_stmt_bind_param(
    $inventory_stmt,
    "iis",
    $new_id,
    $stock,
    $unit
);

mysqli_stmt_execute($inventory_stmt);
mysqli_stmt_close($inventory_stmt);
    mysqli_stmt_close($stmt);

    return [
        "success"    => true,
        "message"    => "Product added successfully.",
        "product_id" => $new_id,
        "image_url"  => $image_url
    ];
}


// =========================================================
// EDIT PRODUCT 
// =========================================================
function edit_product($conn, $product_id, $data) {

    $product_id = (int)$product_id;

    if ($product_id <= 0) {
        return ["success" => false, "message" => "Invalid product ID"];
    }

    $fields = [];
    $types  = "";
    $values = [];

    if (isset($data["product_name"])) {
        $fields[] = "product_name = ?";
        $types .= "s";
        $values[] = trim($data["product_name"]);
    }

    if (isset($data["category_id"])) {
        $fields[] = "category_id = ?";
        $types .= "i";
        $values[] = (int)$data["category_id"];
    }

    if (isset($data["collections"])) {
        $fields[] = "collections = ?";
        $types .= "s";
        $values[] = trim($data["collections"]);
    }

    if (isset($data["price"])) {
        $fields[] = "price = ?";
        $types .= "d";
        $values[] = (float)$data["price"];
    }

    if (isset($data["stock"])) {
        $stock = (int)$data["stock"];

        $fields[] = "stock = ?";
        $types .= "i";
        $values[] = $stock;

        if (!isset($data["status"])) {
            if ($stock <= 0) $status = "Out of Stock";
            elseif ($stock <= 5) $status = "Low Stock";
            else $status = "In Stock";

            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $status;
        }
    }

    if (isset($data["status"])) {
        $allowed = ["In Stock", "Low Stock", "Out of Stock"];

        if (!in_array($data["status"], $allowed)) {
            return ["success" => false, "message" => "Invalid status"];
        }

        $fields[] = "status = ?";
        $types .= "s";
        $values[] = $data["status"];
    }

    if (isset($data["description"])) {
        $fields[] = "description = ?";
        $types .= "s";
        $values[] = trim($data["description"]);
    }

    // IMAGE URL ONLY (NO FILE DELETE ANYMORE)
    if (isset($data["image_url"])) {
        $fields[] = "image_url = ?";
        $types .= "s";
        $values[] = trim($data["image_url"]);
    }

    if (empty($fields)) {
        return ["success" => false, "message" => "No data to update"];
    }

    $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE product_id = ?";

    $types .= "i";
    $values[] = $product_id;

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return ["success" => false, "message" => "DB error"];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$values);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // =========================================================
// UPDATE INVENTORY TABLE
// =========================================================

if (isset($data["stock"])) {

    $unit = !empty($data["unit"])
        ? trim($data["unit"])
        : "pcs";

    // Check if inventory row exists
    $check_stmt = mysqli_prepare(
        $conn,
        "SELECT inventory_id FROM inventory WHERE product_id = ?"
    );

    mysqli_stmt_bind_param($check_stmt, "i", $product_id);
    mysqli_stmt_execute($check_stmt);

    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {

        // UPDATE inventory
        $inventory_stmt = mysqli_prepare(
            $conn,
            "UPDATE inventory
             SET quantity = ?, unit = ?
             WHERE product_id = ?"
        );

        mysqli_stmt_bind_param(
            $inventory_stmt,
            "isi",
            $stock,
            $unit,
            $product_id
        );

    } else {

        // INSERT inventory if missing
        $inventory_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO inventory (product_id, quantity, unit)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $inventory_stmt,
            "iis",
            $product_id,
            $stock,
            $unit
        );
    }

    mysqli_stmt_execute($inventory_stmt);

    mysqli_stmt_close($inventory_stmt);
    mysqli_stmt_close($check_stmt);
}
    return [
        "success"    => true,
        "message"    => "Product updated successfully",
        "product_id" => $product_id,
        "image_url"  => $data["image_url"] ?? null
    ];
}


// =========================================================
// DELETE PRODUCT 
// =========================================================
function delete_product($conn, $product_id) {

    $product_id = (int)$product_id;

    if ($product_id <= 0) {
        return ["success" => false, "message" => "Invalid ID"];
    }

    // delete inventory first
    $inv_stmt = mysqli_prepare(
        $conn,
        "DELETE FROM inventory WHERE product_id = ?"
    );
    mysqli_stmt_bind_param($inv_stmt, "i", $product_id);
    mysqli_stmt_execute($inv_stmt);
    mysqli_stmt_close($inv_stmt);

    // delete product
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        "success" => true,
        "message" => "Product deleted successfully"
    ];
}


// =========================================================
// GET CATEGORIES
// =========================================================
function get_categories($conn) {

    $result = mysqli_query($conn, "SELECT category_id, category_name, display_name FROM categories");

    if (!$result) return [];

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}
