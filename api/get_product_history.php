<?php
include '../db.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    try {
        // First, check if stock_history table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'stock_history'");
        if ($tableCheck->rowCount() == 0) {
            // Return empty array if table doesn't exist
            echo json_encode([]);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT product_name, action, quantity, timestamp FROM stock_history WHERE product_id = ? ORDER BY timestamp DESC");
        $stmt->execute([$product_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($history);
    } catch (PDOException $e) {
        // Return empty array on error instead of error message
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>