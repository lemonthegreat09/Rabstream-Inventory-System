<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $product_name = $_POST['product_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($id > 0 && !empty($product_name)) {
        $stmt = $conn->prepare("UPDATE stocks SET product_name = ?, quantity = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$product_name, $quantity, $id])) {

            // Log edit to history
            $log = $conn->prepare("INSERT INTO history (action, product_name, quantity) VALUES ('Edited', ?, ?)");
            $log->execute([$product_name, $quantity]);

            echo "Stock updated successfully";
        } else {
            echo "Error updating stock";
        }
    } else {
        echo "Invalid input";
    }
}
?>
