<?php
include 'db.php';

echo "<h2>Debug Information</h2>";

// Check stocks table
echo "<h3>Stocks Table:</h3>";
try {
    $stmt = $conn->query("SELECT * FROM stocks");
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($stocks, true) . "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Check stock_history table
echo "<h3>Stock History Table:</h3>";
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'stock_history'");
    if ($tableCheck->rowCount() > 0) {
        $stmt = $conn->query("SELECT * FROM stock_history");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($history, true) . "</pre>";
    } else {
        echo "stock_history table does not exist";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>