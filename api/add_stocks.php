<?php
include '../db.php';
header('Content-Type: application/json');

if (!isset($_POST['product_name'], $_POST['quantity'])) {
  echo json_encode(['status'=>'error','message'=>'Missing fields']);
  exit;
}

$name = trim($_POST['product_name']);
$qty = (int)$_POST['quantity'];

try {
  $stmt = $conn->prepare("INSERT INTO stocks (product_name, quantity) VALUES (?, ?)");
  $stmt->execute([$name, $qty]);
  $product_id = $conn->lastInsertId();

  // log to history
  $h = $conn->prepare("INSERT INTO history (product_id, action, quantity) VALUES (?, 'Added', ?)");
  $h->execute([$product_id, $qty]);

  echo json_encode(['status'=>'success','message'=>'Stock added successfully']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
