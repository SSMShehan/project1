<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Check if customer ID is provided
if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = (int)$_GET['id'];

// Fetch customer details
$customer_sql = "SELECT * FROM customers WHERE customer_id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();

if ($customer_result->num_rows === 0) {
    header("Location: customers.php");
    exit();
}

$customer = $customer_result->fetch_assoc();
$customer_stmt->close();

// Fetch customer's recent orders
$orders_sql = "SELECT so_id, order_number, order_date, status, total_amount 
              FROM sales_orders 
              WHERE customer_id = ? 
              ORDER BY order_date DESC 
              LIMIT 5";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$recent_orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Customer Details - <?php echo htmlspecialchars($customer['name']); ?></title>
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
        
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
        }
        
        .customer-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 200px;
        }
        
        .detail-value {
            flex: 1;
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
        
        .pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .recent-orders {
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        .order-status {
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
    </style>
</head>
<body>

<div class="header-container">
    <h2>Customer: <?php echo htmlspecialchars($customer['name']); ?></h2>
    <div>
        <a href="customers.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
        <a href="edit_customer.php?id=<?php echo $customer_id; ?>" class="btn btn-edit" style="margin-left: 10px;">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>

<div class="customer-details">
    <div class="detail-row">
        <span class="detail-label">Status:</span>
        <span class="detail-value">
            <span class="status <?php echo $customer['status']; ?>">
                <?php echo ucfirst($customer['status']); ?>
            </span>
        </span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Contact Person:</span>
        <span class="detail-value"><?php echo htmlspecialchars($customer['contact_person']); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Email:</span>
        <span class="detail-value"><?php echo htmlspecialchars($customer['email']); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Phone:</span>
        <span class="detail-value"><?php echo htmlspecialchars($customer['phone']); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Address:</span>
        <span class="detail-value"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Tax ID:</span>
        <span class="detail-value"><?php echo htmlspecialchars($customer['tax_id']); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Credit Limit:</span>
        <span class="detail-value">Rs <?php echo number_format($customer['credit_limit'], 2); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Payment Terms:</span>
        <span class="detail-value"><?php echo htmlspecialchars($customer['payment_terms']); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Created At:</span>
        <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($customer['created_at'])); ?></span>
    </div>
    
    <div class="detail-row">
        <span class="detail-label">Last Updated:</span>
        <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($customer['updated_at'])); ?></span>
    </div>
</div>

<div class="recent-orders">
    <h3>Recent Orders</h3>
    
    <?php if (count($recent_orders) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td>
                            <span class="order-status <?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <a href="view_sales_order.php?id=<?php echo $order['so_id']; ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 15px;">
            <a href="sales_orders.php?filter_customer=<?php echo $customer_id; ?>">View all orders for this customer</a>
        </div>
    <?php else: ?>
        <p>No recent orders found for this customer.</p>
    <?php endif; ?>
</div>

<?php 
$conn->close();
?>