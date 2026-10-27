<?php
include '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['id']);

    try {
        // Get product info before deleting - use product_id
        $stmt = $conn->prepare("SELECT product_name FROM stocks WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            exit;
        }

        // Delete from stocks - use product_id
        $delete_stmt = $conn->prepare("DELETE FROM stocks WHERE product_id = ?");
        $delete_stmt->execute([$product_id]);

        // Add to history
        $history_stmt = $conn->prepare("INSERT INTO stock_history (product_id, product_name, action, quantity) VALUES (?, ?, 'deleted', 0)");
        $history_stmt->execute([$product_id, $product['product_name']]);

        echo json_encode(['status' => 'success', 'message' => 'Stock deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete stock: ' . $e->getMessage()]);
    }
}
?>