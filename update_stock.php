<?php
// api/update_stock.php
header('Content-Type: application/json');
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success'=>false,'message'=>'Invalid request']);
  exit;
}

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['product_name'] ?? '');
$qty = intval($_POST['quantity'] ?? -1);

if ($id <= 0 || $name === '' || $qty < 0) {
  echo json_encode(['success'=>false,'message'=>'Invalid data']);
  exit;
}

try {
  // Update the stock
  $stmt = $conn->prepare("UPDATE stocks SET product_name=?, quantity=?, last_updated=NOW() WHERE id=?");
  $stmt->bind_param("sii", $name, $qty, $id);
  if ($stmt->execute()) {

    // Log the edit into history
    $log = $conn->prepare("INSERT INTO stock_history (action, product_name, quantity, date_time) VALUES ('EDIT', ?, ?, NOW())");
    $log->bind_param("si", $name, $qty);
    $log->execute();

    echo json_encode(['success'=>true,'message'=>'Stock updated successfully']);
  } else {
    echo json_encode(['success'=>false,'message'=>'Failed to update stock']);
  }
} catch (Exception $e) {
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
