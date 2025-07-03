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
$filter_mo_number = isset($_GET['filter_mo_number']) ? $_GET['filter_mo_number'] : '';
$filter_product = isset($_GET['filter_product']) ? $_GET['filter_product'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_date_from = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filter_date_to = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// Fetch products for filter dropdown
$products = [];
$prod_result = $conn->query("SELECT product_id, name FROM products WHERE manufacturing_cost > 0 ORDER BY name");
while ($row = $prod_result->fetch_assoc()) {
    $products[$row['product_id']] = $row['name'];
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_mo_number)) {
    $filter_conditions[] = "mo.mo_number LIKE ?";
    $filter_params[] = "%$filter_mo_number%";
    $filter_types .= 's';
}

if (!empty($filter_product)) {
    $filter_conditions[] = "mo.product_id = ?";
    $filter_params[] = $filter_product;
    $filter_types .= 'i';
}

if (!empty($filter_status)) {
    $filter_conditions[] = "mo.status = ?";
    $filter_params[] = $filter_status;
    $filter_types .= 's';
}

if (!empty($filter_date_from)) {
    $filter_conditions[] = "mo.start_date >= ?";
    $filter_params[] = $filter_date_from;
    $filter_types .= 's';
}

if (!empty($filter_date_to)) {
    $filter_conditions[] = "mo.start_date <= ?";
    $filter_params[] = $filter_date_to;
    $filter_types .= 's';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of MOs with filters
$count_sql = "SELECT COUNT(*) AS total FROM manufacturing_orders mo $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated MOs with filters
$sql_mos = "SELECT mo.*, p.name as product_name, p.sku
           FROM manufacturing_orders mo
           JOIN products p ON mo.product_id = p.product_id
           $where_clause
           ORDER BY mo.start_date DESC, mo.mo_id DESC
           LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql_mos);

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
    <title>Manufacturing Orders</title>
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
        
        .btn-create {
            background-color: #28a745;
            color: white;
        }
        
        .btn-create:hover {
            background-color: #218838;
        }
        
        .btn-view {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background-color: #138496;
        }
        
        .btn-complete {
            background-color: #007bff;
            color: white;
        }
        
        .btn-complete:hover {
            background-color: #0056b3;
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
        
        .planned {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .in_progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
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
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Manufacturing Orders</h2>
    <div>
        <a href="create_manufacturing_order.php" class="btn btn-create">
            <i class="fas fa-plus"></i> New Manufacturing Order
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="message success">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Filter Form -->
<div class="filter-container">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter_mo_number">MO Number:</label>
                <input type="text" id="filter_mo_number" name="filter_mo_number" value="<?php echo htmlspecialchars($filter_mo_number); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_product">Product:</label>
                <select id="filter_product" name="filter_product">
                    <option value="">All Products</option>
                    <?php foreach ($products as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_product == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_status">Status:</label>
                <select id="filter_status" name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="planned" <?php echo ($filter_status === 'planned') ? 'selected' : ''; ?>>Planned</option>
                    <option value="in_progress" <?php echo ($filter_status === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo ($filter_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($filter_status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>
        
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter_date_from">From Date:</label>
                <input type="date" id="filter_date_from" name="filter_date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_date_to">To Date:</label>
                <input type="date" id="filter_date_to" name="filter_date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
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
            <th>MO Number</th>
            <th>Product</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Start Date</th>
            <th>Status</th>
            <th class="amount">Total Cost</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['mo_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['start_date'] ? date('M d, Y', strtotime($row['start_date'])) : '-'; ?></td>
                    <td>
                        <span class="status <?php echo str_replace(' ', '_', $row['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                        </span>
                    </td>
                    <td class="amount">Rs <?php echo number_format($row['total_cost'], 2); ?></td>
                    <td class="action-buttons">
                        <a href="manufacturing_order_details.php?id=<?php echo $row['mo_id']; ?>" class="btn btn-view">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php if ($row['status'] == 'planned' || $row['status'] == 'in_progress'): ?>
                            <a href="manufacturing_order_details.php?id=<?php echo $row['mo_id']; ?>&action=complete" 
                               class="btn btn-complete" 
                               onclick="return confirm('Complete this manufacturing order? This will deduct raw materials and add finished goods to inventory.');">
                                <i class="fas fa-check"></i> Complete
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No manufacturing orders found</td>
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
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) echo '<span>...</span>';
    
    for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
           <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor;
    
    if ($end_page < $total_pages) echo '<span>...</span>';
    ?>
    
    <?php if ($current_page < $total_pages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next &rsaquo;</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">Last &raquo;</a>
    <?php else: ?>
        <span class="disabled">Next &rsaquo;</span>
        <span class="disabled">Last &raquo;</span>
    <?php endif; ?>
</div>

<?php 
$conn->close();
?>
