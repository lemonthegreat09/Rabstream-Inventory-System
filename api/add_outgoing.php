<?php
include '../db.php';
header('Content-Type: application/json');

$product = trim($_POST['product_name'] ?? '');
$qty = intval($_POST['quantity'] ?? 0);
$receiver = trim($_POST['receiver'] ?? '');
$os = trim($_POST['os_number'] ?? '');
$terms = trim($_POST['terms'] ?? '');

if($product=='' || $qty <= 0){ echo json_encode(['status'=>'error','message'=>'Invalid input']); exit; }

// check product
$stmt = $conn->prepare("SELECT id, quantity FROM products WHERE product_name = ?");
stmt->execute([$product]);
$productRow = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$productRow){ echo json_encode(['status'=>'error','message'=>'Product not found']); exit; }
if($productRow['quantity'] < $qty){ echo json_encode(['status'=>'error','message'=>'Insufficient stock']); exit; }

// deduct
$update = $conn->prepare("UPDATE products SET quantity = quantity - ?, created_at = NOW() WHERE id = ?");
$update->execute([$qty, $productRow['id']]);

// insert outgoing
$insert = $conn->prepare("INSERT INTO outgoing (product_name, quantity, receiver, os_number, terms, date_out) VALUES (?, ?, ?, ?, ?, NOW())");
$insert->execute([$product, $qty, $receiver, $os, $terms]);

// log history
$log = $conn->prepare("INSERT INTO history (action, product_name, quantity, timestamp) VALUES ('Outgoing', ?, ? , NOW())");
$log->execute([$product, $qty]);

echo json_encode(['status'=>'success','message'=>'Outgoing recorded']);
?>
