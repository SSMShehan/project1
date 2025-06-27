<?php
include '../addphp/navbar.php';
include '../config/db_config.php'; // Make sure this path is correct

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get count from database
function getCount($conn, $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    return 0;
}

// Function to get count with condition
function getCountWithCondition($conn, $table, $condition) {
    $sql = "SELECT COUNT(*) as count FROM $table WHERE $condition";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    return 0;
}

// Get counts from database
$itemsCount = getCount($conn, 'products');
$ordersCount = getCount($conn, 'sales_orders');
$backordersCount = getCountWithCondition($conn, 'back_orders', "status = 'pending'");
$stocksCount = getCount($conn, 'inventory');
$purchaseOrdersCount = getCount($conn, 'purchase_orders');
$receiveOrdersCount = getCountWithCondition($conn, 'purchase_orders', "status = 'received'");
$finalProductsCount = getCountWithCondition($conn, 'products', "is_active = 1"); // Assuming active products are final
$salesCount = getCountWithCondition($conn, 'sales_orders', "status = 'shipped' OR status = 'delivered'");
$usersCount = getCount($conn, 'users');

$conn->close();
?>
        <link rel="stylesheet" href="../styles/dashboard_style.css">
    <div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
        <h1>Welcome to Stock Management System - GSM Garment</h1>
    </div>

    <!-- Cards Grid -->
    <div class="cards-grid">
        <!-- Items Card -->
            <a href="items.php">
                <div class="dashboard-card">
                    <div class="card-icon1">
                        <i class="fa-solid fa-shirt"></i>
                    </div>
                    <div class="card-content">
                        <h3>Items</h3>
                        <div class="card-value"><?php echo $itemsCount; ?></div>
                    </div>
                </div>
            </a>
        

        <!-- Orders Card -->
        <a href="order.php">
            <div class="dashboard-card">
            <div class="card-icon2">
            <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-content">
                <h3>Orders</h3>
                <div class="card-value"><?php echo $ordersCount; ?></div>
            </div>
            </div>
        </a>

        <!-- Backorders Card -->
        <a href="Backorders.php">
            <div class="dashboard-card warning">
            <div class="card-icon3">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="card-content">
                <h3>Backorders</h3>
                <div class="card-value"><?php echo $backordersCount; ?></div>
            </div>
            </div>
        </a>

        <!-- Stocks Card -->
        <a href="Stock.php">
            <div class="dashboard-card">
            <div class="card-icon4">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="card-content">
                <h3>Stocks</h3>
                <div class="card-value"><?php echo $stocksCount; ?></div>
            </div>
            </div>
        </a>

        <!-- Purchase Orders Card -->
        <a href="Purchasesorders.php">
            <div class="dashboard-card">
            <div class="card-icon5">
            <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="card-content">
                <h3>Purchase Orders</h3>
                <div class="card-value"><?php echo $purchaseOrdersCount; ?></div>
            </div>
            </div>
        </a>

        <!-- Receive Orders Card -->
        <a href="Receiveorders.php">
            <div class="dashboard-card highlight">
            <div class="card-icon6">
            <i class="fas fa-truck"></i>
            </div>
            <div class="card-content">
                <h3>Receive Orders</h3>
                <div class="card-value"><?php echo $receiveOrdersCount; ?></div>
            </div>
            </div>
        </a>

        <!-- Final Products Card -->
        <a href="Finalproduct.php">
            <div class="dashboard-card">
            <div class="card-icon7">
            <i class="fa-solid fa-check-to-slot"></i>
            </div>
            <div class="card-content">
                <h3>Final Products</h3>
                <div class="card-value"><?php echo $finalProductsCount; ?></div>
            </div>
            </div>
        </a>

        <!-- Sales Card -->
        <a href="Sales.php">
            <div class="dashboard-card success">
                <div class="card-icon8">
                <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3>Sales</h3>
                    <div class="card-value"><?php echo $salesCount; ?></div>
                </div>
            </div>
        </a>

        <!-- Users Card -->
        <a href="Users.php">
            <div class="dashboard-card">
                <div class="card-icon9">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3>Users</h3>
                    <div class="card-value"><?php echo $usersCount; ?></div>
                </div>
            </div>
        </a>
    </div>
    </div>
    </div>