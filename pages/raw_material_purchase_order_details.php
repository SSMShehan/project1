<?php

include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Get RMPO ID from URL
$rmp_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$rmp_id) {
    header("Location: raw_material_purchase_orders.php");
    exit();
}

// Fetch RMPO data
$rmp_stmt = $conn->prepare("SELECT rmpo.*, s.name as supplier_name, s.contact_person, s.email, s.phone, s.address
                         FROM raw_material_purchase_orders rmpo
                         JOIN suppliers s ON rmpo.supplier_id = s.supplier_id
                         WHERE rmpo.rmp_id = ?");
$rmp_stmt->bind_param("i", $rmp_id);
$rmp_stmt->execute();
$rmp_result = $rmp_stmt->get_result();
$rmp_data = $rmp_result->fetch_assoc();
$rmp_stmt->close();

if (!$rmp_data) {
    $message = 'Raw material purchase order not found';
    $messageType = 'error';
} else {
    // Fetch RMPO items
    $items_stmt = $conn->prepare("SELECT rmpi.*, rm.sku, rm.name, rm.unit_of_measure
                                FROM raw_material_purchase_order_items rmpi
                                JOIN raw_materials rm ON rmpi.material_id = rm.material_id
                                WHERE rmpi.rmp_id = ?
                                ORDER BY rmpi.rmp_item_id");
    $items_stmt->bind_param("i", $rmp_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $rmp_items = [];
    while ($row = $items_result->fetch_assoc()) {
        $rmp_items[] = $row;
    }
    $items_stmt->close();
}

// Handle RMPO status change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        
        try {
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("UPDATE raw_material_purchase_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE rmp_id = ?");
            $stmt->bind_param("si", $new_status, $rmp_id);
            $stmt->execute();
            $stmt->close();
            
            // If status changed to received, update inventory
            if ($new_status == 'received') {
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
                    $user_id = $_SESSION['user_id'];
                    $trans_stmt->bind_param("idisi", $item['material_id'], $item['quantity'], $rmp_id, $rmp_data['rmp_number'], $user_id);
                    $trans_stmt->execute();
                    $trans_stmt->close();
                }
            }
            
            $conn->commit();
            
            $message = 'Raw material purchase order status updated successfully!';
            $messageType = 'success';
            
            // Refresh RMPO data
            $rmp_stmt = $conn->prepare("SELECT * FROM raw_material_purchase_orders WHERE rmp_id = ?");
            $rmp_stmt->bind_param("i", $rmp_id);
            $rmp_stmt->execute();
            $rmp_result = $rmp_stmt->get_result();
            $rmp_data = $rmp_result->fetch_assoc();
            $rmp_stmt->close();
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
    <title>Raw Material Purchase Order Details</title>
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
        
        .rmp-info {
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
    
    <?php if ($rmp_data): ?>
        <div class="header">
            <div class="rmp-info">
                <h2>Raw Material Purchase Order: <?php echo htmlspecialchars($rmp_data['rmp_number']); ?></h2>
                <p><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($rmp_data['order_date'])); ?></p>
                <p><strong>Expected Delivery:</strong> <?php echo $rmp_data['expected_delivery_date'] ? date('M d, Y', strtotime($rmp_data['expected_delivery_date'])) : 'Not specified'; ?></p>
                <p><strong>Status:</strong> <span class="status-badge <?php echo $rmp_data['status']; ?>"><?php echo ucfirst($rmp_data['status']); ?></span></p>
            </div>
            
            <div class="supplier-info"> <!--
                <h3><?php echo htmlspecialchars($rmp_data['supplier_name']); ?></h3>
                <p><?php echo htmlspecialchars($rmp_data['contact_person']); ?></p>
                <p><?php echo htmlspecialchars($rmp_data['email']); ?></p>
                <p><?php echo htmlspecialchars($rmp_data['phone']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($rmp_data['address'])); ?></p> -->
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th class="amount">Unit Price (Rs)</th>
                    <th class="amount">Total (Rs)</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rmp_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td><?php echo number_format($item['quantity'], 2) . ' ' . $item['unit_of_measure']; ?></td>
                        <td class="amount"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="amount"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        <td><?php echo number_format($item['received_quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="4">Subtotal</td>
                    <td class="amount">Rs <?php echo number_format($rmp_data['subtotal'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="4">Tax (10%)</td>
                    <td class="amount">Rs <?php echo number_format($rmp_data['tax_amount'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="4">Total</td>
                    <td class="amount">Rs <?php echo number_format($rmp_data['total_amount'], 2); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if ($rmp_data['notes']): ?>
            <div class="notes">
                <h4>Notes:</h4>
                <p><?php echo nl2br(htmlspecialchars($rmp_data['notes'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="raw_material_purchase_orders.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            
            <?php if ($rmp_data['status'] == 'ordered'): ?>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="status" value="received">
                    <button type="submit" class="btn btn-receive">
                        <i class="fas fa-truck"></i> Mark as Received
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($rmp_data['status'] != 'cancelled' && $rmp_data['status'] != 'received'): ?>
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
                    <option value="draft" <?php echo ($rmp_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="ordered" <?php echo ($rmp_data['status'] == 'ordered') ? 'selected' : ''; ?>>Ordered</option>
                    <?php if ($rmp_data['status'] == 'received'): ?>
                        <option value="received" selected>Received</option>
                    <?php endif; ?>
                    <?php if ($rmp_data['status'] == 'cancelled'): ?>
                        <option value="cancelled" selected>Cancelled</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-submit">Update Status</button>
            </form>
        </div>
        
    <?php else: ?>
        <p>Raw material purchase order not found.</p>
        <a href="raw_material_purchase_orders.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>
</div>

</body>
</html>

<?php 
$conn->close();
?>
[file content end]