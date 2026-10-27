<?php
include '../db.php';

if (isset($_POST['id'], $_POST['quantity'])) {
    $id = (int)$_POST['id'];
    $qty = (int)$_POST['quantity'];

    $stmt = $conn->prepare("UPDATE stocks SET quantity = ? WHERE product_id = ?");
    $stmt->execute([$qty, $id]);

    // Log edit in history
    $p = $conn->prepare("SELECT product_name FROM stocks WHERE product_id = ?");
    $p->execute([$id]);
    $product = $p->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $hist = $conn->prepare("INSERT INTO history (action, product_name, quantity) VALUES ('Edited', ?, ?)");
        $hist->execute([$product['product_name'], $qty]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Stock updated successfully']);
}
?>
