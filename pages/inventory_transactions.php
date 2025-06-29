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
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_product = isset($_GET['filter_product']) ? $_GET['filter_product'] : '';
$filter_date_from = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filter_date_to = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_type)) {
    $filter_conditions[] = "t.transaction_type = ?";
    $filter_params[] = $filter_type;
    $filter_types .= 's';
}

if (!empty($filter_product)) {
    $filter_conditions[] = "(p.sku LIKE ? OR p.name LIKE ?)";
    $filter_params[] = "%$filter_product%";
    $filter_params[] = "%$filter_product%";
    $filter_types .= 'ss';
}

if (!empty($filter_date_from)) {
    $filter_conditions[] = "t.created_at >= ?";
    $filter_params[] = $filter_date_from;
    $filter_types .= 's';
}

if (!empty($filter_date_to)) {
    $filter_date_to = date('Y-m-d', strtotime($filter_date_to . ' +1 day'));
    $filter_conditions[] = "t.created_at < ?";
    $filter_params[] = $filter_date_to;
    $filter_types .= 's';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of transactions with filters
$count_sql = "SELECT COUNT(*) AS total 
              FROM inventory_transactions t
              JOIN products p ON t.product_id = p.product_id
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

// Fetch paginated transactions
$sql = "SELECT t.transaction_id, t.transaction_type, t.quantity, t.created_at, t.notes,
               p.product_id, p.sku, p.name as product_name,
               u.username as created_by,
               CASE 
                   WHEN t.reference_number IS NOT NULL THEN t.reference_number
                   ELSE 'Manual Adjustment'
               END as reference
        FROM inventory_transactions t
        JOIN products p ON t.product_id = p.product_id
        LEFT JOIN users u ON t.created_by = u.user_id
        $where_clause
        ORDER BY t.created_at DESC
        LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql);

if (!empty($filter_params)) {
    $stmt->bind_param($filter_types, ...$filter_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get transaction types for filter dropdown
$transaction_types = $conn->query("SELECT DISTINCT transaction_type FROM inventory_transactions")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventory Transactions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse styles from inventory.php with minor adjustments */
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
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
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
        
        .transaction-type {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }
        
        .type-purchase {
            background-color: #d4edda;
            color: #155724;
        }
        
        .type-sale {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .type-adjustment {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .type-transfer {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .quantity-positive {
            color: #28a745;
            font-weight: bold;
        }
        
        .quantity-negative {
            color: #dc3545;
            font-weight: bold;
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
    </style>
</head>
<body>

<div class="header-container">
    <h2>Inventory Transactions</h2>
    <div>
        <a href="inventory.php" class="btn btn-primary">
            <i class="fas fa-boxes"></i> Inventory Overview
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Filter Form -->
<div class="filter-container">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter_type">Transaction Type:</label>
                <select id="filter_type" name="filter_type">
                    <option value="">All Types</option>
                    <?php foreach ($transaction_types as $type): ?>
                        <option value="<?php echo $type['transaction_type']; ?>" <?php echo ($filter_type == $type['transaction_type']) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type['transaction_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_product">Product (SKU or Name):</label>
                <input type="text" id="filter_product" name="filter_product" value="<?php echo htmlspecialchars($filter_product); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_date_from">Date From:</label>
                <input type="date" id="filter_date_from" name="filter_date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_date_to">Date To:</label>
                <input type="date" id="filter_date_to" name="filter_date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="transactions.php" class="btn-reset">
                <i class="fas fa-times"></i> Reset Filters
            </a>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Transaction ID</th>
            <th>Type</th>
            <th>Product</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Reference</th>
            <th>User</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                    <td><?php echo $row['transaction_id']; ?></td>
                    <td>
                        <span class="transaction-type type-<?php echo $row['transaction_type']; ?>">
                            <?php echo ucfirst($row['transaction_type']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td class="<?php echo ($row['quantity'] > 0) ? 'quantity-positive' : 'quantity-negative'; ?>">
                        <?php echo ($row['quantity'] > 0) ? '+' . $row['quantity'] : $row['quantity']; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['reference']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                    <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" style="text-align: center;">No transactions found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?page=1<?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_product) ? '&filter_product=' . urlencode($filter_product) : ''; ?><?php echo !empty($filter_date_from) ? '&filter_date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&filter_date_to=' . urlencode($filter_date_to) : ''; ?>">
                &laquo; First
            </a>
            <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_product) ? '&filter_product=' . urlencode($filter_product) : ''; ?><?php echo !empty($filter_date_from) ? '&filter_date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&filter_date_to=' . urlencode($filter_date_to) : ''; ?>">
                &lsaquo; Previous
            </a>
        <?php else: ?>
            <span class="disabled">&laquo; First</span>
            <span class="disabled">&lsaquo; Previous</span>
        <?php endif; ?>

        <?php 
        // Show page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            echo '<span>...</span>';
        }
        
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_product) ? '&filter_product=' . urlencode($filter_product) : ''; ?><?php echo !empty($filter_date_from) ? '&filter_date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&filter_date_to=' . urlencode($filter_date_to) : ''; ?>" <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>>
                <?php echo $i; ?>
            </a>
        <?php endfor;
        
        if ($end_page < $total_pages) {
            echo '<span>...</span>';
        }
        ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_product) ? '&filter_product=' . urlencode($filter_product) : ''; ?><?php echo !empty($filter_date_from) ? '&filter_date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&filter_date_to=' . urlencode($filter_date_to) : ''; ?>">
                Next &rsaquo;
            </a>
            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_product) ? '&filter_product=' . urlencode($filter_product) : ''; ?><?php echo !empty($filter_date_from) ? '&filter_date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&filter_date_to=' . urlencode($filter_date_to) : ''; ?>">
                Last &raquo;
            </a>
        <?php else: ?>
            <span class="disabled">Next &rsaquo;</span>
            <span class="disabled">Last &raquo;</span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php 
$stmt->close();
$conn->close();
?>

</body>
</html>