<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Get PO ID from URL
$po_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$po_id) {
    header("Location: purchase_orders.php");
    exit();
}

// Fetch PO data
$po_stmt = $conn->prepare("SELECT po.*, s.name as supplier_name, s.contact_person, s.email, s.phone, s.address
                         FROM purchase_orders po
                         JOIN suppliers s ON po.supplier_id = s.supplier_id
                         WHERE po.po_id = ?");
$po_stmt->bind_param("i", $po_id);
$po_stmt->execute();
$po_result = $po_stmt->get_result();
$po_data = $po_result->fetch_assoc();
$po_stmt->close();

if (!$po_data) {
    $message = 'Purchase order not found';
    $messageType = 'error';
} else {
    // Fetch PO items
    $items_stmt = $conn->prepare("SELECT poi.*, p.sku, p.name, p.unit_price as retail_price
                                FROM purchase_order_items poi
                                JOIN products p ON poi.product_id = p.product_id
                                WHERE poi.po_id = ?
                                ORDER BY poi.po_item_id");
    $items_stmt->bind_param("i", $po_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $po_items = [];
    while ($row = $items_result->fetch_assoc()) {
        $po_items[] = $row;
    }
    $items_stmt->close();
}

// Handle PO status change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        
        try {
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("UPDATE purchase_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE po_id = ?");
            $stmt->bind_param("si", $new_status, $po_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            $message = 'Purchase order status updated successfully!';
            $messageType = 'success';
            
            // Refresh PO data
            $po_stmt = $conn->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
            $po_stmt->bind_param("i", $po_id);
            $po_stmt->execute();
            $po_result = $po_stmt->get_result();
            $po_data = $po_result->fetch_assoc();
            $po_stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error updating purchase order status: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .po-info {
            flex: 1;
        }
        
        .supplier-info {
            flex: 1;
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
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
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .amount {
            text-align: right;
        }
        
        .totals-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .notes {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-print {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-print:hover {
            background-color: #5a6268;
        }
        
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
        }
        
        .btn-receive {
            background-color: #28a745;
            color: white;
        }
        
        .btn-receive:hover {
            background-color: #218838;
        }
        
        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
        }
        
        .status-form {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($po_data): ?>
        <div class="header">
            <div class="po-info">
                <h2>Purchase Order: <?php echo htmlspecialchars($po_data['po_number']); ?></h2>
                <p><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($po_data['order_date'])); ?></p>
                <p><strong>Expected Delivery:</strong> <?php echo $po_data['expected_delivery_date'] ? date('M d, Y', strtotime($po_data['expected_delivery_date'])) : 'Not specified'; ?></p>
                <p><strong>Status:</strong> <span class="status-badge <?php echo $po_data['status']; ?>"><?php echo ucfirst($po_data['status']); ?></span></p>
            </div>
            
            <div class="supplier-info">
                <h3><?php echo htmlspecialchars($po_data['supplier_name']); ?></h3>
                <p><?php echo htmlspecialchars($po_data['contact_person']); ?></p>
                <p><?php echo htmlspecialchars($po_data['email']); ?></p>
                <p><?php echo htmlspecialchars($po_data['phone']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($po_data['address'])); ?></p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th class="amount">Unit Price (Rs)</th>
                    <th class="amount">Retail Price (Rs)</th>
                    <th class="amount">Total (Rs)</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="amount"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="amount"><?php echo number_format($item['retail_price'], 2); ?></td>
                        <td class="amount"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        <td><?php echo $item['received_quantity']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5">Subtotal</td>
                    <td class="amount">Rs <?php echo number_format($po_data['subtotal'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="5">Tax (10%)</td>
                    <td class="amount">Rs <?php echo number_format($po_data['tax_amount'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="5">Total</td>
                    <td class="amount">Rs <?php echo number_format($po_data['total_amount'], 2); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if ($po_data['notes']): ?>
            <div class="notes">
                <h4>Notes:</h4>
                <p><?php echo nl2br(htmlspecialchars($po_data['notes'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="purchase_orders.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            
            <?php if ($po_data['status'] == 'ordered'): ?>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="status" value="received">
                    <button type="submit" class="btn btn-receive">
                        <i class="fas fa-truck"></i> Mark as Received
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($po_data['status'] != 'cancelled' && $po_data['status'] != 'received'): ?>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this purchase order?');">
                        <i class="fas fa-times"></i> Cancel PO
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Status change form -->
        <div class="status-form">
            <h4>Change Status</h4>
            <form method="POST" action="">
                <input type="hidden" name="update_status" value="1">
                <select name="status">
                    <option value="draft" <?php echo ($po_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="ordered" <?php echo ($po_data['status'] == 'ordered') ? 'selected' : ''; ?>>Ordered</option>
                    <?php if ($po_data['status'] == 'received'): ?>
                        <option value="received" selected>Received</option>
                    <?php endif; ?>
                    <?php if ($po_data['status'] == 'cancelled'): ?>
                        <option value="cancelled" selected>Cancelled</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-submit">Update Status</button>
            </form>
        </div>
        
    <?php else: ?>
        <p>Purchase order not found.</p>
        <a href="purchase_orders.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>
</div>

</body>
</html>

<?php 
$conn->close();
?>