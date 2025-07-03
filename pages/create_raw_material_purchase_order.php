<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Check if we're editing an existing RMPO
$is_edit = isset($_GET['id']);
$rmp_id = $is_edit ? (int)$_GET['id'] : 0;
$rmp_data = null;
$rmp_items = [];

// Fetch suppliers and raw materials
$suppliers = [];
$sup_result = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
while ($row = $sup_result->fetch_assoc()) {
    $suppliers[$row['supplier_id']] = $row['name'];
}

$materials = [];
$mat_result = $conn->query("SELECT material_id, sku, name, unit_of_measure, cost_per_unit 
                          FROM raw_materials 
                          WHERE is_active = 1
                          ORDER BY name");
while ($row = $mat_result->fetch_assoc()) {
    $materials[$row['material_id']] = $row;
}

// Load RMPO data if editing
if ($is_edit) {
    $rmp_stmt = $conn->prepare("SELECT * FROM raw_material_purchase_orders WHERE rmp_id = ?");
    $rmp_stmt->bind_param("i", $rmp_id);
    $rmp_stmt->execute();
    $rmp_result = $rmp_stmt->get_result();
    $rmp_data = $rmp_result->fetch_assoc();
    $rmp_stmt->close();
    
    if (!$rmp_data) {
        $message = 'Raw material purchase order not found';
        $messageType = 'error';
        $is_edit = false;
    } else {
        // Load RMPO items
        $items_stmt = $conn->prepare("SELECT rmpi.*, rm.sku, rm.name 
                                    FROM raw_material_purchase_order_items rmpi
                                    JOIN raw_materials rm ON rmpi.material_id = rm.material_id
                                    WHERE rmpi.rmp_id = ?");
        $items_stmt->bind_param("i", $rmp_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        while ($row = $items_result->fetch_assoc()) {
            $rmp_items[] = $row;
        }
        $items_stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_id = (int)$_POST['supplier_id'];
    $order_date = $_POST['order_date'];
    $expected_delivery_date = $_POST['expected_delivery_date'] ?: null;
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    $items = [];
    $item_count = isset($_POST['material_id']) ? count($_POST['material_id']) : 0;
    
    for ($i = 0; $i < $item_count; $i++) {
        if (!empty($_POST['material_id'][$i])) {
            $items[] = [
                'material_id' => (int)$_POST['material_id'][$i],
                'quantity' => (float)$_POST['quantity'][$i],
                'unit_price' => (float)$_POST['unit_price'][$i]
            ];
        }
    }
    
    // Validate
    if (empty($supplier_id)) {
        $message = 'Supplier is required';
        $messageType = 'error';
    } elseif (empty($order_date)) {
        $message = 'Order date is required';
        $messageType = 'error';
    } elseif (!DateTime::createFromFormat('Y-m-d', $order_date)) {
        $message = 'Invalid date format (YYYY-MM-DD required)';
        $messageType = 'error';
    } elseif (empty($items)) {
        $message = 'At least one item is required';
        $messageType = 'error';
    } else {
        try {
            $conn->begin_transaction();
            
            if ($is_edit) {
                // Update existing RMPO
                $stmt = $conn->prepare("UPDATE raw_material_purchase_orders SET 
                                       supplier_id = ?, 
                                       order_date = ?, 
                                       expected_delivery_date = ?, 
                                       status = ?, 
                                       notes = ?,
                                       updated_at = CURRENT_TIMESTAMP
                                       WHERE rmp_id = ?");
                $stmt->bind_param("issssi", $supplier_id, $order_date, $expected_delivery_date, $status, $notes, $rmp_id);
                $stmt->execute();
                $stmt->close();
                
                // Delete existing items
                $del_stmt = $conn->prepare("DELETE FROM raw_material_purchase_order_items WHERE rmp_id = ?");
                $del_stmt->bind_param("i", $rmp_id);
                $del_stmt->execute();
                $del_stmt->close();
            } else {
                // Generate RMPO number
                $rmp_number = 'RMPO-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Create new RMPO
                $stmt = $conn->prepare("INSERT INTO raw_material_purchase_orders 
                                      (rmp_number, supplier_id, order_date, expected_delivery_date, status, notes, created_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $user_id = $_SESSION['user_id'];
                $stmt->bind_param("sissssi", $rmp_number, $supplier_id, $order_date, $expected_delivery_date, $status, $notes, $user_id);
                $stmt->execute();
                $rmp_id = $conn->insert_id;
                $stmt->close();
            }
            
            // Calculate totals
            $subtotal = 0;
            
            // Add items
            foreach ($items as $item) {
                $item_subtotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $item_subtotal;
                
                $item_stmt = $conn->prepare("INSERT INTO raw_material_purchase_order_items 
                                            (rmp_id, material_id, quantity, unit_price) 
                                            VALUES (?, ?, ?, ?)");
                $item_stmt->bind_param("iidd", $rmp_id, $item['material_id'], $item['quantity'], $item['unit_price']);
                $item_stmt->execute();
                $item_stmt->close();
            }
            
            // Calculate tax (10%)
            $tax_rate = 0.10;
            $tax_amount = $subtotal * $tax_rate;
            $total_amount = $subtotal + $tax_amount;
            
            // Update RMPO with calculated amounts
            $update_stmt = $conn->prepare("UPDATE raw_material_purchase_orders SET 
                                         subtotal = ?, 
                                         tax_amount = ?, 
                                         total_amount = ? 
                                         WHERE rmp_id = ?");
            $update_stmt->bind_param("dddi", $subtotal, $tax_amount, $total_amount, $rmp_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $conn->commit();
            
            $message = $is_edit ? 'Raw material purchase order updated successfully!' : 'Raw material purchase order created successfully!';
            $messageType = 'success';
            
            // Redirect to view page after creation
            if (!$is_edit) {
                header("Location: raw_material_purchase_order_details.php?id=$rmp_id");
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error saving raw material purchase order. Please try again.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Raw Material Purchase Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Same styles as product purchase order form */
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            min-height: 80px;
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
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        
        .btn-add-item {
            background-color: #17a2b8;
            color: white;
            margin-bottom: 15px;
        }
        
        .btn-add-item:hover {
            background-color: #138496;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th, .items-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f2f2f2;
        }
        
        .items-table input {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
        }
        
        .remove-item {
            color: #dc3545;
            cursor: pointer;
            text-align: center;
        }
        
        .remove-item:hover {
            color: #c82333;
        }
        
        .totals-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .amount {
            text-align: right;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2><?php echo $is_edit ? 'Edit' : 'Create'; ?> Raw Material Purchase Order</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="supplier_id">Supplier *</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($is_edit && $rmp_data['supplier_id'] == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="order_date">Order Date *</label>
                <input type="date" id="order_date" name="order_date" required 
                       value="<?php echo $is_edit ? htmlspecialchars($rmp_data['order_date']) : date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="expected_delivery_date">Expected Delivery Date</label>
                <input type="date" id="expected_delivery_date" name="expected_delivery_date"
                       value="<?php echo $is_edit && $rmp_data['expected_delivery_date'] ? htmlspecialchars($rmp_data['expected_delivery_date']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="draft" <?php echo ($is_edit && $rmp_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="ordered" <?php echo ($is_edit && $rmp_data['status'] == 'ordered') ? 'selected' : ''; ?>>Ordered</option>
                    <?php if ($is_edit && $rmp_data['status'] == 'received'): ?>
                        <option value="received" selected>Received</option>
                    <?php endif; ?>
                    <?php if ($is_edit && $rmp_data['status'] == 'cancelled'): ?>
                        <option value="cancelled" selected>Cancelled</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?php echo $is_edit ? htmlspecialchars($rmp_data['notes']) : ''; ?></textarea>
        </div>
        
        <h3>Items</h3>
        <button type="button" class="btn btn-add-item" id="addItemBtn">
            <i class="fas fa-plus"></i> Add Item
        </button>
        
        <table class="items-table" id="itemsTable">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Quantity</th>
                    <th>Unit Price (Rs)</th>
                    <th>Total (Rs)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="itemsTableBody">
                <?php if ($is_edit && !empty($rmp_items)): ?>
                    <?php foreach ($rmp_items as $item): ?>
                        <tr>
                            <td>
                                <select name="material_id[]" class="material-select" required>
                                    <option value="">-- Select Material --</option>
                                    <?php foreach ($materials as $id => $material): ?>
                                        <option value="<?php echo $id; ?>" 
                                            data-price="<?php echo $material['cost_per_unit']; ?>"
                                            data-uom="<?php echo $material['unit_of_measure']; ?>"
                                            <?php echo ($item['material_id'] == $id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($material['name'] . ' (' . $material['sku'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="uom-display"><?php echo $item['unit_of_measure']; ?></div>
                            </td>
                            <td><input type="number" name="quantity[]" min="0.01" step="0.01" value="<?php echo $item['quantity']; ?>" required></td>
                            <td><input type="number" name="unit_price[]" step="0.01" min="0" value="<?php echo $item['unit_price']; ?>" required></td>
                            <td class="amount"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                            <td class="remove-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty row template will be added by JavaScript -->
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="3">Subtotal</td>
                    <td class="amount" id="subtotal">Rs 0.00</td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="3">Tax (10%)</td>
                    <td class="amount" id="tax">Rs 0.00</td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="3">Total</td>
                    <td class="amount" id="total">Rs 0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="form-group action-buttons">
            <button type="submit" class="btn btn-submit"><?php echo $is_edit ? 'Update' : 'Create'; ?> Purchase Order</button>
            <a href="raw_material_purchase_orders.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<script>
// Material data for JavaScript
const materials = <?php echo json_encode($materials); ?>;
let itemCounter = <?php echo $is_edit ? count($rmp_items) : 0; ?>;

// Add item row
document.getElementById('addItemBtn').addEventListener('click', function() {
    const tbody = document.getElementById('itemsTableBody');
    const tr = document.createElement('tr');
    
    tr.innerHTML = `
        <td>
            <select name="material_id[]" class="material-select" required>
                <option value="">-- Select Material --</option>
                <?php foreach ($materials as $id => $material): ?>
                    <option value="<?php echo $id; ?>" 
                        data-price="<?php echo $material['cost_per_unit']; ?>"
                        data-uom="<?php echo $material['unit_of_measure']; ?>">
                        <?php echo htmlspecialchars($material['name'] . ' (' . $material['sku'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="uom-display"></div>
        </td>
        <td><input type="number" name="quantity[]" min="0.01" step="0.01" value="1" required></td>
        <td><input type="number" name="unit_price[]" step="0.01" min="0" value="0" required></td>
        <td class="amount">Rs 0.00</td>
        <td class="remove-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></td>
    `;
    
    tbody.appendChild(tr);
    itemCounter++;
    
    // Add event listeners to new row
    const select = tr.querySelector('.material-select');
    const quantityInput = tr.querySelector('input[name="quantity[]"]');
    const priceInput = tr.querySelector('input[name="unit_price[]"]');
    
    select.addEventListener('change', updateRow);
    quantityInput.addEventListener('input', updateRow);
    priceInput.addEventListener('input', updateRow);
});

// Remove item row
function removeItem(button) {
    if (itemCounter > 1) {
        const tr = button.parentNode;
        tr.parentNode.removeChild(tr);
        itemCounter--;
        calculateTotals();
    } else {
        alert('A purchase order must have at least one item.');
    }
}

// Update row total when material, quantity or price changes
function updateRow() {
    const tr = this.closest('tr');
    const select = tr.querySelector('.material-select');
    const quantityInput = tr.querySelector('input[name="quantity[]"]');
    const priceInput = tr.querySelector('input[name="unit_price[]"]');
    const totalCell = tr.querySelector('.amount');
    const uomDisplay = tr.querySelector('.uom-display');
    
    if (select.value && quantityInput.value && priceInput.value) {
        const quantity = parseFloat(quantityInput.value);
        const price = parseFloat(priceInput.value);
        const total = quantity * price;
        totalCell.textContent = 'Rs ' + total.toFixed(2);
        
        // Update UOM display
        const materialId = select.value;
        if (materials[materialId]) {
            uomDisplay.textContent = materials[materialId].unit_of_measure;
        }
    } else {
        totalCell.textContent = 'Rs 0.00';
        uomDisplay.textContent = '';
    }
    
    calculateTotals();
}

// Calculate subtotal, tax and total
function calculateTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('#itemsTableBody tr');
    
    rows.forEach(row => {
        const totalCell = row.querySelector('.amount');
        const rowTotal = parseFloat(totalCell.textContent.replace('Rs ', ''));
        if (!isNaN(rowTotal)) {
            subtotal += rowTotal;
        }
    });
    
    const taxRate = 0.10;
    const tax = subtotal * taxRate;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = 'Rs ' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = 'Rs ' + tax.toFixed(2);
    document.getElementById('total').textContent = 'Rs ' + total.toFixed(2);
}

// Set default price when material is selected
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('material-select') && e.target.value) {
        const materialId = e.target.value;
        const material = materials[materialId];
        const priceInput = e.target.closest('tr').querySelector('input[name="unit_price[]"]');
        priceInput.value = material.cost_per_unit.toFixed(2);
        updateRow.call(e.target);
    }
});

// Initialize event listeners for existing rows
document.querySelectorAll('.material-select').forEach(select => {
    select.addEventListener('change', updateRow);
});

document.querySelectorAll('input[name="quantity[]"]').forEach(input => {
    input.addEventListener('input', updateRow);
});

document.querySelectorAll('input[name="unit_price[]"]').forEach(input => {
    input.addEventListener('input', updateRow);
});

// Calculate initial totals
calculateTotals();
</script>

</body>
</html>

<?php 
$conn->close();
?>
