<?php

require_once '../config/cors.php';
require_once '../config/db-db.php';
require_once '../config/response.php';

set_cors_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Only POST allowed", 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    send_error("Invalid JSON", 400);
}

$customerId = (int)($data['customer_id'] ?? 0);
$shipping   = $data['shipping'] ?? [];
$payment    = $data['payment'] ?? [];

if ($customerId <= 0) {
    send_error("customer_id required", 400);
}

$pdo = getPDO();

try {

    // GET CART 
    $stmt = $pdo->prepare("
        SELECT 
            c.quantity,
            p.product_id,
            p.price,
            p.product_name
            FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = :cid
        GROUP BY c.product_id, c.quantity, p.product_name, p.product_id
    ");

$stmt->execute(['cid' => $customerId]);
$cartItems = $stmt->fetchAll();

    // CALCULATE TOTALS
   
    $orderItems = [];

    foreach ($cartItems as $item) {
        $line = $item['price'] * $item['quantity'];
        $subtotal += $line;

        $orderItems[] = [
            "product_name" => $item['product_name'],
            "quantity" => (int)$item['quantity'],
            "price" => (float)$item['price'],
            "line_total" => round($line, 2)
        ];
    }

    $shippingFee = 30; 
    $tax = round($subtotal * 0.14, 2); 
    $total = round($subtotal + $shippingFee + $tax, 2);

    
    // TRANSACTION
    $pdo->beginTransaction();


    // INSERT ORDER
  
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (customer_id, order_date, shipped_date, status)
        VALUES (:cid, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Delivered')
    ");

    $stmt->execute(['cid' => $customerId]);
    $orderId = $pdo->lastInsertId();



    // INSERT ORDER ITEMS

    $stmt = $pdo->prepare("
     INSERT INTO order_items
        (
        order_id,
        product_id,
        quantity,
        discount,
        price,
        total_price
    )
    VALUES
    (
        :oid,
        :pid,
        :qty,
        0,
        :price,
        :total_price
    )
");

foreach ($cartItems as $item) {

    $totalPrice = $item['quantity'] * $item['price'];

    $stmt->execute([
        'oid'         => $orderId,
        'pid'         => $item['product_id'],
        'qty'         => $item['quantity'],
        'price'       => $item['price'],
        'total_price' => $totalPrice
    ]);
}
   
    // SHIPPING 

    $stmt = $pdo->prepare("
        INSERT INTO shipping_details
        (order_id, first_name, last_name, street_address, city, state, ZIP, shipping_method)
        VALUES (:oid, :fn, :ln, :addr, :city, :state, :zip, :method)
    ");

    $stmt->execute([
        'oid'    => $orderId,
        'fn'     => $shipping['first_name'] ?? '',
        'ln'     => $shipping['last_name'] ?? '',
        'addr'   => $shipping['street_address'] ?? '',
        'city'   => $shipping['city'] ?? '',
        'state'  => $shipping['state'] ?? '',
        'zip'    => $shipping['zip'] ?? '',
        'method' => $shipping['shipping_method'] ?? 'Standard Local Delivery'
    ]);

  
    // PAYMENT 

    if (!preg_match('/^\d{1,2}\/\d{2}$/', $payment['expiration_date'])) {
        throw new Exception("Use MM/YY format");
    }

    list($m, $y) = explode('/', $payment['expiration_date']);
    $m = str_pad($m, 2, '0', STR_PAD_LEFT);
    $y = "20" . $y;

    $exp = "$y-$m-01";

    $stmt = $pdo->prepare("
        INSERT INTO payments
        (customer_id, order_id, card_number, expiration_date, cvv, payment_status)
        VALUES (:cid, :oid, :card, :exp, :cvv, 'completed')
    ");

    $stmt->execute([
        'cid'  => $customerId,
        'oid'  => $orderId,
        'card' => $payment['card_number'],
        'exp'  => $exp,
        'cvv'  => $payment['cvv']
    ]);

    // CLEAR CART
    $pdo->prepare("DELETE FROM cart WHERE customer_id = :cid")
        ->execute(['cid' => $customerId]);

    $pdo->commit();

    // RESPONSE 
    send_json([
        "success" => true,
        "order_id" => (int)$orderId,
        "items" => $orderItems,
        "summary" => [
            "subtotal" => $subtotal,
            "shipping" => $shippingFee,
            "tax" => $tax,
            "total" => $total
        ]
    ], 201);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    send_error("Checkout failed: " . $e->getMessage(), 500);
}
