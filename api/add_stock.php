<?php
include '../db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $quantity = intval($_POST['quantity']);

    try {
        // Check if product already exists
        $check_stmt = $conn->prepare("SELECT product_id FROM stocks WHERE product_name = ?");
        $check_stmt->execute([$product_name]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo json_encode(['status' => 'error', 'message' => 'Product already exists']);
            exit;
        }

        // Insert new stock - include default values for serial_number and price
        $insert_stmt = $conn->prepare("INSERT INTO stocks (product_name, serial_number, quantity, price) VALUES (?, '', ?, 0.00)");
        $insert_stmt->execute([$product_name, $quantity]);
        $product_id = $conn->lastInsertId();

        // Add to history
        $history_stmt = $conn->prepare("INSERT INTO stock_history (product_id, product_name, action, quantity) VALUES (?, ?, 'added', ?)");
        $history_stmt->execute([$product_id, $product_name, $quantity]);

        echo json_encode(['status' => 'success', 'message' => 'Stock added successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add stock: ' . $e->getMessage()]);
    }
}
?>