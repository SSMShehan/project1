<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Filtering setup
$filter_sku = isset($_GET['filter_sku']) ? $_GET['filter_sku'] : '';
$filter_name = isset($_GET['filter_name']) ? $_GET['filter_name'] : '';
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_stock = isset($_GET['filter_stock']) ? $_GET['filter_stock'] : '';

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $adjustment = (int)$_POST['adjustment'];
    $notes = $_POST['notes'];
    $user_id = $_SESSION['user_id'] ?? 1; // Default to admin if not set
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update inventory
        $stmt = $conn->prepare("UPDATE inventory SET quantity_on_hand = quantity_on_hand + ? WHERE product_id = ?");
        $stmt->bind_param("ii", $adjustment, $product_id);
        $stmt->execute();
        
        // Record transaction
        $stmt = $conn->prepare("INSERT INTO inventory_transactions 
                              (product_id, transaction_type, quantity, notes, created_by) 
                              VALUES (?, 'adjustment', ?, ?, ?)");
        $stmt->bind_param("iisi", $product_id, $adjustment, $notes, $user_id);
        $stmt->execute();
        
        $conn->commit();
        $message = 'Inventory adjusted successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error adjusting inventory: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
while ($row = $cat_result->fetch_assoc()) {
    $categories[$row['category_id']] = $row['name'];
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_sku)) {
    $filter_conditions[] = "p.sku LIKE ?";
    $filter_params[] = "%$filter_sku%";
    $filter_types .= 's';
}

if (!empty($filter_name)) {
    $filter_conditions[] = "p.name LIKE ?";
    $filter_params[] = "%$filter_name%";
    $filter_types .= 's';
}

if (!empty($filter_category)) {
    $filter_conditions[] = "p.category_id = ?";
    $filter_params[] = $filter_category;
    $filter_types .= 'i';
}

if ($filter_status !== '') {
    $filter_conditions[] = "p.is_active = ?";
    $filter_params[] = ($filter_status === 'active') ? 1 : 0;
    $filter_types .= 'i';
}

if ($filter_stock === 'low') {
    $filter_conditions[] = "(i.quantity_on_hand - i.quantity_allocated) <= p.reorder_level";
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of records with filters
$count_sql = "SELECT COUNT(*) AS total 
              FROM products p 
              JOIN inventory i ON p.product_id = i.product_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated inventory data
$sql = "SELECT p.product_id, p.sku, p.name, c.name as category_name, 
               p.unit_price, p.cost_price, p.reorder_level, p.unit_of_measure, p.is_active,
               i.quantity_on_hand, i.quantity_allocated,
               (i.quantity_on_hand - i.quantity_allocated) as available_quantity,
               p.cost_price * i.quantity_on_hand as inventory_value
        FROM products p
        JOIN inventory i ON p.product_id = i.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        $where_clause
        ORDER BY p.name
        LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql);

