<?php
session_start(); // ADD THIS AT THE TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $dentist_name = $_POST['dentist_name'];
    $price = $_POST['price'];
    $os_number = $_POST['os_number'];
    $terms_of_payment = $_POST['terms_of_payment'];
    $serial_number = $_POST['serial_number'];

    try {
        // Get product details from stocks
        $stmt = $conn->prepare("SELECT product_name, serial_number, quantity FROM stocks WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['error'] = "Product not found!";
        } elseif ($product['quantity'] < $quantity) {
            $_SESSION['error'] = "Insufficient stock! Available: " . $product['quantity'];
        } else {
            // Insert outgoing record
            $insert = $conn->prepare("INSERT INTO outgoing 
                (product_name, serial_number, dentist_name, price, os_number, terms_of_payment, quantity, sent_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $insert->execute([$product['product_name'], $serial_number, $dentist_name, $price, $os_number, $terms_of_payment, $quantity]);

            // Update stock quantity
            $update = $conn->prepare("UPDATE stocks SET quantity = quantity - ? WHERE product_id = ?");
            $update->execute([$quantity, $product_id]);

            // Add to stock_history table
            $history = $conn->prepare("INSERT INTO stock_history (product_id, product_name, action, quantity) VALUES (?, ?, 'outgoing', ?)");
            $history->execute([$product_id, $product['product_name'], $quantity]);

            $_SESSION['success'] = "Product successfully sent out!";
        }
        
        // Redirect to clear POST data and prevent form resubmission
        header("Location: outgoing_dashboard.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: outgoing_dashboard.php");
        exit;
    }
}

// Fetch all products from current stock with all details
$products = $conn->query("SELECT * FROM stocks WHERE quantity > 0 ORDER BY product_name ASC");

// Get filtering and sorting parameters
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'sent_date';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : '';

// Validate sort parameters
$allowed_sort_columns = ['id', 'product_name', 'dentist_name', 'price', 'os_number', 'terms_of_payment', 'quantity', 'sent_date'];
$allowed_sort_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'sent_date';
}
if (!in_array($sort_order, $allowed_sort_orders)) {
    $sort_order = 'DESC';
}

// Build query for outgoing history
$query = "SELECT * FROM outgoing WHERE 1=1";
$params = [];

if ($filter) {
    $query .= " AND product_name = :filter";
    $params[':filter'] = $filter;
}

if ($filter_month) {
    $query .= " AND MONTH(sent_date) = :month";
    $params[':month'] = $filter_month;
}

if ($filter_year) {
    $query .= " AND YEAR(sent_date) = :year";
    $params[':year'] = $filter_year;
}

$query .= " ORDER BY $sort_by $sort_order";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$outgoing = $stmt;

// Calculate total quantity
$total_quantity = 0;
$total_rows = 0;
if ($outgoing && $outgoing->rowCount() > 0) {
    // Reset pointer and calculate totals
    $stmt->execute();
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_quantity += $row['quantity'];
        $total_rows++;
    }
    // Reset pointer again for display
    $stmt->execute();
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}

