<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: sales_orders.php");
    exit();
}

$so_id = (int)$_GET['id'];

// Fetch order details
$order_sql = "SELECT so.*, c.name as customer_name, c.email, c.phone, c.address 
             FROM sales_orders so 
             JOIN customers c ON so.customer_id = c.customer_id
             WHERE so.so_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $so_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header("Location: sales_orders.php");
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Fetch order items
$items_sql = "SELECT soi.*, p.name as product_name, p.sku, p.unit_price as list_price 
             FROM sales_order_items soi
             JOIN products p ON soi.product_id = p.product_id
             WHERE soi.so_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $so_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Sales Order #<?php echo $order['order_number']; ?></title>
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
        
        .btn-print {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-print:hover {
            background-color: #138496;
        }
        
        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        
        .info-value {
            flex: 1;
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
        
        .order-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-label {
            font-weight: bold;
        }
        
        .summary-value {
            text-align: right;
        }
        
        .total-row {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .notes-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .notes-content {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Sales Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
    <div>
        <a href="sales_orders.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <button class="btn btn-print" onclick="window.print()" style="margin-left: 10px;">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<div class="customer-info">
    <div class="info-row">
        <span class="info-label">Customer:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Email:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Phone:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Address:</span>
        <span class="info-value"><?php echo nl2br(htmlspecialchars($order['address'])); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Order Date:</span>
        <span class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Required Date:</span>
        <span class="info-value"><?php echo $order['required_date'] ? date('M d, Y', strtotime($order['required_date'])) : '-'; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Shipped Date:</span>
        <span class="info-value"><?php echo $order['shipped_date'] ? date('M d, Y', strtotime($order['shipped_date'])) : '-'; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Status:</span>
        <span class="info-value">
            <span class="status <?php echo $order['status']; ?>">
                <?php echo ucfirst($order['status']); ?>
            </span>
        </span>
    </div>
</div>

<h3>Order Items</h3>

<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($order_items) > 0): ?>
            <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>Rs <?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>Rs <?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No items in this order</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="order-summary">
    <div class="summary-row">
        <span class="summary-label">Subtotal:</span>
        <span class="summary-value">Rs <?php echo number_format($order['subtotal'], 2); ?></span>
    </div>
    <div class="summary-row">
        <span class="summary-label">Tax (<?php echo ($order['subtotal'] != 0 ? round($order['tax_amount'] / $order['subtotal'] * 100, 2) : 0); ?>%):</span>
        <span class="summary-value">Rs <?php echo number_format($order['tax_amount'], 2); ?></span>
    </div>
    <div class="summary-row total-row">
        <span class="summary-label">Total Amount:</span>
        <span class="summary-value">Rs <?php echo number_format($order['total_amount'], 2); ?></span>
    </div>
</div>

<?php if (!empty($order['notes'])): ?>
    <div class="notes-section">
        <div class="notes-label">Order Notes:</div>
        <div class="notes-content"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
    </div>
<?php endif; ?>

<?php 
$conn->close();
?>