if (!empty($filter_params)) {
    $stmt->bind_param($filter_types, ...$filter_params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventory Overview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            width: 80%;
            max-width: 500px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        textarea {
            min-height: 100px;
        }
        
        .message {
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }
        
        .active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .stock-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }
        
        .stock-ok {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-critical {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #333;
            border-radius: 4px;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .disabled {
            color: #aaa;
            pointer-events: none;
            cursor: default;
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
        
        .btn-filter {
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-filter:hover {
            background-color: #5a6268;
        }
        
        .btn-reset {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-reset:hover {
            background-color: #c82333;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            margin-top: 0;
            color: #6c757d;
            font-size: 1rem;
        }
        
        .summary-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .summary-card .label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Inventory Overview</h2>
    <div>
        <a href="inventory_transactions.php" class="btn btn-info">
            <i class="fas fa-exchange-alt"></i> View Transactions
        </a>
        <a href="inventory_valuation.php" class="btn btn-success" style="margin-left: 10px;">
            <i class="fas fa-chart-bar"></i> Valuation Report
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Inventory Summary Cards -->
<div class="summary-cards">
    <?php
    // Get total inventory value
    $total_value = $conn->query("SELECT calculate_inventory_value() as value")->fetch_assoc()['value'];

    
    // Get low stock count
    $low_stock = $conn->query("SELECT COUNT(*) as count FROM vw_products_to_reorder")->fetch_assoc()['count'];
    
    // Get total products
    $total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch_assoc()['count'];
    
    // Get out of stock items
    $out_of_stock = $conn->query("SELECT COUNT(*) as count FROM inventory i JOIN products p ON i.product_id = p.product_id WHERE i.quantity_on_hand = 0 AND p.is_active = 1")->fetch_assoc()['count'];
    ?>
    
    <div class="summary-card">
        <h3>Total Inventory Value</h3>
        <div class="value">Rs <?php echo number_format($total_value, 2); ?></div>
        <div class="label">Current stock valuation</div>
    </div>
    
    <div class="summary-card">
        <h3>Total Products</h3>
        <div class="value"><?php echo $total_products; ?></div>
        <div class="label">Active products in system</div>
    </div>
    
    <div class="summary-card">
        <h3>Low Stock Items</h3>
        <div class="value"><?php echo $low_stock; ?></div>
        <div class="label">Below reorder level</div>
    </div>
    
    <div class="summary-card">
        <h3>Out of Stock</h3>
        <div class="value"><?php echo $out_of_stock; ?></div>
        <div class="label">Items with zero stock</div>
    </div>
</div>

<!-- Filter Form -->
<div class="filter-container">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter_sku">SKU:</label>
                <input type="text" id="filter_sku" name="filter_sku" value="<?php echo htmlspecialchars($filter_sku); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_name">Product Name:</label>
                <input type="text" id="filter_name" name="filter_name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_category">Category:</label>
                <select id="filter_category" name="filter_category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_category == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_status">Status:</label>
                <select id="filter_status" name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo ($filter_status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($filter_status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_stock">Stock Level:</label>
                <select id="filter_stock" name="filter_stock">
                    <option value="">All</option>
                    <option value="low" <?php echo ($filter_stock === 'low') ? 'selected' : ''; ?>>Low Stock</option>
                </select>
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="?" class="btn-reset">
                <i class="fas fa-times"></i> Reset
            </a>
        </div>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Category</th>
            <th>On Hand</th>
            <th>Allocated</th>
            <th>Available</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                $available = $row['available_quantity'];
                $stock_status = '';
                
                if ($available <= 0) {
                    $stock_status = 'stock-critical';
                    $status_text = 'Out of Stock';
                } elseif ($available <= $row['reorder_level']) {
                    $stock_status = 'stock-low';
                    $status_text = 'Low Stock';
                } else {
                    $stock_status = 'stock-ok';
                    $status_text = 'In Stock';
                }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?php echo $row['quantity_on_hand']; ?></td>
                    <td><?php echo $row['quantity_allocated']; ?></td>
                    <td><?php echo $available; ?></td>
                    <td><?php echo $row['reorder_level']; ?></td>
                    <td>
                        <span class="stock-status <?php echo $stock_status; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button class="btn btn-warning" onclick="openAdjustModal(
                            <?php echo $row['product_id']; ?>,
                            '<?php echo addslashes($row['sku']); ?>',
                            '<?php echo addslashes($row['name']); ?>',
                            <?php echo $row['quantity_on_hand']; ?>
                        )">
                            <i class="fas fa-adjust"></i> Adjust
                        </button>
                        <a href="product_inventory_history.php?id=<?php echo $row['product_id']; ?>" class="btn btn-info">
                            <i class="fas fa-history"></i> History
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">No inventory items found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination Navigation -->
<div class="pagination">
    <?php if ($current_page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">&laquo; First</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">&lsaquo; Prev</a>
    <?php else: ?>
        <span class="disabled">&laquo; First</span>
        <span class="disabled">&lsaquo; Prev</span>
    <?php endif; ?>
    
    <?php
    // Show page numbers (limited to 5 around current page)
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        echo '<span>...</span>';
    }
    
    for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor;
    
    if ($end_page < $total_pages) {
        echo '<span>...</span>';
    }
    ?>
    
    <?php if ($current_page < $total_pages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next &rsaquo;</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">Last &raquo;</a>
    <?php else: ?>
        <span class="disabled">Next &rsaquo;</span>
        <span class="disabled">Last &raquo;</span>
    <?php endif; ?>
</div>

<!-- Stock Adjustment Modal -->
<div id="adjustModal" class="modal">
    <div class="modal-content">
        <h3>Adjust Stock Level</h3>
        <form method="POST" action="">
            <input type="hidden" name="adjust_stock" value="1">
            <input type="hidden" id="adjustProductId" name="product_id">
            
            <div class="form-group">
                <label>SKU:</label>
                <p id="adjustSku" style="font-weight: bold;"></p>
            </div>
            
            <div class="form-group">
                <label>Product Name:</label>
                <p id="adjustProductName" style="font-weight: bold;"></p>
            </div>
            
            <div class="form-group">
                <label>Current Stock:</label>
                <p id="adjustCurrentStock" style="font-weight: bold;"></p>
            </div>
            
            <div class="form-group">
                <label for="adjustment">Adjustment:</label>
                <input type="number" id="adjustment" name="adjustment" required>
                <small>Use positive number to add stock, negative to remove</small>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes:</label>
                <textarea id="notes" name="notes" required></textarea>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-primary">Save Adjustment</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Function to open adjust modal with data
function openAdjustModal(productId, sku, name, currentStock) {
    document.getElementById('adjustProductId').value = productId;
    document.getElementById('adjustSku').textContent = sku;
    document.getElementById('adjustProductName').textContent = name;
    document.getElementById('adjustCurrentStock').textContent = currentStock;
    document.getElementById('adjustModal').style.display = 'flex';
}

// Function to close any modal
function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        closeModal();
    }
}
</script>

<?php 
$conn->close();
?>