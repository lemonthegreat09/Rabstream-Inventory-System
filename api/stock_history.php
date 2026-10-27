<?php
include '../db.php';
$stmt = $conn->query("SELECT * FROM stock_history ORDER BY added_at DESC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
