<?php
require_once '../config/cors.php';
require_once '../config/db-db.php';
require_once '../config/response.php';

set_cors_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed. Use GET.', 405);
}

try {
    $pdo = getPDO();

    // Filters
    $search     = $_GET['search'] ?? '';
    $category   = $_GET['category'] ?? '';
    $collection = $_GET['collection'] ?? '';

    // Query
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.collections,
            p.image_url,
            p.description,
            c.category_name,
            p.price 
        FROM products p
        INNER JOIN categories c 
            ON p.category_id = c.category_id
        LEFT JOIN product_sizes ps 
            ON p.product_id = ps.product_id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND p.product_name LIKE :search ";
        $params[':search'] = "%$search%";
    }

    if (!empty($category)) {
        $sql .= " AND c.category_name = :category ";
        $params[':category'] = $category;
    }

    if (!empty($collection)) {
        $sql .= " AND p.collections = :collection ";
        $params[':collection'] = $collection;
    }

    $sql .= "
        GROUP BY p.product_id
        ORDER BY p.product_name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response (flat)
    $result = [];

    foreach ($products as $p) {
        $result[] = [
            'product_id'    => (int)$p['product_id'],
            'product_name'  => $p['product_name'],
            'description'   => $p['description'],
            'category_name' => $p['category_name'],
            'collection'    => $p['collections'],
            'price'         => (float)$p['price'],
            'image_url'     => $p['image_url'] ?: '/images/default.jpg'
        ];
    }

    send_json([
        'success' => true,
        'data' => $result
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    send_error('Database error', 500);
} catch (Exception $e) {
    send_error('Server error', 500);
}