<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: index.php");
  exit;
}

include 'db.php';

// ✅ Use correct table names (stocks, outgoing, history)
$totalProducts = $conn->query("SELECT COUNT(*) FROM stocks")->fetchColumn() ?? 0;
$totalOutgoing = $conn->query("SELECT COUNT(*) FROM outgoing WHERE DATE(sent_date)=CURDATE()")->fetchColumn() ?? 0;
$lowStocks = $conn->query("SELECT COUNT(*) FROM stocks WHERE quantity <= 5")->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Inventory System</title>
<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Poppins", sans-serif;
  }

  body {
    display: flex;
    min-height: 100vh;
    background: #081b29;
    color: #fff;
    overflow-x: hidden;
  }

  /* SIDEBAR */
  .sidebar {
    width: 240px;
    background: #0f4c75;
    padding: 30px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: 0.3s;
  }

  .sidebar h2 {
    margin-bottom: 30px;
    font-size: 1.4rem;
    letter-spacing: 1px;
    color: #00adb5;
  }

  .nav {
    list-style: none;
    width: 100%;
  }

  .nav li { width: 100%; }

  .nav a {
    display: block;
    padding: 12px 30px;
    color: #fff;
    text-decoration: none;
    transition: 0.3s;
  }

  .nav a:hover, .nav a.active {
    background: #00adb5;
    color: #fff;
  }

  .logout {
    margin-top: auto;
    padding: 10px 30px;
    color: #ff6b6b;
    cursor: pointer;
  }

  .logout:hover { color: #fff; }

  /* MAIN CONTENT */
  .main {
    flex: 1;
    padding: 40px;
    background: linear-gradient(135deg, #081b29, #0f4c75);
    animation: gradientShift 10s ease infinite;
    background-size: 400% 400%;
  }

  @keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  h1 {
    font-size: 2rem;
    margin-bottom: 30px;
    color: #00adb5;
    text-shadow: 0 0 10px rgba(0,173,181,0.3);
  }

  /* DASHBOARD CARDS */
  .cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
  }

  .card {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    transition: 0.3s ease;
    box-shadow: 0 0 20px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    cursor: pointer;
  }

  .card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 0 25px rgba(0,173,181,0.5);
  }

  .card h3 {
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: #00adb5;
  }

  .card p {
    font-size: 2rem;
    font-weight: bold;
    color: #fff;
    transition: 0.3s;
  }

  /* HISTORY SECTION */
  .history-section {
    margin-top: 60px;
    background: rgba(255,255,255,0.05);
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 0 15px rgba(0,173,181,0.3);
    backdrop-filter: blur(8px);
    animation: fadeIn 0.8s ease;
  }

  .history-section h2 {
    text-align: center;
    color: #ffc107;
    margin-bottom: 20px;
    font-size: 1.6rem;
    text-shadow: 0 0 10px rgba(255,193,7,0.4);
  }

  .history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
  }

  .history-table th, .history-table td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.2);
  }

  .history-table th {
    background: rgba(255,193,7,0.2);
    color: #ffc107;
  }

  .history-table tr:hover {
    background: rgba(255,193,7,0.08);
    transform: scale(1.01);
  }

  .action.Added { color: #28a745; font-weight: bold; }
  .action.Edited { color: #17a2b8; font-weight: bold; }
  .action.Deleted { color: #dc3545; font-weight: bold; }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Responsive */
  @media(max-width: 768px) {
    .sidebar {
      position: fixed;
      width: 200px;
      height: 100%;
      z-index: 10;
      left: -200px;
    }
    .sidebar.active { left: 0; }
    .main { margin-left: 0; padding: 20px; }
    .toggle-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      background: #00adb5;
      color: #fff;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      z-index: 20;
    }
  }
</style>
</head>
<body>

  <div class="sidebar" id="sidebar">
    <h2>Rabstream Inc</h2>
    <ul class="nav">
      <li><a href="dashboard.php" class="active">🏠 Dashboard</a></li>
      <li><a href="stocks.php">📦 Current Stocks</a></li>
      <li><a href="outgoing_dashboard.php">🚚 Outgoing Stocks</a></li>
    </ul>
    <div class="logout" onclick="window.location='logout.php'">🔒 Logout</div>
  </div>

  <div class="main">
    <div class="toggle-btn" onclick="toggleSidebar()">☰</div>
    <h1>Dashboard Overview</h1>

    <div class="cards">
      <div class="card"><h3>Total Products</h3><p id="total-products"><?php echo $totalProducts; ?></p></div>
      <div class="card"><h3>Outgoing Today</h3><p id="total-outgoing"><?php echo $totalOutgoing; ?></p></div>
      <div class="card"><h3>Low Stocks</h3><p id="low-stocks"><?php echo $lowStocks; ?></p></div>
    </div>

    <!-- 📜 History Section -->
<div class="history-section">
  <h2>📜 Inventory History Log</h2>
  <table class="history-table">
    <thead>
      <tr>
        <th>Action</th>
        <th>Product</th>
        <th>Quantity</th>
        <th>Date & Time</th>
      </tr>
    </thead>
    <tbody>
      <?php
try {
    // FIX: Changed 'history' to 'stock_history' and 'date_time' to 'timestamp'
    $history = $conn->query("SELECT * FROM stock_history ORDER BY timestamp DESC LIMIT 10");

    if ($history->rowCount() > 0) {
        while ($row = $history->fetch(PDO::FETCH_ASSOC)) {
            // Add CSS class based on action type
            $action_class = strtolower($row['action']);
            echo "<tr>
                    <td class='action {$action_class}'>{$row['action']}</td>
                    <td>{$row['product_name']}</td>
                    <td>{$row['quantity']}</td>
                    <td>{$row['timestamp']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>⚠️ No history records found.</td></tr>";
    }

} catch (PDOException $e) {
    echo "<tr><td colspan='4'>⚠️ Unable to load history: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
    </tbody>
  </table>
</div>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
  }

  // Animate the numbers
  const counters = document.querySelectorAll('p');
  counters.forEach(counter => {
    const target = +counter.innerText;
    counter.innerText = '0';
    const update = () => {
      const current = +counter.innerText;
      const increment = target / 100;
      if(current < target) {
        counter.innerText = Math.ceil(current + increment);
        setTimeout(update, 10);
      } else {
        counter.innerText = target;
      }
    };
    update();
  });
</script>
</body>
</html>
