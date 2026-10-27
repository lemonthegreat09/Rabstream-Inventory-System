<?php
include '../db.php';

header('Content-Type: application/json');

try {
    // Use the correct column name (id or product_id)
    $stmt = $conn->prepare("SELECT product_id as id, product_name, quantity, created_at FROM stocks ORDER BY product_id");
    $stmt->execute();
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($stocks);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>