<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize variables
$message = '';
$messageType = '';
$mo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$mo_data = null;
$mo_items = [];


// Validate MO ID
if (!$mo_id) {
    header("Location: manufacturing_orders.php");
    exit();
}

// Verify user exists in database
$user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$user_check->bind_param("i", $user_id);
$user_check->execute();
if (!$user_check->get_result()->num_rows) {
    die("Error: Invalid user account");
}
$user_check->close();

// Fetch MO data
$mo_stmt = $conn->prepare("SELECT mo.*, p.name as product_name, p.sku, 
                          u.username as created_by_name
                          FROM manufacturing_orders mo
                          JOIN products p ON mo.product_id = p.product_id
                          LEFT JOIN users u ON mo.created_by = u.user_id
                          WHERE mo.mo_id = ?");
$mo_stmt->bind_param("i", $mo_id);
$mo_stmt->execute();
$mo_result = $mo_stmt->get_result();
$mo_data = $mo_result->fetch_assoc();
$mo_stmt->close();

if (!$mo_data) {
    $message = 'Manufacturing order not found';
    $messageType = 'error';
} else {
    // Fetch MO recipe items
    $items_stmt = $conn->prepare("SELECT pr.*, rm.sku as material_sku, rm.name as material_name,
                                rm.unit_of_measure, rmi.quantity_on_hand as available_stock
                                FROM product_recipes pr
                                JOIN raw_materials rm ON pr.material_id = rm.material_id
                                LEFT JOIN raw_material_inventory rmi ON rm.material_id = rmi.material_id
                                WHERE pr.product_id = ?");
    $items_stmt->bind_param("i", $mo_data['product_id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    while ($row = $items_result->fetch_assoc()) {
        $row['required_qty'] = $row['quantity_required'] * $mo_data['quantity'];
        $mo_items[] = $row;
    }
    $items_stmt->close();
}

// Handle MO completion
if ($action == 'complete' && $mo_data) {
    if ($mo_data['status'] == 'completed') {
        $message = 'Manufacturing order already completed';
        $messageType = 'error';
    } else {
        // Start the try-catch block
        try {
            $conn->begin_transaction();
            
            // Check raw material availability
            $insufficient_materials = [];
            foreach ($mo_items as $item) {
                $required_qty = $item['quantity_required'] * $mo_data['quantity'];
                if ($item['available_stock'] < $required_qty) {
                    $insufficient_materials[] = [
                        'name' => $item['material_name'],
                        'required' => $required_qty,
                        'available' => $item['available_stock']
                    ];
                }
            }
            
            if (!empty($insufficient_materials)) {
                $message = 'Insufficient raw materials:';
                foreach ($insufficient_materials as $mat) {
                    $message .= "<br>- {$mat['name']}: Required {$mat['required']}, Available {$mat['available']}";
                }
                $messageType = 'error';
                $conn->rollback();
            } else {
                // Process materials
                foreach ($mo_items as $item) {
                    $required_qty = $item['quantity_required'] * $mo_data['quantity'];
                    
                    // Update inventory
                    $update_stmt = $conn->prepare("UPDATE raw_material_inventory 
                                                SET quantity_on_hand = quantity_on_hand - ? 
                                                WHERE material_id = ?");
                    $update_stmt->bind_param("di", $required_qty, $item['material_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Record transaction
                    $trans_stmt = $conn->prepare("INSERT INTO raw_material_transactions 
                                                (material_id, transaction_type, quantity, reference_id, reference_number, mo_id, created_by) 
                                                VALUES (?, 'consumption', ?, ?, ?, ?, ?)");
                    $material_id = $item['material_id'];
                    $quantity = -$required_qty; // Negative for consumption
                    $ref_id = $mo_id;
                    $ref_number = $mo_data['mo_number'];
                    $mo_id_param = $mo_id;
                
                    
                    $trans_stmt->bind_param("idiiis", 
                        $material_id, 
                        $quantity,
                        $ref_id, 
                        $ref_number, 
                        $mo_id_param,
                        $created_by);
                    $trans_stmt->execute();
                    $trans_stmt->close();
                }
                
                // Add finished goods to inventory
                $inv_stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity_on_hand)
                                          VALUES (?, ?)
                                          ON DUPLICATE KEY UPDATE 
                                          quantity_on_hand = quantity_on_hand + VALUES(quantity_on_hand)");
                $inv_stmt->bind_param("ii", $mo_data['product_id'], $mo_data['quantity']);
                $inv_stmt->execute();
                $inv_stmt->close();
                
                // Record inventory transaction
                $inv_trans_stmt = $conn->prepare("INSERT INTO inventory_transactions 
                                                (product_id, transaction_type, quantity, reference_id, reference_number, created_by) 
                                                VALUES (?, 'production', ?, ?, ?, ?)");
                $product_id = $mo_data['product_id'];
                $quantity = $mo_data['quantity'];
                $ref_id = $mo_id;
                $ref_number = $mo_data['mo_number'];
                
                
                $inv_trans_stmt->bind_param("isiis",
                    $product_id,
                 
                    $quantity,
                    $ref_id,
                    $ref_number,
                    $created_by);
                $inv_trans_stmt->execute();
                $inv_trans_stmt->close();
                
                // Update MO status
                $update_mo_stmt = $conn->prepare("UPDATE manufacturing_orders 
                                                SET status = 'completed', 
                                                    completion_date = CURDATE(),
                                                    updated_at = CURRENT_TIMESTAMP
                                                WHERE mo_id = ?");
                $update_mo_stmt->bind_param("i", $mo_id);
                $update_mo_stmt->execute();
                $update_mo_stmt->close();
                
                $conn->commit();
                
                $message = 'Manufacturing order completed successfully! Raw materials deducted and finished goods added to inventory.';
                $messageType = 'success';
                
                // Refresh data
                $mo_stmt = $conn->prepare("SELECT * FROM manufacturing_orders WHERE mo_id = ?");
                $mo_stmt->bind_param("i", $mo_id);
                $mo_stmt->execute();
                $mo_result = $mo_stmt->get_result();
                $mo_data = $mo_result->fetch_assoc();
                $mo_stmt->close();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error completing manufacturing order: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Manufacturing order completion error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manufacturing Order Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .mo-info {
            flex: 1;
        }
        
        .product-info {
            flex: 1;
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
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
        
        .insufficient {
            color: #dc3545;
            font-weight: bold;
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
            text-decoration: none;
            display: inline-block;
            text-align: center;
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
        
        .btn-complete {
            background-color: #28a745;
            color: white;
        }
        
        .btn-complete:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($mo_data): ?>
        <div class="header">
            <div class="mo-info">
                <h2>Manufacturing Order: <?php echo htmlspecialchars($mo_data['mo_number']); ?></h2>
                <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($mo_data['created_at'])); ?></p>
                <p><strong>Status:</strong> <span class="status-badge <?php echo str_replace(' ', '_', $mo_data['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $mo_data['status'])); ?></span></p>
                <?php if ($mo_data['start_date']): ?>
                    <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($mo_data['start_date'])); ?></p>
                <?php endif; ?>
                <?php if ($mo_data['completion_date']): ?>
                    <p><strong>Completion Date:</strong> <?php echo date('M d, Y', strtotime($mo_data['completion_date'])); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                
                <p><strong>Quantity:</strong> <?php echo $mo_data['quantity']; ?></p>
                <p><strong>Total Cost:</strong> Rs <?php echo number_format($mo_data['total_cost'], 2); ?></p>
            </div>
        </div>
        
        <h3>Required Raw Materials</h3>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>SKU</th>
                    <th>Unit of Measure</th>
                    <th>Required per Unit</th>
                    <th>Total Required</th>
                    <th>Available Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mo_items as $item): ?>
                    <?php 
                    $total_required = $item['quantity_required'] * $mo_data['quantity'];
                    $sufficient = $item['available_stock'] >= $total_required;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['material_sku']); ?></td>
                        <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                        <td><?php echo number_format($item['quantity_required']); ?></td>
                        <td><?php echo number_format($total_required); ?></td>
                        <td><?php echo number_format($item['available_stock']); ?></td>
                        <td class="<?php echo $sufficient ? '' : 'insufficient'; ?>">
                            <?php echo $sufficient ? 'Sufficient' : 'Insufficient'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($mo_data['notes']): ?>
            <div class="notes">
                <h4>Notes:</h4>
                <p><?php echo nl2br(htmlspecialchars($mo_data['notes'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="manufacturing_orders.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            
            <?php if ($mo_data['status'] == 'planned' || $mo_data['status'] == 'in_progress'): ?>
                <a href="?id=<?php echo $mo_id; ?>&action=complete" 
                   class="btn btn-complete" 
                   onclick="return confirm('Complete this manufacturing order? This will deduct raw materials and add finished goods to inventory.');">
                    <i class="fas fa-check"></i> Complete Order
                </a>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <p>Manufacturing order not found.</p>
        <a href="manufacturing_orders.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>
</div>

</body>
</html>

<?php 
$conn->close();
?>