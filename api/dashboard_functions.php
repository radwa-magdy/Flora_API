<?php

// =========================================================
//  dashboard_functions.php — Overview Dashboard Functions
//  Contains all the helper functions used by dashboard_api.php
//  Each function does one specific job (fetch orders, update status, etc.)
// =========================================================

require_once '../config/db.php';


// =========================================================
//  FUNCTION: get_recent_orders()
//  Fetches all orders from the database, most recent first.
//  Joins with the customers table to get the customer's full name.
//  Also calculates the total amount for each order from order_items.
// =========================================================
function get_recent_orders($conn) {

    // SQL query:
    // - SELECT order details + customer name
    // - SUM the total_price from order_items to get the order total
    // - LEFT JOIN so orders without items still appear (total will just be 0)
    // - Group by order so we get one row per order
    // - Order by newest first
    $sql = "
        SELECT
            o.order_id,
            o.order_date,
            o.status,
            CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
            COALESCE(SUM(oi.total_price), 0) AS order_total
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_id, o.order_date, o.status, c.first_name, c.last_name
        ORDER BY o.order_date DESC, o.order_id DESC
    ";

    // Run the query
    $result = mysqli_query($conn, $sql);

    // If the query failed, return an empty array
    if (!$result) {
        return [];
    }

    // Loop through each row and collect into an array
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = [
            "order_id"      => "#ORD-" . str_pad($row["order_id"], 3, "0", STR_PAD_LEFT), // Format: #ORD-092
            "raw_order_id"  => (int) $row["order_id"],   // Raw number for updates
            "order_date"    => date("M d, Y", strtotime($row["order_date"])), // Format: Oct 24, 2023
            "customer_name" => $row["customer_name"],
            "status"        => $row["status"],
            "order_total"   => number_format((float) $row["order_total"], 2) // Format: 145.00
        ];
    }

    return $orders;
}


// =========================================================
//  FUNCTION: update_order_status($conn, $order_id, $new_status)
//  Updates the status of a single order in the database.
//  Only accepts the 3 valid status values shown in the UI.
// =========================================================
function update_order_status($conn, $order_id, $new_status) {

    // --- Validate: only allow the 3 statuses that exist in the ENUM ---
    $allowed_statuses = ["Processing", "Out for Delivery", "Delivered"];

    if (!in_array($new_status, $allowed_statuses)) {
        return [
            "success" => false,
            "message" => "Invalid status. Allowed values: Processing, Out for Delivery, Delivered"
        ];
    }

    // --- Validate: order_id must be a positive number ---
    $order_id = (int) $order_id;
    if ($order_id <= 0) {
        return [
            "success" => false,
            "message" => "Invalid order ID."
        ];
    }

    // --- Prepare a safe SQL statement to prevent SQL injection ---
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE order_id = ?");

    if (!$stmt) {
        return [
            "success" => false,
            "message" => "Failed to prepare statement: " . mysqli_error($conn)
        ];
    }

    // --- Bind the values to the placeholders (s = string, i = integer) ---
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);

    // --- Execute the update ---
    $executed = mysqli_stmt_execute($stmt);

    if (!$executed) {
        return [
            "success" => false,
            "message" => "Failed to update order status."
        ];
    }

    // --- Check if any row was actually changed ---
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected === 0) {
        return [
            "success" => false,
            "message" => "Order not found or status is already the same."
        ];
    }

    return [
        "success" => true,
        "message" => "Order status updated successfully.",
        "order_id" => $order_id,
        "new_status" => $new_status
    ];
}