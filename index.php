<?php
session_start();
include 'db.php';

// 🧠 AUTO-CREATE DEFAULT ADMIN ACCOUNT IF MISSING
try {
  $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE email = 'admin'");
  $exists = $stmt->fetchColumn();

  if ($exists == 0) {
    $hash = password_hash("admin123", PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (email, password) VALUES ('admin', ?)");
    $insert->execute([$hash]);
  }
} catch (Exception $e) {
  die("Error checking admin account: " . $e->getMessage());
}

// 🧾 LOGIN PROCESS
if (isset($_SESSION['admin'])) {
  header("Location: dashboard.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory System Login</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Poppins", sans-serif;
  }

  body {
    background: linear-gradient(135deg, #081b29, #0f4c75, #00adb5);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
    animation: gradientShift 10s ease infinite;
    background-size: 400% 400%;
  }

  @keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  .login-container {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 40px;
    width: 340px;
    text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.2);
    animation: fadeIn 1s ease;
  }

  @keyframes fadeIn {
    from {opacity: 0; transform: scale(0.9);}
    to {opacity: 1; transform: scale(1);}
  }

  h2 {
    margin-bottom: 25px;
    font-size: 1.8rem;
    color: #fff;
  }

  input {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0;
    border-radius: 8px;
    border: none;
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-size: 15px;
    outline: none;
    transition: background 0.3s ease;
  }

  input:focus {
    background: rgba(255,255,255,0.35);
  }

  button {
    width: 100%;
    padding: 12px;
    background: #00adb5;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    margin-top: 10px;
    cursor: pointer;
    transition: 0.3s;
  }

  button:hover {
    background: #04c6d2;
    transform: translateY(-2px);
  }

  .footer {
    margin-top: 20px;
    font-size: 13px;
    color: #ccc;
  }

  .alert {
    background: rgba(255, 0, 0, 0.3);
    border-left: 4px solid red;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    color: #fff;
  }
</style>
</head>
<body>
  <div class="login-container">
    <h2>Rabsteam Inventory Login</h2>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $email = trim($_POST['email']);
      $password = $_POST['password'];

      $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin'] = $user['email'];
        header("Location: dashboard.php");
        exit;
      } else {
        echo "<div class='alert'>Invalid email or password</div>";
      }
    }
    ?>

    <form method="POST">
      <input type="text" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <div class="footer">© 2025 Rabstream Inc Inventory</div>
  </div>
</body>
</html>
