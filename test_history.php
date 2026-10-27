<?php
include 'db.php';

// Test if we can add and retrieve history
echo "<h3>Testing History System:</h3>";

// Add a test history record
$test_stmt = $conn->prepare("INSERT INTO stock_history (product_id, product_name, action, quantity) VALUES (?, ?, 'test', ?)");
$test_stmt->execute([999, 'TEST PRODUCT', 100]);

echo "Test history record added.<br>";

// Retrieve recent history
$history = $conn->query("SELECT * FROM stock_history ORDER BY timestamp DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Recent History:</h3>";
print_r($history);
?>