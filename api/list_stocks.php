<?php
include '../db.php';
$stmt = $conn->query("SELECT * FROM stocks ORDER BY id DESC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
