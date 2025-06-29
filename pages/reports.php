<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Set default report type
$report_type = isset($_GET['report']) ? $_GET['report'] : 'sales';

// Set date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventory Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .report-nav {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .report-nav a {
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            border: 1px solid #ddd;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            background-color: #f8f9fa;
        }
        
        .report-nav a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .report-nav a:hover:not(.active) {
            background-color: #e9ecef;
        }
        
        .filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .report-section {
            margin-bottom: 30px;
        }
        
        .report-title {
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        
        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            flex: 1;
            min-width: 200px;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .summary-card-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .summary-card-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .currency {
            font-size: 16px;
            color: #28a745;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .text-warning {
            color: #ffc107;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Inventory Reports</h2>
</div>

<!-- Report Navigation -->
<div class="report-nav">
    <a href="?report=sales" class="<?php echo $report_type === 'sales' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Sales Reports
    </a>
    <a href="?report=inventory" class="<?php echo $report_type === 'inventory' ? 'active' : ''; ?>">
        <i class="fas fa-boxes"></i> Inventory Reports
    </a>
    <a href="?report=purchasing" class="<?php echo $report_type === 'purchasing' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i> Purchasing Reports
    </a>
    <a href="?report=profitability" class="<?php echo $report_type === 'profitability' ? 'active' : ''; ?>">
        <i class="fas fa-money-bill-wave"></i> Profitability Analysis
    </a>
</div>

<!-- Date Range Filter -->
<div class="filter-container">
    <form method="GET" action="">
        <input type="hidden" name="report" value="<?php echo $report_type; ?>">
        
        <div class="filter-row">
            <div class="filter-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            
            <div class="filter-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Apply Date Range
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='?report=<?php echo $report_type; ?>'">
                <i class="fas fa-times"></i> Reset
            </button>
        </div>
    </form>
</div>

<?php if ($report_type === 'sales'): ?>
    <!-- Sales Reports -->
    <div class="report-section">
        <h3 class="report-title">Sales Summary</h3>
        
        <?php
        // Get total sales for the period
        $sales_sql = "SELECT 
                        COUNT(DISTINCT so.so_id) as order_count,
                        SUM(so.total_amount) as total_sales,
                        SUM(so.total_amount - so.tax_amount - so.discount_amount) as net_sales,
                        SUM(so.tax_amount) as total_tax,
                        SUM(so.discount_amount) as total_discount
                      FROM sales_orders so
                      WHERE so.order_date BETWEEN ? AND ?
                      AND so.status NOT IN ('cancelled', 'draft')";
        
        $stmt = $conn->prepare($sales_sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $sales_result = $stmt->get_result();
        $sales_data = $sales_result->fetch_assoc();
        $stmt->close();
        ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">Total Orders</div>
                <div class="summary-card-value"><?php echo $sales_data['order_count'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Total Sales</div>
                <div class="summary-card-value">Rs <?php echo number_format($sales_data['total_sales'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Net Sales</div>
                <div class="summary-card-value">Rs <?php echo number_format($sales_data['net_sales'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Total Tax</div>
                <div class="summary-card-value">Rs <?php echo number_format($sales_data['total_tax'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Total Discount</div>
                <div class="summary-card-value">Rs <?php echo number_format($sales_data['total_discount'] ?? 0, 2); ?></div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="salesTrendChart"></canvas>
        </div>
        
        <h3 class="report-title">Sales by Product</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Orders</th>
                    <th>Quantity Sold</th>
                    <th>Total Sales</th>
                    <th>% of Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $product_sales_sql = "SELECT 
                                        p.product_id,
                                        p.name as product_name,
                                        c.name as category_name,
                                        COUNT(DISTINCT so.so_id) as order_count,
                                        SUM(soi.quantity) as total_quantity,
                                        SUM(soi.quantity * soi.unit_price) as total_sales
                                      FROM sales_order_items soi
                                      JOIN sales_orders so ON soi.so_id = so.so_id
                                      JOIN products p ON soi.product_id = p.product_id
                                      LEFT JOIN categories c ON p.category_id = c.category_id
                                      WHERE so.order_date BETWEEN ? AND ?
                                      AND so.status NOT IN ('cancelled', 'draft')
                                      GROUP BY p.product_id, p.name, c.name
                                      ORDER BY total_sales DESC";
                
                $stmt = $conn->prepare($product_sales_sql);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $product_sales_result = $stmt->get_result();
                
                $total_sales = $sales_data['total_sales'] ?? 1; // Avoid division by zero
                
                while ($row = $product_sales_result->fetch_assoc()):
                    $percentage = ($row['total_sales'] / $total_sales) * 100;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo $row['order_count']; ?></td>
                        <td><?php echo $row['total_quantity']; ?></td>
                        <td>Rs <?php echo number_format($row['total_sales'], 2); ?></td>
                        <td><?php echo number_format($percentage, 1); ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <h3 class="report-title">Sales by Customer</h3>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Orders</th>
                    <th>Total Sales</th>
                    <th>% of Total</th>
                    <th>First Order</th>
                    <th>Last Order</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $customer_sales_sql = "SELECT 
                                        c.customer_id,
                                        c.name as customer_name,
                                        COUNT(DISTINCT so.so_id) as order_count,
                                        SUM(so.total_amount) as total_sales,
                                        MIN(so.order_date) as first_order_date,
                                        MAX(so.order_date) as last_order_date
                                      FROM sales_orders so
                                      JOIN customers c ON so.customer_id = c.customer_id
                                      WHERE so.order_date BETWEEN ? AND ?
                                      AND so.status NOT IN ('cancelled', 'draft')
                                      GROUP BY c.customer_id, c.name
                                      ORDER BY total_sales DESC";
                
                $stmt = $conn->prepare($customer_sales_sql);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $customer_sales_result = $stmt->get_result();
                
                while ($row = $customer_sales_result->fetch_assoc()):
                    $percentage = ($row['total_sales'] / $total_sales) * 100;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo $row['order_count']; ?></td>
                        <td>Rs <?php echo number_format($row['total_sales'], 2); ?></td>
                        <td><?php echo number_format($percentage, 1); ?>%</td>
                        <td><?php echo date('M d, Y', strtotime($row['first_order_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['last_order_date'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <script>
        // Sales Trend Chart
        document.addEventListener('DOMContentLoaded', function() {
            // This would be replaced with actual data from PHP
            const ctx = document.getElementById('salesTrendChart').getContext('2d');
            const salesTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Monthly Sales',
                        data: [12000, 19000, 15000, 18000, 22000, 25000, 28000, 26000, 24000, 27000, 30000, 35000],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rs ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Sales: Rs ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
    </div>

<?php elseif ($report_type === 'inventory'): ?>
    <!-- Inventory Reports -->
    <div class="report-section">
        <h3 class="report-title">Inventory Status</h3>
        
        <?php
        // Get inventory valuation
        $inventory_value = $conn->query("SELECT calculate_inventory_value() as total_value")->fetch_assoc()['total_value'];
        
        // Get inventory status summary
        $inventory_summary_sql = "SELECT 
                                    COUNT(*) as total_products,
                                    SUM(quantity_on_hand) as total_quantity,
                                    SUM(quantity_allocated) as total_allocated,
                                    SUM(quantity_on_hand - quantity_allocated) as total_available
                                  FROM inventory i
                                  JOIN products p ON i.product_id = p.product_id
                                  WHERE p.is_active = 1";
        
        $inventory_summary = $conn->query($inventory_summary_sql)->fetch_assoc();
        ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">Total Products</div>
                <div class="summary-card-value"><?php echo $inventory_summary['total_products'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Total Quantity</div>
                <div class="summary-card-value"><?php echo $inventory_summary['total_quantity'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Allocated Quantity</div>
                <div class="summary-card-value"><?php echo $inventory_summary['total_allocated'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Available Quantity</div>
                <div class="summary-card-value"><?php echo $inventory_summary['total_available'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Inventory Value</div>
                <div class="summary-card-value">Rs <?php echo number_format($inventory_value, 2); ?></div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="inventoryTurnoverChart"></canvas>
        </div>
        
        <h3 class="report-title">Inventory by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Products</th>
                    <th>Total Quantity</th>
                    <th>Inventory Value</th>
                    <th>Potential Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $category_inventory_sql = "SELECT 
                                            c.category_id,
                                            c.name as category_name,
                                            COUNT(p.product_id) as product_count,
                                            SUM(i.quantity_on_hand) as total_quantity,
                                            SUM(p.cost_price * i.quantity_on_hand) as total_value,
                                            SUM(p.unit_price * i.quantity_on_hand) as potential_revenue
                                          FROM products p
                                          JOIN inventory i ON p.product_id = i.product_id
                                          LEFT JOIN categories c ON p.category_id = c.category_id
                                          WHERE p.is_active = 1
                                          GROUP BY c.category_id, c.name
                                          ORDER BY total_value DESC";
                
                $category_inventory_result = $conn->query($category_inventory_sql);
                
                while ($row = $category_inventory_result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo $row['product_count']; ?></td>
                        <td><?php echo $row['total_quantity']; ?></td>
                        <td>Rs <?php echo number_format($row['total_value'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['potential_revenue'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <h3 class="report-title">Products to Reorder</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>On Hand</th>
                    <th>Allocated</th>
                    <th>Available</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $reorder_sql = "SELECT 
                                    p.product_id,
                                    p.sku,
                                    p.name as product_name,
                                    c.name as category_name,
                                    i.quantity_on_hand,
                                    i.quantity_allocated,
                                    (i.quantity_on_hand - i.quantity_allocated) as available_quantity,
                                    p.reorder_level,
                                    s.name as supplier_name
                                FROM products p
                                JOIN inventory i ON p.product_id = i.product_id
                                LEFT JOIN categories c ON p.category_id = c.category_id
                                LEFT JOIN suppliers s ON p.product_id = s.supplier_id
                                WHERE (i.quantity_on_hand - i.quantity_allocated) <= p.reorder_level
                                AND p.is_active = 1
                                ORDER BY available_quantity ASC";
                
                $reorder_result = $conn->query($reorder_sql);
                
                while ($row = $reorder_result->fetch_assoc()):
                    $status_class = ($row['available_quantity'] <= 0) ? 'text-danger' : 'text-warning';
                    $status_text = ($row['available_quantity'] <= 0) ? 'Out of Stock' : 'Low Stock';
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['sku']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo $row['quantity_on_hand']; ?></td>
                        <td><?php echo $row['quantity_allocated']; ?></td>
                        <td><?php echo $row['available_quantity']; ?></td>
                        <td><?php echo $row['reorder_level']; ?></td>
                        <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <script>
        // Inventory Turnover Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('inventoryTurnoverChart').getContext('2d');
            const inventoryTurnoverChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['T-Shirts', 'Jeans', 'Outerwear', 'Accessories'],
                    datasets: [{
                        label: 'Inventory Value',
                        data: [4500, 6800, 2400, 1600],
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Potential Revenue',
                        data: [8500, 12500, 4800, 3200],
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rs ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rs ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
    </div>

<?php elseif ($report_type === 'purchasing'): ?>
    <!-- Purchasing Reports -->
    <div class="report-section">
        <h3 class="report-title">Purchasing Summary</h3>
        
        <?php
        // Get purchasing summary
        $purchasing_sql = "SELECT 
                            COUNT(DISTINCT po.po_id) as po_count,
                            SUM(po.total_amount) as total_spend,
                            AVG(po.total_amount) as avg_po_value,
                            MIN(po.order_date) as first_po_date,
                            MAX(po.order_date) as last_po_date
                          FROM purchase_orders po
                          WHERE po.order_date BETWEEN ? AND ?
                          AND po.status NOT IN ('cancelled', 'draft')";
        
        $stmt = $conn->prepare($purchasing_sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $purchasing_result = $stmt->get_result();
        $purchasing_data = $purchasing_result->fetch_assoc();
        $stmt->close();
        ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">Purchase Orders</div>
                <div class="summary-card-value"><?php echo $purchasing_data['po_count'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Total Spend</div>
                <div class="summary-card-value">Rs <?php echo number_format($purchasing_data['total_spend'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Avg PO Value</div>
                <div class="summary-card-value">Rs <?php echo number_format($purchasing_data['avg_po_value'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">First PO Date</div>
                <div class="summary-card-value"><?php echo $purchasing_data['first_po_date'] ? date('M d, Y', strtotime($purchasing_data['first_po_date'])) : 'N/A'; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Last PO Date</div>
                <div class="summary-card-value"><?php echo $purchasing_data['last_po_date'] ? date('M d, Y', strtotime($purchasing_data['last_po_date'])) : 'N/A'; ?></div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="purchasingTrendChart"></canvas>
        </div>
        
        <h3 class="report-title">Supplier Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>PO Count</th>
                    <th>Total Spend</th>
                    <th>Avg PO Value</th>
                    <th>First PO</th>
                    <th>Last PO</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $supplier_performance_sql = "SELECT 
                                            s.supplier_id,
                                            s.name as supplier_name,
                                            COUNT(DISTINCT po.po_id) as po_count,
                                            SUM(po.total_amount) as total_spend,
                                            AVG(po.total_amount) as avg_po_value,
                                            MIN(po.order_date) as first_po_date,
                                            MAX(po.order_date) as last_po_date
                                          FROM purchase_orders po
                                          JOIN suppliers s ON po.supplier_id = s.supplier_id
                                          WHERE po.order_date BETWEEN ? AND ?
                                          AND po.status NOT IN ('cancelled', 'draft')
                                          GROUP BY s.supplier_id, s.name
                                          ORDER BY total_spend DESC";
                
                $stmt = $conn->prepare($supplier_performance_sql);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $supplier_result = $stmt->get_result();
                
                while ($row = $supplier_result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?php echo $row['po_count']; ?></td>
                        <td>Rs <?php echo number_format($row['total_spend'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['avg_po_value'], 2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['first_po_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['last_po_date'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <h3 class="report-title">Top Purchased Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>PO Count</th>
                    <th>Total Quantity</th>
                    <th>Total Cost</th>
                    <th>Avg Unit Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $top_products_sql = "SELECT 
                                    p.product_id,
                                    p.sku,
                                    p.name as product_name,
                                    c.name as category_name,
                                    COUNT(DISTINCT poi.po_id) as po_count,
                                    SUM(poi.quantity) as total_quantity,
                                    SUM(poi.quantity * poi.unit_price) as total_cost,
                                    AVG(poi.unit_price) as avg_unit_cost
                                  FROM purchase_order_items poi
                                  JOIN purchase_orders po ON poi.po_id = po.po_id
                                  JOIN products p ON poi.product_id = p.product_id
                                  LEFT JOIN categories c ON p.category_id = c.category_id
                                  WHERE po.order_date BETWEEN ? AND ?
                                  AND po.status NOT IN ('cancelled', 'draft')
                                  GROUP BY p.product_id, p.sku, p.name, c.name
                                  ORDER BY total_quantity DESC
                                  LIMIT 10";
                
                $stmt = $conn->prepare($top_products_sql);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $top_products_result = $stmt->get_result();
                
                while ($row = $top_products_result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['sku']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo $row['po_count']; ?></td>
                        <td><?php echo $row['total_quantity']; ?></td>
                        <td>Rs <?php echo number_format($row['total_cost'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['avg_unit_cost'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <script>
        // Purchasing Trend Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('purchasingTrendChart').getContext('2d');
            const purchasingTrendChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Purchase Orders',
                        data: [5, 7, 6, 8, 9, 10, 8, 7, 6, 8, 9, 11],
                        backgroundColor: 'rgba(108, 117, 125, 0.7)',
                        borderColor: 'rgba(108, 117, 125, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Purchase Amount',
                        data: [12000, 18000, 15000, 20000, 22000, 25000, 21000, 19000, 18000, 22000, 24000, 28000],
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of POs'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Amount (Rs)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rs ' + value.toLocaleString();
                                }
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label === 'Purchase Amount') {
                                        label += ': Rs ' + context.raw.toLocaleString();
                                    } else {
                                        label += ': ' + context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
    </div>

<?php elseif ($report_type === 'profitability'): ?>
    <!-- Profitability Analysis -->
    <div class="report-section">
        <h3 class="report-title">Profitability Overview</h3>
        
        <?php
        // Get profitability data
        $profit_sql = "SELECT 
                        SUM(so.total_amount - so.tax_amount - so.discount_amount) as net_sales,
                        SUM(soi.quantity * p.cost_price) as total_cost,
                        SUM(so.total_amount - so.tax_amount - so.discount_amount - (soi.quantity * p.cost_price)) as gross_profit,
                        (SUM(so.total_amount - so.tax_amount - so.discount_amount - (soi.quantity * p.cost_price)) / 
                         SUM(so.total_amount - so.tax_amount - so.discount_amount)) * 100 as gross_margin
                      FROM sales_orders so
                      JOIN sales_order_items soi ON so.so_id = soi.so_id
                      JOIN products p ON soi.product_id = p.product_id
                      WHERE so.order_date BETWEEN ? AND ?
                      AND so.status NOT IN ('cancelled', 'draft')";
        
        $stmt = $conn->prepare($profit_sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $profit_result = $stmt->get_result();
        $profit_data = $profit_result->fetch_assoc();
        $stmt->close();
        
        $gross_margin = $profit_data['gross_margin'] ?? 0;
        $margin_class = ($gross_margin >= 40) ? 'text-success' : (($gross_margin >= 30) ? 'text-warning' : 'text-danger');
        ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">Net Sales</div>
                <div class="summary-card-value">Rs <?php echo number_format($profit_data['net_sales'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Cost of Goods</div>
                <div class="summary-card-value">Rs <?php echo number_format($profit_data['total_cost'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Gross Profit</div>
                <div class="summary-card-value">Rs <?php echo number_format($profit_data['gross_profit'] ?? 0, 2); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Gross Margin</div>
                <div class="summary-card-value <?php echo $margin_class; ?>"><?php echo number_format($gross_margin, 1); ?>%</div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="profitabilityChart"></canvas>
        </div>
        
        <h3 class="report-title">Profitability by Product</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Sales</th>
                    <th>Cost</th>
                    <th>Profit</th>
                    <th>Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $product_profit_sql = "SELECT 
                                        p.product_id,
                                        p.sku,
                                        p.name as product_name,
                                        c.name as category_name,
                                        SUM(soi.quantity * soi.unit_price) as sales,
                                        SUM(soi.quantity * p.cost_price) as cost,
                                        SUM(soi.quantity * soi.unit_price) - SUM(soi.quantity * p.cost_price) as profit,
                                        (SUM(soi.quantity * soi.unit_price) - SUM(soi.quantity * p.cost_price)) / 
                                        SUM(soi.quantity * soi.unit_price) * 100 as margin
                                      FROM sales_order_items soi
                                      JOIN sales_orders so ON soi.so_id = so.so_id
                                      JOIN products p ON soi.product_id = p.product_id
                                      LEFT JOIN categories c ON p.category_id = c.category_id
                                      WHERE so.order_date BETWEEN ? AND ?
                                      AND so.status NOT IN ('cancelled', 'draft')
                                      GROUP BY p.product_id, p.sku, p.name, c.name
                                      ORDER BY profit DESC";
                
                $stmt = $conn->prepare($product_profit_sql);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $product_profit_result = $stmt->get_result();
                
                while ($row = $product_profit_result->fetch_assoc()):
                    $margin = $row['margin'] ?? 0;
                    $margin_class = ($margin >= 40) ? 'text-success' : (($margin >= 30) ? 'text-warning' : 'text-danger');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['sku']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>Rs <?php echo number_format($row['sales'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['cost'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['profit'], 2); ?></td>
                        <td class="<?php echo $margin_class; ?>"><?php echo number_format($margin, 1); ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <h3 class="report-title">Profitability by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Sales</th>
                    <th>Cost</th>
                    <th>Profit</th>
                    <th>Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $category_profit_sql = "SELECT 
                                        c.category_id,
                                        c.name as category_name,
                                        SUM(soi.quantity * soi.unit_price) as sales,
                                        SUM(soi.quantity * p.cost_price) as cost,
                                        SUM(soi.quantity * soi.unit_price) - SUM(soi.quantity * p.cost_price) as profit,
                                        (SUM(soi.quantity * soi.unit_price) - SUM(soi.quantity * p.cost_price)) / 
                                        SUM(soi.quantity * soi.unit_price) * 100 as margin
                                      FROM sales_order_items soi
                                      JOIN sales_orders so ON soi.so_id = so.so_id
                                      JOIN products p ON soi.product_id = p.product_id
                                      LEFT JOIN categories c ON p.category_id = c.category_id
                                      WHERE so.order_date BETWEEN ? AND ?
                                      AND so.status NOT IN ('cancelled', 'draft')
                                      GROUP BY c.category_id, c.name
                                      ORDER BY profit DESC";
                
                $stmt = $conn->prepare($category_profit_sql);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $category_profit_result = $stmt->get_result();
                
                while ($row = $category_profit_result->fetch_assoc()):
                    $margin = $row['margin'] ?? 0;
                    $margin_class = ($margin >= 40) ? 'text-success' : (($margin >= 30) ? 'text-warning' : 'text-danger');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>Rs <?php echo number_format($row['sales'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['cost'], 2); ?></td>
                        <td>Rs <?php echo number_format($row['profit'], 2); ?></td>
                        <td class="<?php echo $margin_class; ?>"><?php echo number_format($margin, 1); ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <script>
        // Profitability Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('profitabilityChart').getContext('2d');
            const profitabilityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['T-Shirts', 'Jeans', 'Outerwear', 'Accessories'],
                    datasets: [{
                        label: 'Sales',
                        data: [8500, 12500, 4800, 3200],
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Cost',
                        data: [4500, 6800, 2400, 1600],
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Profit',
                        data: [4000, 5700, 2400, 1600],
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rs ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rs ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
    </div>
<?php endif; ?>

<?php 
$conn->close();
?>
</body>
</html>