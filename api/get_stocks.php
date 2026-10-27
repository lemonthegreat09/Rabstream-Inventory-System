<?php
include '../db.php';

header('Content-Type: application/json');

try {
    // Include all columns from your table
    $stmt = $conn->query("SELECT product_id as id, product_name, quantity, added_date as created_at FROM stocks ORDER BY added_date DESC");
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($stocks);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>