<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Fetch product details
$product = $conn->query("SELECT p.*, c.name as category_name 
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.category_id
                         WHERE p.product_id = $product_id")->fetch_assoc();

if (!$product) {
    die("Product not found");
}

// Fetch transaction history
$transactions = $conn->query("
    SELECT t.*, u.username 
    FROM inventory_transactions t
    LEFT JOIN users u ON t.created_by = u.user_id
    WHERE t.product_id = $product_id
    ORDER BY t.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventory History - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }
        
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
        
        .product-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .product-info h3 {
            margin-top: 0;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
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
    </style>
</head>
<body>

<div class="header-container">
    <h2>Inventory History for <?php echo htmlspecialchars($product['name']); ?></h2>
    <div>
        <a href="inventory.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
    </div>
</div>

<div class="product-info">
    <h3>Product Details</h3>
    <div class="info-row">
        <div class="info-label">SKU:</div>
        <div><?php echo htmlspecialchars($product['sku']); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Category:</div>
        <div><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Current Price:</div>
        <div>Rs <?php echo number_format($product['unit_price'], 2); ?></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Quantity</th>
            <th>Reference</th>
            <th>User</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($transactions->num_rows > 0): ?>
            <?php while ($row = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                    <td>
                        <span class="transaction-type type-<?php echo $row['transaction_type']; ?>">
                            <?php echo ucfirst($row['transaction_type']); ?>
                        </span>
                    </td>
                    <td class="<?php echo ($row['quantity'] > 0) ? 'quantity-positive' : 'quantity-negative'; ?>">
                        <?php echo ($row['quantity'] > 0) ? '+' . $row['quantity'] : $row['quantity']; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['reference_number'] ?? 'Manual'); ?></td>
                    <td><?php echo htmlspecialchars($row['username'] ?? 'System'); ?></td>
                    <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No inventory transactions found for this product</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
<?php $conn->close(); ?>