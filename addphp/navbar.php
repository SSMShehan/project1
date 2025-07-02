<?php
require_once '../config/db_config.php';

$sqlforname = "SELECT setting_value FROM system_settings WHERE setting_id = 1";
$resultforname = $conn->query($sqlforname);

if ($resultforname && $resultforname->num_rows > 0) {
    $row = $resultforname->fetch_assoc();
    $companyName = $row['setting_value'];
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("location: login.php");
    exit;
}

// Get logged-in user's ID
$user_id = $_SESSION["id"];

// Fetch the logged-in user's details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="../styles/index_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <nav class="navbar1">
        <div class="left-section">
            <div class="logo">
                <span class="logo-icon">G</span>
                <span class="logo-text"><?php echo htmlspecialchars($companyName); ?></span>
            </div>
            <button class="back-btn">&#8592;</button>
            <h1 class="dboard">Dashboard</h1>
        </div>

        <div class="right-section">
            <input type="text" class="search" placeholder="Search" />
            <button class="notif-btn">&#128276;</button>
            <div class="profile">
                <span class="profile-icon"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span> 
                <div>
                    <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                    <small>Product Manager</small>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <nav>
                <ul class="menu-items">
                    <li><a href="Dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="Products.php"><i class="fa-solid fa-shirt"></i> Products</a></li>
                    <li><a href="inventory.php"><i class="fas fa-shopping-cart"></i>Products Inventory</a></li> <br>
                    
                    <!-- Purchasing Section -->
                    <li class="menu-header">PURCHASING</li><hr>
                  
                    <li><a href="raw_material_purchase_orders.php"><i class="fas fa-exchange-alt"></i> Raw Material Purchases</a></li>
                    <li><a href="suppliers.php"><i class="fas fa-boxes"></i> Suppliers</a></li><br>
                    
                    
                    
                    <!-- Manufacturing Section -->
                    <li class="menu-header">MANUFACTURING</li><hr>
                    <li><a href="manufacturing_orders.php"><i class="fas fa-industry"></i> Manufacturing Orders</a></li>
                    <li><a href="raw_materials.php"><i class="fas fa-boxes"></i> Raw Materials Inventory</a></li><br>

                     <!-- Sales Section -->
                    <li class="menu-header">SALES</li><hr>
                    <li><a href="sales_orders.php"><i class="fas fa-file-invoice-dollar"></i> Sales Orders</a></li>
                      <li><a href="purchase_orders.php"><i class="fas fa-exchange-alt"></i> Product Purchases</a></li><br>
                    
                    <!-- Reports -->
                    <li class="menu-header">REPORTS</li><hr>
                    <li><a href="reports.php"><i class="fa-solid fa-chart-bar"></i> Inventory Reports</a></li><br>
                    
                    <!-- Settings -->
                    <li class="menu-header">SYSTEM</li><hr>
                    <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
                    <li><a href="Setting.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="table-container">
                <div class="table-header">
                </div>

    <script src="../js/index_script.js"></script>
</body>
</html>