<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = ''; // 'success' or 'error'

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Filtering setup
$filter_order_number = isset($_GET['filter_order_number']) ? $_GET['filter_order_number'] : '';
$filter_customer = isset($_GET['filter_customer']) ? $_GET['filter_customer'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_date_from = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filter_date_to = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_order'])) {
        // Handle order creation
        $customer_id = $_POST['customer_id'];
        $order_date = $_POST['order_date'];
        $required_date = $_POST['required_date'];
        $notes = $_POST['notes'];
        
        // Generate order number
        $order_number = 'SO-' . date('Ymd-His');
        
        $sql = "INSERT INTO sales_orders (order_number, customer_id, order_date, required_date, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sissii", $order_number, $customer_id, $order_date, $required_date, $notes, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $so_id = $stmt->insert_id;
                $message = 'Sales order created successfully! Order Number: ' . $order_number;
                $messageType = 'success';
                // Redirect to edit page to add items
                header("Location: edit_sales_order.php?id=$so_id");
                exit();
            } else {
                $message = 'Error creating sales order: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['process_shipment'])) {
        // Handle order shipment
        $so_id = $_POST['so_id'];
        
        try {
            $conn->autocommit(FALSE);
            
            // Call the stored procedure to process shipment
            $stmt = $conn->prepare("CALL ship_sales_order(?, ?)");
            $stmt->bind_param("ii", $so_id, $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                $message = 'Order shipped successfully!';
                $messageType = 'success';
            } else {
                $conn->rollback();
                $message = 'Error processing shipment: No rows affected';
                $messageType = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error processing shipment: ' . $e->getMessage();
            $messageType = 'error';
        } finally {
            $conn->autocommit(TRUE);
        }
    }
}

// Fetch customers for dropdown
$customers = [];
$cust_result = $conn->query("SELECT customer_id, name FROM customers WHERE status = 'active' ORDER BY name");
while ($row = $cust_result->fetch_assoc()) {
    $customers[$row['customer_id']] = $row['name'];
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_order_number)) {
    $filter_conditions[] = "so.order_number LIKE ?";
    $filter_params[] = "%$filter_order_number%";
    $filter_types .= 's';
}

if (!empty($filter_customer)) {
    $filter_conditions[] = "so.customer_id = ?";
    $filter_params[] = $filter_customer;
    $filter_types .= 'i';
}

if (!empty($filter_status)) {
    $filter_conditions[] = "so.status = ?";
    $filter_params[] = $filter_status;
    $filter_types .= 's';
}

if (!empty($filter_date_from)) {
    $filter_conditions[] = "so.order_date >= ?";
    $filter_params[] = $filter_date_from;
    $filter_types .= 's';
}

if (!empty($filter_date_to)) {
    $filter_conditions[] = "so.order_date <= ?";
    $filter_params[] = $filter_date_to;
    $filter_types .= 's';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of orders with filters
$count_sql = "SELECT COUNT(*) AS total FROM sales_orders so $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated orders with customer names and filters
$sql_orders = "SELECT so.*, c.name as customer_name 
              FROM sales_orders so 
              JOIN customers c ON so.customer_id = c.customer_id
              $where_clause
              ORDER BY so.order_date DESC, so.so_id DESC
              LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql_orders);

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
    <title>Sales Orders Management</title>
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
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
        }
        
        .btn-view {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background-color: #138496;
        }
        
        .btn-ship {
            background-color: #6f42c1;
            color: white;
        }
        
        .btn-ship:hover {
            background-color: #5a3d8e;
        }
        
        .btn-submit {
            background-color: #007bff;
            color: white;
        }
        
        .btn-submit:hover {
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
            max-width: 600px;
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
        
        .draft {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .confirmed {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .delivered {
            background-color: #cce5ff;
            color: #004085;
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
    <h2>Sales Orders Management</h2>
    <div>
        <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
            <i class="fas fa-plus"></i> New Sales Order
        </button>
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
                <label for="filter_order_number">Order Number:</label>
                <input type="text" id="filter_order_number" name="filter_order_number" value="<?php echo htmlspecialchars($filter_order_number); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_customer">Customer:</label>
                <select id="filter_customer" name="filter_customer">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_customer == $id) ? 'selected' : ''; ?>>
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
                    <option value="confirmed" <?php echo ($filter_status === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="shipped" <?php echo ($filter_status === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo ($filter_status === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
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
            <th>Order #</th>
            <th>Customer</th>
            <th>Order Date</th>
            <th>Required Date</th>
            <th>Status</th>
            <th>Total Amount</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                    <td><?php echo $row['required_date'] ? date('M d, Y', strtotime($row['required_date'])) : '-'; ?></td>
                    <td>
                        <span class="status <?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>Rs <?php echo number_format($row['total_amount'], 2); ?></td>
                    <td class="action-buttons">
                        <a href="view_sales_order.php?id=<?php echo $row['so_id']; ?>" class="btn btn-view">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="edit_sales_order.php?id=<?php echo $row['so_id']; ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if ($row['status'] == 'confirmed'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="process_shipment" value="1">
                                <input type="hidden" name="so_id" value="<?php echo $row['so_id']; ?>">
                                <button type="submit" class="btn btn-ship" onclick="return confirm('Are you sure you want to mark this order as shipped?');">
                                    <i class="fas fa-truck"></i> Ship
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No sales orders found</td>
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

<!-- Create Order Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <h3>Create New Sales Order</h3>
        <form method="POST" action="">
            <input type="hidden" name="create_order" value="1">
            
            <div class="form-group">
                <label for="customer_id">Customer:</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="order_date">Order Date:</label>
                <input type="date" id="order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="required_date">Required Date:</label>
                <input type="date" id="required_date" name="required_date">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes:</label>
                <textarea id="notes" name="notes"></textarea>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Create</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
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