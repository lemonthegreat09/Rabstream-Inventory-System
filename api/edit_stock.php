<?php
include '../db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['id']);
    $quantity = intval($_POST['quantity']);

    try {
        // Get current product info
        $stmt = $conn->prepare("SELECT product_name FROM stocks WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            exit;
        }

        // Update stock - only update quantity, leave other fields unchanged
        $update_stmt = $conn->prepare("UPDATE stocks SET quantity = ? WHERE product_id = ?");
        $update_stmt->execute([$quantity, $product_id]);

        // Add to history
        $history_stmt = $conn->prepare("INSERT INTO stock_history (product_id, product_name, action, quantity) VALUES (?, ?, 'edited', ?)");
        $history_stmt->execute([$product_id, $product['product_name'], $quantity]);

        echo json_encode(['status' => 'success', 'message' => 'Stock updated successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update stock: ' . $e->getMessage()]);
    }
}
?>