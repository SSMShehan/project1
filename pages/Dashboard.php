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

include '../addphp/navbar.php';
// Check if user is logged in


// Get all dashboard statistics
$stats = [
    'items' => $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0],
    'orders' => $conn->query("SELECT COUNT(*) FROM sales_orders WHERE status != 'cancelled'")->fetch_row()[0],
    'backorders' => $conn->query("SELECT COUNT(*) FROM back_orders WHERE status = 'pending'")->fetch_row()[0],
    'stocks' => $conn->query("SELECT SUM(quantity_on_hand) FROM inventory")->fetch_row()[0],
    'purchase_orders' => $conn->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'ordered'")->fetch_row()[0],
    'received_orders' => $conn->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'received'")->fetch_row()[0],
    'final_products' => $conn->query("SELECT COUNT(*) FROM products WHERE is_active = TRUE")->fetch_row()[0], // Adjusted for final products
    'sales' => $conn->query("SELECT COUNT(*) FROM sales_orders WHERE status = 'shipped'")->fetch_row()[0],
    'users' => $conn->query("SELECT COUNT(*) FROM users WHERE is_active = TRUE")->fetch_row()[0],
    'inventory_value' => $conn->query("SELECT calculate_inventory_value()")->fetch_row()[0]
];

// Get recent activities
$activitiesResult = $conn->query("
    (SELECT 'sale' as type, order_number as reference, order_date as date, total_amount, status 
     FROM sales_orders ORDER BY order_date DESC LIMIT 3)
    UNION
    (SELECT 'purchase' as type, po_number as reference, order_date as date, total_amount, status 
     FROM purchase_orders ORDER BY order_date DESC LIMIT 3)
    ORDER BY date DESC LIMIT 5
");
$activities = [];
while ($row = $activitiesResult->fetch_assoc()) {
    $activities[] = $row;
}

// Get low stock items
$lowStockResult = $conn->query("
    SELECT p.name, p.sku, i.quantity_on_hand, p.reorder_level 
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    WHERE (i.quantity_on_hand - i.quantity_allocated) <= p.reorder_level
    AND p.is_active = TRUE
    LIMIT 5
");
$lowStockItems = [];
while ($row = $lowStockResult->fetch_assoc()) {
    $lowStockItems[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GSM Garment</title>
    <link rel="stylesheet" href="../styles/dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <h1>Welcome to Stock Management System - MGS Garment</h1>
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
                        <div class="card-value"><?= $stats['items'] ?></div>
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
            <!-- Orders Card -->
            <a href="order.php">
                <div class="dashboard-card">
                    <div class="card-icon2">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-content">
                        <h3>Orders</h3>
                        <div class="card-value"><?= $stats['orders'] ?></div>
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
                        <div class="card-value"><?= $stats['backorders'] ?></div>
                    </div>
                </div>
                <div class="card-content">
                    <h3>Users</h3>
                    <div class="card-value"><?php echo $usersCount; ?></div>
            </a>

            <!-- Stocks Card -->
            <a href="Stock.php">
                <div class="dashboard-card">
                    <div class="card-icon4">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-content">
                        <h3>Stocks</h3>
                        <div class="card-value"><?= $stats['stocks'] ?></div>
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
                        <div class="card-value"><?= $stats['purchase_orders'] ?></div>
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
                        <div class="card-value"><?= $stats['received_orders'] ?></div>
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
                        <div class="card-value"><?= $stats['final_products'] ?></div>
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
                        <div class="card-value"><?= $stats['sales'] ?></div>
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
                        <div class="card-value"><?= $stats['users'] ?></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Activities Section -->
        <div class="recent-activities">
            <h2>Recent Activities</h2>
            <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <span class="activity-type <?= $activity['type'] == 'sale' ? 'type-sale' : 'type-purchase' ?>">
                            <?= ucfirst($activity['type']) ?>
                        </span>
                        <span><?= htmlspecialchars($activity['reference']) ?></span>
                    </div>
                    <div class="activity-details">
                        <span class="activity-amount">$<?= number_format($activity['total_amount'], 2) ?></span>
                        <span class="activity-date"><?= date('M d, Y', strtotime($activity['date'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Low Stock Alert Section -->
        <div class="low-stock-alerts">
            <h2>Low Stock Alerts</h2>
            <?php if (count($lowStockItems) > 0): ?>
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="stock-item">
                        <div class="stock-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="stock-sku">SKU: <?= htmlspecialchars($item['sku']) ?></div>
                        <div class="stock-level">
                            <span>Current: <?= $item['quantity_on_hand'] ?></span>
                            <span>Reorder at: <?= $item['reorder_level'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No low stock items at this time.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>
</body>
</html>
