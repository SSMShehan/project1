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
               <!-- <span class="profile-icon"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span> 
                <div>
                    <strong>
                    <?php echo htmlspecialchars($user['username']); ?> -->
                    </strong><br>
                    <small>Product Manager</small>
                </div>
            </div>
        </div>
    </nav>


    <div class="dashboard-container">
        <aside class="sidebar">
            <nav>
                <ul class="menu-items">
                    <li><a href="Dashboard.php" ><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="Products.php"><i class="fa-solid fa-shirt"></i> Products</a></li>
                    <li><a href="inventory.php"><i class="fas fa-shopping-cart"></i> inventory</a></li>
                    <li><a href="purchase_orders.php"><i class="fas fa-exchange-alt"></i> Purchase orders</a></li>
                    <li><a href="suppliers.php"><i class="fas fa-boxes"></i> Suppliers Management</a></li>
                    <li><a href="sales_orders.php"><i class="fas fa-file-invoice-dollar"></i>  Sales Management</a></li>
                    <li><a href="customers.php"><i class="fas fa-truck"></i> Customers Management</a></li>
                    <li><a href="reports.php"><i class="fa-solid fa-check-to-slot"></i> Inventory Reports</a></li>
                    <!-- <li><a href="Sales.php"><i class="fas fa-chart-line"></i> Sales</a></li> -->
                    <!-- <li><a href="Users.php"><i class="fas fa-users"></i> Users</a></li> -->
                    <li><a href="Setting.php"><i class="fas fa-cog"></i> Setting</a></li>
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