// Get unique years for year filter
$years_stmt = $conn->query("SELECT DISTINCT YEAR(sent_date) as year FROM outgoing ORDER BY year DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);

// Month names for display
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Outgoing Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body {
        background-color: #0e0f12;
        color: #e5e5e5;
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 20px;
    }
    .container {
        max-width: 1100px;
        margin: auto;
    }
    .card {
        background-color: #181a1e;
        border: 1px solid #00bcd4;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 0 10px #00bcd450;
    }
    h2 {
        color: #00bcd4;
        margin-bottom: 20px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-top: 10px;
    }
    input, select {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border-radius: 6px;
        border: none;
        background-color: #2b2e34;
        color: #fff;
        font-size: 14px;
    }
    select option {
        background-color: #1c1e24;
        color: #00bcd4;
        font-weight: bold;
    }
    input[type="submit"], .print-btn, .action-btn {
        background-color: #00bcd4;
        color: black;
        font-weight: bold;
        cursor: pointer;
        margin-top: 15px;
        transition: 0.2s;
        padding: 10px 15px;
        border-radius: 6px;
        border: none;
        text-decoration: none;
        display: inline-block;
        margin-right: 10px;
    }
    input[type="submit"]:hover, .print-btn:hover, .action-btn:hover {
        background-color: #0097a7;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #1c1e24;
        margin-top: 15px;
    }
    th, td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #333;
    }
    th {
        background-color: #00bcd4;
        color: black;
        cursor: pointer;
        position: relative;
    }
    th.sortable:hover {
        background-color: #0097a7;
    }
    th i {
        margin-left: 5px;
        font-style: normal;
    }
    .filter-box {
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .filter-box form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
        width: 100%;
    }
    .filter-box .form-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }
    .filter-box label {
        margin-bottom: 5px;
        font-size: 14px;
    }
    .back-btn {
        display: inline-block;
        background-color: #00bcd4;
        color: black;
        font-weight: bold;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        margin-bottom: 15px;
        transition: 0.3s;
        box-shadow: 0 0 10px #00bcd4a0;
    }
    .back-btn:hover {
        background-color: #0097a7;
        box-shadow: 0 0 20px #00bcd4;
    }
    .product-info {
        background: rgba(0, 188, 212, 0.1);
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
        display: none;
    }
    .product-info.active {
        display: block;
    }
    .auto-fill-btn {
        background-color: #ffc107;
        color: black;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 5px;
        font-size: 12px;
    }
    .auto-fill-btn:hover {
        background-color: #e0a800;
    }
    .print-only {
        display: none;
    }
    .actions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .sort-indicator {
        font-size: 12px;
        color: #ffc107;
        margin-left: 5px;
    }
    .total-row {
        background-color: rgba(0, 188, 212, 0.2);
        font-weight: bold;
    }
    .total-info {
        color: #00bcd4;
        font-size: 16px;
        margin-top: 15px;
        padding: 15px;
        background-color: rgba(0, 188, 212, 0.1);
        border-radius: 6px;
        text-align: center;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }
    .total-item {
        padding: 10px;
    }
    @media print {
        body {
            background-color: white;
            color: black;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }
        .card, table {
            border: 1px solid #000;
            box-shadow: none;
        }
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            background-color: #ddd !important;
            color: black !important;
            -webkit-print-color-adjust: exact;
            padding: 6px !important;
        }
        td {
            padding: 6px !important;
        }
        .back-btn, input, select, .filter-box, .print-btn, .action-btn, #outgoingForm, h2:first-child {
            display: none !important;
        }
        h2:last-child {
            display: block !important;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
        .total-info {
            display: block !important;
            border: 1px solid #000;
            margin-top: 10px;
            padding: 8px;
            font-size: 12px;
            background-color: #f0f0f0 !important;
            color: black !important;
        }
        .page-break {
            page-break-before: always;
        }
    }
</style>
</head>
<body>

<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
<div class="container">

<div class="card no-print">
    <h2>Send Out Product</h2>
    <form method="POST" id="outgoingForm">
        <label>Select Product:</label>
        <select name="product_id" id="product_id" onchange="updateProductInfo()" required>
            <option value="">-- Select Product --</option>
            <?php 
            $productData = [];
            $products = $conn->query("SELECT * FROM stocks WHERE quantity > 0 ORDER BY product_name ASC");
            if ($products && $products->rowCount() > 0): 
                while ($p = $products->fetch(PDO::FETCH_ASSOC)):
                    $productData[$p['product_id']] = $p;
            ?>
                <option value="<?= $p['product_id'] ?>" 
                        data-name="<?= htmlspecialchars($p['product_name']) ?>"
                        data-serial="<?= htmlspecialchars($p['serial_number']) ?>"
                        data-price="<?= $p['price'] ?>"
                        data-quantity="<?= $p['quantity'] ?>">
                    <?= htmlspecialchars($p['product_name']) ?> 
                    (Stock: <?= $p['quantity'] ?> | Price: ₱<?= number_format($p['price'], 2) ?>)
                </option>
            <?php endwhile; endif; ?>
        </select>

        <!-- Product Information Display -->
        <div id="productInfo" class="product-info">
            <div><strong>Selected Product:</strong> <span id="displayName">-</span></div>
            <div><strong>Current Stock:</strong> <span id="displayStock">-</span></div>
            <div><strong>Price:</strong> ₱<span id="displayPrice">-</span></div>
        </div>

        <!-- Serial Number Field -->
        <label>Serial Number:
            <button type="button" class="auto-fill-btn" onclick="fillSerialFromProduct()">Auto-fill from product</button>
        </label>
        <input type="text" name="serial_number" id="serial_number" required>

        <label>Name of Dentist:</label>
        <input type="text" name="dentist_name" required>

        <label>Price:</label>
        <input type="number" step="0.01" id="price" name="price" required>

        <label>OS / Resibo Number:</label>
        <input type="text" name="os_number" required>

        <label>Terms of Payment:</label>
        <input type="text" name="terms_of_payment" required>

        <label>Quantity to Send:</label>
        <input type="number" name="quantity" id="quantity" min="1" required onchange="validateQuantity()">
        <small id="quantityError" style="color: #ff6b6b; display: none;">Insufficient stock!</small>

        <input type="submit" value="Send Out Product">
    </form>
