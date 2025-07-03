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
$filter_rmp_number = isset($_GET['filter_rmp_number']) ? $_GET['filter_rmp_number'] : '';
$filter_supplier = isset($_GET['filter_supplier']) ? $_GET['filter_supplier'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_date_from = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filter_date_to = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// Handle RMPO receiving
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receive_rmp'])) {
    $rmp_id = $_POST['rmp_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $conn->begin_transaction();
        
        // Fetch RMPO items
        $items_stmt = $conn->prepare("SELECT * FROM raw_material_purchase_order_items WHERE rmp_id = ?");
        $items_stmt->bind_param("i", $rmp_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $rmp_items = [];
        while ($row = $items_result->fetch_assoc()) {
            $rmp_items[] = $row;
        }
        $items_stmt->close();
        
        // Update inventory and transactions
        foreach ($rmp_items as $item) {
            // Update raw material inventory
            $update_stmt = $conn->prepare("UPDATE raw_material_inventory 
                                        SET quantity_on_hand = quantity_on_hand + ?
                                        WHERE material_id = ?");
            $update_stmt->bind_param("di", $item['quantity'], $item['material_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Record raw material transaction
            $trans_stmt = $conn->prepare("INSERT INTO raw_material_transactions 
                                        (material_id, transaction_type, quantity, reference_id, reference_number, created_by) 
                                        VALUES (?, 'purchase', ?, ?, ?, ?)");
            $rmp_number = $conn->query("SELECT rmp_number FROM raw_material_purchase_orders WHERE rmp_id = $rmp_id")->fetch_assoc()['rmp_number'];
            $trans_stmt->bind_param("idisi", $item['material_id'], $item['quantity'], $rmp_id, $rmp_number, $user_id);
            $trans_stmt->execute();
            $trans_stmt->close();
        }
        
        // Update RMPO status
        $stmt = $conn->prepare("UPDATE raw_material_purchase_orders SET status = 'received', updated_at = CURRENT_TIMESTAMP WHERE rmp_id = ?");
        $stmt->bind_param("i", $rmp_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        $message = 'Raw material purchase order received successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error receiving raw material purchase order: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch suppliers for dropdown
$suppliers = [];
$sup_result = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
while ($row = $sup_result->fetch_assoc()) {
    $suppliers[$row['supplier_id']] = $row['name'];
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_rmp_number)) {
    $filter_conditions[] = "rmpo.rmp_number LIKE ?";
    $filter_params[] = "%$filter_rmp_number%";
    $filter_types .= 's';
}

if (!empty($filter_supplier)) {
    $filter_conditions[] = "rmpo.supplier_id = ?";
    $filter_params[] = $filter_supplier;
    $filter_types .= 'i';
}

if (!empty($filter_status)) {
    $filter_conditions[] = "rmpo.status = ?";
    $filter_params[] = $filter_status;
    $filter_types .= 's';
}

if (!empty($filter_date_from)) {
    $filter_conditions[] = "rmpo.order_date >= ?";
    $filter_params[] = $filter_date_from;
    $filter_types .= 's';
}

if (!empty($filter_date_to)) {
    $filter_conditions[] = "rmpo.order_date <= ?";
    $filter_params[] = $filter_date_to;
    $filter_types .= 's';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of RMPOs with filters
$count_sql = "SELECT COUNT(*) AS total FROM raw_material_purchase_orders rmpo $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated RMPOs with supplier names and filters
$sql_rmpos = "SELECT rmpo.*, s.name as supplier_name 
             FROM raw_material_purchase_orders rmpo 
             JOIN suppliers s ON rmpo.supplier_id = s.supplier_id
             $where_clause
             ORDER BY rmpo.order_date DESC, rmpo.rmp_id DESC
             LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql_rmpos);

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
    <title>Raw Material Purchase Orders</title>
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
        
        .btn-receive {
            background-color: #007bff;
            color: white;
        }
        
        .btn-receive:hover {
            background-color: #0056b3;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #5a6268;
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
            max-width: 800px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
        
        .draft {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .ordered {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .received {
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
    <h2>Raw Material Purchase Orders</h2>
    <div>
        <a href="create_raw_material_purchase_order.php" class="btn btn-create">
            <i class="fas fa-plus"></i> New Purchase Order
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
                <label for="filter_rmp_number">RMPO Number:</label>
                <input type="text" id="filter_rmp_number" name="filter_rmp_number" value="<?php echo htmlspecialchars($filter_rmp_number); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_supplier">Supplier:</label>
                <select id="filter_supplier" name="filter_supplier">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_supplier == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_status">Status:</label>
                <select id="filter_status" name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="draft" <?php echo ($filter_status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="ordered" <?php echo ($filter_status === 'ordered') ? 'selected' : ''; ?>>Ordered</option>
                    <option value="received" <?php echo ($filter_status === 'received') ? 'selected' : ''; ?>>Received</option>
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
            <th>RMPO Number</th>
            <th>Supplier</th>
            <th>Order Date</th>
            <th>Expected Delivery</th>
            <th>Status</th>
            <th class="amount">Total Amount</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['rmp_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                    <td><?php echo $row['expected_delivery_date'] ? date('M d, Y', strtotime($row['expected_delivery_date'])) : '-'; ?></td>
                    <td>
                        <span class="status <?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td class="amount">Rs <?php echo number_format($row['total_amount'], 2); ?></td>
                    <td class="action-buttons">
                        <a href="raw_material_purchase_order_details.php?id=<?php echo $row['rmp_id']; ?>" class="btn btn-view">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php if ($row['status'] == 'ordered'): ?>
                            <button class="btn btn-receive" onclick="openReceiveModal(<?php echo $row['rmp_id']; ?>, '<?php echo addslashes($row['rmp_number']); ?>')">
                                <i class="fas fa-truck"></i> Receive
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No raw material purchase orders found</td>
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

<!-- Receive RMPO Modal -->
<div id="receiveModal" class="modal">
    <div class="modal-content">
        <h3>Receive Raw Material Purchase Order</h3>
        <p id="receiveModalText">Are you sure you want to receive this purchase order?</p>
        <form method="POST" action="">
            <input type="hidden" name="receive_rmp" value="1">
            <input type="hidden" id="receiveRmpId" name="rmp_id">
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-receive">Confirm Receive</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Function to open receive modal
function openReceiveModal(rmpId, rmpNumber) {
    document.getElementById('receiveRmpId').value = rmpId;
    document.getElementById('receiveModalText').textContent = `Are you sure you want to receive raw material purchase order ${rmpNumber}?`;
    document.getElementById('receiveModal').style.display = 'flex';
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
