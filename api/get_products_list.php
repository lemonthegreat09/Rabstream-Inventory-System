<?php
include '../db.php';
header('Content-Type: application/json');
$stmt = $conn->query("SELECT product_name, quantity FROM products ORDER BY product_name ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