</div>

<div class="card">
    <div class="actions-header no-print">
        <h2>Outgoing Products History</h2>
        <div>
            <button onclick="window.print()" class="print-btn">Print Current View</button>
            <button onclick="printFilteredTable()" class="action-btn">Print Report</button>
        </div>
    </div>
    
    <div class="print-only">
        <h2>Outgoing Products Report</h2>
        <p><strong>Generated on:</strong> <?php echo date('F d, Y H:i:s'); ?></p>
        <?php if ($filter): ?>
            <p><strong>Product:</strong> <?php echo htmlspecialchars($filter); ?></p>
        <?php endif; ?>
        <?php if ($filter_month): ?>
            <p><strong>Month:</strong> <?php echo $month_names[$filter_month]; ?></p>
        <?php endif; ?>
        <?php if ($filter_year): ?>
            <p><strong>Year:</strong> <?php echo $filter_year; ?></p>
        <?php endif; ?>
        <?php if (!$filter_month && !$filter_year): ?>
            <p><strong>Period:</strong> All Time</p>
        <?php endif; ?>
        <p><strong>Sorted by:</strong> <?php 
            $sort_labels = [
                'sent_date' => 'Date',
                'product_name' => 'Product Name',
                'dentist_name' => 'Dentist Name',
                'quantity' => 'Quantity',
                'price' => 'Price'
            ];
            echo ($sort_labels[$sort_by] ?? 'Date') . ' (' . $sort_order . ')'; 
        ?></p>
        <p><strong>Total Quantity:</strong> <?= $total_quantity ?></p>
    </div>
    
    <div class="filter-box no-print">
        <form method="GET">
            <div class="form-group">
                <label>Filter by Product:</label>
                <select name="filter">
                    <option value="">-- All Products --</option>
                    <?php
                    $product_list = $conn->query("SELECT DISTINCT product_name FROM outgoing ORDER BY product_name ASC");
                    while ($prod = $product_list->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?= $prod['product_name'] ?>" <?= ($filter == $prod['product_name']) ? 'selected' : '' ?>>
                            <?= $prod['product_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Filter by Month:</label>
                <select name="filter_month">
                    <option value="">-- All Months --</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= ($filter_month == $i) ? 'selected' : '' ?>>
                            <?= $month_names[$i] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Filter by Year:</label>
                <select name="filter_year">
                    <option value="">-- All Years --</option>
                    <?php foreach ($years as $year_data): ?>
                        <option value="<?= $year_data['year'] ?>" <?= ($filter_year == $year_data['year']) ? 'selected' : '' ?>>
                            <?= $year_data['year'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Sort by:</label>
                <select name="sort_by">
                    <option value="sent_date" <?= ($sort_by == 'sent_date') ? 'selected' : '' ?>>Date</option>
                    <option value="product_name" <?= ($sort_by == 'product_name') ? 'selected' : '' ?>>Product Name</option>
                    <option value="dentist_name" <?= ($sort_by == 'dentist_name') ? 'selected' : '' ?>>Dentist</option>
                    <option value="quantity" <?= ($sort_by == 'quantity') ? 'selected' : '' ?>>Quantity</option>
                    <option value="price" <?= ($sort_by == 'price') ? 'selected' : '' ?>>Price</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Order:</label>
                <select name="sort_order">
                    <option value="DESC" <?= ($sort_order == 'DESC') ? 'selected' : '' ?>>Descending</option>
                    <option value="ASC" <?= ($sort_order == 'ASC') ? 'selected' : '' ?>>Ascending</option>
                </select>
            </div>
            
            <input type="submit" value="Apply Filters">
            <button type="button" onclick="resetFilters()" class="action-btn">Reset All</button>
        </form>
    </div>

    <table id="outgoingTable">
        <thead>
            <tr>
                <?php
                $columns = [
                    'id' => 'ID',
                    'product_name' => 'Product',
                    'serial_number' => 'Serial No.',
                    'dentist_name' => 'Dentist',
                    'price' => 'Price',
                    'os_number' => 'OS/Resibo',
                    'terms_of_payment' => 'Terms',
                    'quantity' => 'Quantity',
                    'sent_date' => 'Sent Date'
                ];
                
                foreach ($columns as $key => $label) {
                    $sort_indicator = '';
                    if ($sort_by == $key) {
                        $sort_indicator = $sort_order == 'ASC' ? ' ↑' : ' ↓';
                    }
                    
                    $next_order = ($sort_by == $key && $sort_order == 'ASC') ? 'DESC' : 'ASC';
                    
                    // Build URL with all current filters
                    $url_params = [];
                    if ($filter) $url_params[] = "filter=" . urlencode($filter);
                    if ($filter_month) $url_params[] = "filter_month=" . urlencode($filter_month);
                    if ($filter_year) $url_params[] = "filter_year=" . urlencode($filter_year);
                    $url_params[] = "sort_by=$key";
                    $url_params[] = "sort_order=$next_order";
                    $url = "?" . implode('&', $url_params);
                    
                    echo "<th class='sortable' onclick=\"window.location.href='$url'\">
                            $label<span class='sort-indicator'>$sort_indicator</span>
                          </th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($outgoing && $outgoing->rowCount() > 0): 
                while ($row = $outgoing->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['serial_number']) ?></td>
                <td><?= htmlspecialchars($row['dentist_name']) ?></td>
                <td>₱<?= number_format($row['price'], 2) ?></td>
                <td><?= htmlspecialchars($row['os_number']) ?></td>
                <td><?= htmlspecialchars($row['terms_of_payment']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= date('M d, Y H:i', strtotime($row['sent_date'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="9">No outgoing records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Total Information Display -->
    <div class="total-info">
        <div class="total-item">
            <strong>Total Records:</strong> <?= $total_rows ?>
        </div>
        <div class="total-item">
            <strong>Total Quantity Sent:</strong> <?= $total_quantity ?>
        </div>
        <?php if ($filter_month): ?>
        <div class="total-item">
            <strong>Month:</strong> <?= $month_names[$filter_month] ?> <?= $filter_year ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
const productData = <?= json_encode($productData) ?>;

function updateProductInfo() {
    const select = document.getElementById('product_id');
    const productId = select.value;
    const productInfo = document.getElementById('productInfo');
    
    if (productId && productData[productId]) {
        const product = productData[productId];
        
        document.getElementById('displayName').textContent = product.product_name;
        document.getElementById('displayStock').textContent = product.quantity;
        document.getElementById('displayPrice').textContent = parseFloat(product.price).toFixed(2);
        document.getElementById('price').value = product.price;
        
        productInfo.classList.add('active');
        
        // Update quantity max value
        document.getElementById('quantity').max = product.quantity;
    } else {
        productInfo.classList.remove('active');
        document.getElementById('price').value = '';
    }
}

function fillSerialFromProduct() {
    const productId = document.getElementById('product_id').value;
    if (productId && productData[productId]) {
        document.getElementById('serial_number').value = productData[productId].serial_number;
    }
}

function validateQuantity() {
    const quantityInput = document.getElementById('quantity');
    const productId = document.getElementById('product_id').value;
    const errorElement = document.getElementById('quantityError');
    
    if (productId && productData[productId]) {
        const availableStock = productData[productId].quantity;
        const requestedQuantity = parseInt(quantityInput.value);
        
        if (requestedQuantity > availableStock) {
            errorElement.style.display = 'block';
            errorElement.textContent = `Insufficient stock! Available: ${availableStock}`;
            quantityInput.setCustomValidity('Insufficient stock');
        } else {
            errorElement.style.display = 'none';
            quantityInput.setCustomValidity('');
        }
    }
}

function resetFilters() {
    window.location.href = 'outgoing_dashboard.php';
}

function printFilteredTable() {
    // Create a print-friendly version with all filters
    const printWindow = window.open('', '_blank');
    
    // Get month name for display
    const monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"];
    const filterMonth = <?= $filter_month ?: 'null' ?>;
    const monthName = filterMonth ? monthNames[filterMonth - 1] : '';
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Outgoing Products Report</title>
            <style>
                @page {
                    margin: 0.5in;
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 0;
                    font-size: 11px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 15px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                h1 { 
                    color: #333; 
                    margin: 0 0 5px 0;
                    font-size: 18px;
                }
                .report-info { 
                    margin-bottom: 15px;
                    text-align: center;
                }
                .report-info p {
                    margin: 2px 0;
                }
                .filters {
                    background-color: #f5f5f5;
                    padding: 8px;
                    border-radius: 4px;
                    margin-bottom: 10px;
                    font-size: 10px;
                }
                .filters strong {
                    color: #333;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 10px;
                    font-size: 10px;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 5px; 
                    text-align: left; 
                }
                th { 
                    background-color: #f2f2f2; 
                    font-weight: bold;
                }
                .totals {
                    margin-top: 15px;
                    padding: 10px;
                    background-color: #f8f8f8;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 10px;
                    font-size: 11px;
                }
                .total-item {
                    text-align: center;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Outgoing Products Report</h1>
            </div>
            
            <div class="report-info">
                <div class="filters">
                    <p><strong>Report Period:</strong> 
                        ${<?= $filter_month ? "'" . $month_names[$filter_month] . "'" : "'All Months'"?>} 
                        ${<?= $filter_year ? $filter_year : "'All Years'"?>}
                    </p>
                    <?php if ($filter): ?>
                    <p><strong>Product Filter:</strong> <?php echo htmlspecialchars($filter); ?></p>
                    <?php endif; ?>
                    <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                </div>
            </div>
    `);
    
    // Add totals section
    printWindow.document.write(`
        <div class="totals">
            <div class="total-item">
                <strong>Total Records:</strong><br>
                <?= $total_rows ?>
            </div>
            <div class="total-item">
                <strong>Total Quantity:</strong><br>
                <?= $total_quantity ?>
            </div>
        </div>
    `);
    
    // Add the table content
    printWindow.document.write('<table>');
    printWindow.document.write('<thead><tr>');
    <?php foreach ($columns as $label): ?>
    printWindow.document.write('<th><?php echo $label; ?></th>');
    <?php endforeach; ?>
    printWindow.document.write('</tr></thead><tbody>');
    
    // Reset the pointer and fetch rows again
    <?php 
    $stmt->execute();
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
    ?>
    printWindow.document.write(`
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['serial_number']) ?></td>
            <td><?= htmlspecialchars($row['dentist_name']) ?></td>
            <td>₱<?= number_format($row['price'], 2) ?></td>
            <td><?= htmlspecialchars($row['os_number']) ?></td>
            <td><?= htmlspecialchars($row['terms_of_payment']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= date('M d, Y H:i', strtotime($row['sent_date'])) ?></td>
        </tr>
    `);
    <?php endwhile; ?>
    
    printWindow.document.write('</tbody></table>');
    
    // Add footer
    printWindow.document.write(`
        <div class="footer">
            <p>Outgoing Products Report - Page 1 of 1</p>
        </div>
    `);
    
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    // Wait for content to load then print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateProductInfo();
});

// Show SweetAlert messages from session
<?php if (isset($_SESSION['success'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Success',
    text: '<?= $_SESSION['success'] ?>',
    confirmButtonColor: '#00bcd4'
});
<?php 
unset($_SESSION['success']);
endif; ?>

<?php if (isset($_SESSION['error'])): ?>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: '<?= $_SESSION['error'] ?>',
    confirmButtonColor: '#00bcd4'
});
<?php 
unset($_SESSION['error']);
endif; ?>
</script>

</body>
</html>