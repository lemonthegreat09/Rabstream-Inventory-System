<?php
include 'db.php';

echo "<h3>Stock History Table Contents:</h3>";
$result = $conn->query("SELECT * FROM stock_history ORDER BY timestamp DESC");
$history = $result->fetchAll(PDO::FETCH_ASSOC);

if (empty($history)) {
    echo "No records in stock_history table!<br>";
} else {
    echo "<table border='1' style='color:white;'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Product Name</th><th>Action</th><th>Quantity</th><th>Timestamp</th></tr>";
    foreach ($history as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['product_id']}</td>";
        echo "<td>{$row['product_name']}</td>";
        echo "<td>{$row['action']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>{$row['timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>