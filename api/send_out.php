<?php
include '../db.php';

try {
    $product_name = $_POST['product_name'];
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $receiver = $_POST['receiver'] ?? null;
    $receipt_no = $_POST['receipt_no'] ?? null;
    $terms = $_POST['terms'] ?? null;

    // Check if product exists
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE product_name = ?");
    $stmt->execute([$product_name]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(["status"=>"error","message"=>"Product not found"]);
        exit;
    }

    if ($product['quantity'] < $quantity) {
        echo json_encode(["status"=>"error","message"=>"Not enough stock"]);
        exit;
    }

    // Deduct stock
    $newQty = $product['quantity'] - $quantity;
    $update = $conn->prepare("UPDATE products SET quantity = ?, created_at = NOW() WHERE product_name = ?");
    $update->execute([$newQty, $product_name]);

    // Insert outgoing record
    $insert = $conn->prepare("INSERT INTO outgoing_history (product_name, quantity, price, receiver, receipt_no, terms)
                              VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$product_name, $quantity, $price, $receiver, $receipt_no, $terms]);

    echo json_encode(["status"=>"success","message"=>"Stock sent successfully"]);
} catch (Exception $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
?>
