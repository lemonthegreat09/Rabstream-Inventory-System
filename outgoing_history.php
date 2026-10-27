<?php
session_start();
include 'db.php';
if(!isset($_SESSION['admin'])){
    header("Location: index.php");
    exit;
}

// Get all products for filter
$products = $conn->query("SELECT DISTINCT product_name FROM outgoing_history");
$filter = $_GET['filter'] ?? '';
$query = "SELECT * FROM outgoing_history";
if ($filter) $query .= " WHERE product_name = " . $conn->quote($filter);
$query .= " ORDER BY timestamp DESC";
$history = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
  <title>📜 Outgoing History</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <h2>📜 Outgoing History</h2>

  <form method="get">
    <label>Filter by product:</label>
    <select name="filter" onchange="this.form.submit()">
      <option value="">-- All Products --</option>
      <?php while($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
        <option value="<?= htmlspecialchars($row['product_name']) ?>" <?= $filter==$row['product_name']?'selected':'' ?>>
          <?= htmlspecialchars($row['product_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>
  </form>

  <table class="history-table">
    <thead>
      <tr>
        <th>Product</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Receiver</th>
        <th>Receipt No</th>
        <th>Terms</th>
        <th>Date & Time</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $history->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
          <td><?= htmlspecialchars($row['product_name']) ?></td>
          <td><?= htmlspecialchars($row['quantity']) ?></td>
          <td><?= htmlspecialchars($row['price']) ?></td>
          <td><?= htmlspecialchars($row['receiver']) ?></td>
          <td><?= htmlspecialchars($row['receipt_no']) ?></td>
          <td><?= htmlspecialchars($row['terms']) ?></td>
          <td><?= htmlspecialchars($row['timestamp']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
