<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Safely get form inputs
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    if ($product_name == '' || $quantity <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid product data"]);
        exit;
    }

    // Check if product exists
    $stmt = $conn->prepare("SELECT quantity FROM stocks WHERE product_name = ?");
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "Product not found"]);
        exit;
    }

    $row = $result->fetch_assoc();
    $current_qty = $row['quantity'];

    if ($current_qty < $quantity) {
        echo json_encode(["status" => "error", "message" => "Not enough stock"]);
        exit;
    }

    // Deduct stock
    $new_qty = $current_qty - $quantity;
    $update = $conn->prepare("UPDATE stocks SET quantity = ? WHERE product_name = ?");
    $update->bind_param("is", $new_qty, $product_name);
    $update->execute();

    // Insert into outgoing history
    $insert = $conn->prepare("INSERT INTO outgoing_history (product_name, quantity, price, date_sent) VALUES (?, ?, ?, NOW())");
    $insert->bind_param("sid", $product_name, $quantity, $price);
    $insert->execute();

    echo json_encode(["status" => "success", "message" => "Outgoing recorded successfully"]);
}
?>
