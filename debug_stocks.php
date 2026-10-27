<?php
include 'db.php';

echo "<h3>Stocks Table Structure:</h3>";
$columns = $conn->query("DESCRIBE stocks")->fetchAll(PDO::FETCH_ASSOC);
print_r($columns);

echo "<h3>Sample Data from Stocks:</h3>";
$data = $conn->query("SELECT * FROM stocks LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($data);
?>