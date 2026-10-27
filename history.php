<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: index.php");
  exit;
}
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Stock History</title>
<style>
  body{font-family:Poppins,sans-serif;background:#0f2027;color:#fff;margin:0;padding:20px;}
  table{width:100%;border-collapse:collapse;margin-top:20px;background:rgba(255,255,255,0.06);border-radius:10px;overflow:hidden;}
  th,td{padding:12px 14px;text-align:left;}
  th{background:rgba(0,0,0,0.3);color:#00adb5;}
  tr:nth-child(even){background:rgba(255,255,255,0.04);}
  h1{color:#00adb5;}
</style>
</head>
<body>
<h1>📜 Stock History</h1>
<table>
<thead>
<tr>
  <th>#</th>
  <th>Action</th>
  <th>Product Name</th>
  <th>Quantity</th>
  <th>Date & Time</th>
</tr>
</thead>
<tbody>
<?php
// FIX: Changed date_time to timestamp
$result = $conn->query("SELECT * FROM stock_history ORDER BY timestamp DESC");
if ($result && $result->rowCount() > 0) {
  $i = 1;
  while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
      <td>{$i}</td>
      <td>{$row['action']}</td>
      <td>{$row['product_name']}</td>
      <td>{$row['quantity']}</td>
      <td>{$row['timestamp']}</td>
    </tr>";
    $i++;
  }
} else {
  echo "<tr><td colspan='5'>No history found</td></tr>";
}
?>
</tbody>
</table>
</body>
</html>