<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: sales_orders.php");
    exit();
}

$so_id = (int)$_GET['id'];

// Initialize message variables
$message = '';
$messageType = ''; // 'success' or 'error'

// Fetch order details
$order_sql = "SELECT so.*, c.name as customer_name 
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

// Fetch products for dropdown
$products = [];
$prod_result = $conn->query("SELECT product_id, sku, name, unit_price FROM products WHERE is_active = 1 ORDER BY name");
while ($row = $prod_result->fetch_assoc()) {
    $products[$row['product_id']] = $row;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_order'])) {
        // Handle order update
        $customer_id = $_POST['customer_id'];
        $order_date = $_POST['order_date'];
        $required_date = $_POST['required_date'];
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        
        $sql = "UPDATE sales_orders SET 
                customer_id = ?, 
                order_date = ?, 
                required_date = ?, 
                status = ?, 
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE so_id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issssi", $customer_id, $order_date, $required_date, $status, $notes, $so_id);
            if ($stmt->execute()) {
                $message = 'Order updated successfully!';
                $messageType = 'success';
                // Refresh order data
                header("Location: edit_sales_order.php?id=$so_id");
                exit();
            } else {
                $message = 'Error updating order: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['add_item'])) {
        // Handle item addition
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        
        // Check if product already exists in order
        $check_sql = "SELECT * FROM sales_order_items WHERE so_id = ? AND product_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $so_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing item
            $update_sql = "UPDATE sales_order_items SET 
                          quantity = quantity + ?,
                          unit_price = ?
                          WHERE so_id = ? AND product_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("idii", $quantity, $unit_price, $so_id, $product_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new item
            $insert_sql = "INSERT INTO sales_order_items (so_id, product_id, quantity, unit_price) 
                          VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiid", $so_id, $product_id, $quantity, $unit_price);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        
        // Recalculate order totals
        recalculate_order_totals($conn, $so_id);
        
        $message = 'Item added to order successfully!';
        $messageType = 'success';
        // Refresh page to show updated items
        header("Location: edit_sales_order.php?id=$so_id");
        exit();
    } elseif (isset($_POST['remove_item'])) {
        // Handle item removal
        $so_item_id = $_POST['so_item_id'];
        
        $delete_sql = "DELETE FROM sales_order_items WHERE so_item_id = ? AND so_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $so_item_id, $so_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Recalculate order totals
        recalculate_order_totals($conn, $so_id);
        
        $message = 'Item removed from order successfully!';
        $messageType = 'success';
        // Refresh page to show updated items
        header("Location: edit_sales_order.php?id=$so_id");
        exit();
    } elseif (isset($_POST['update_item'])) {
        // Handle item update
        $so_item_id = $_POST['so_item_id'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        
        $update_sql = "UPDATE sales_order_items SET 
                      quantity = ?,
                      unit_price = ?
                      WHERE so_item_id = ? AND so_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("idii", $quantity, $unit_price, $so_item_id, $so_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Recalculate order totals
        recalculate_order_totals($conn, $so_id);
        
        $message = 'Item updated successfully!';
        $messageType = 'success';
        // Refresh page to show updated items
        header("Location: edit_sales_order.php?id=$so_id");
        exit();
    }
}

// Function to recalculate order totals
function recalculate_order_totals($conn, $so_id) {
    // Calculate subtotal
    $subtotal_sql = "SELECT SUM(quantity * unit_price) AS subtotal 
                    FROM sales_order_items 
                    WHERE so_id = ?";
    $subtotal_stmt = $conn->prepare($subtotal_sql);
    $subtotal_stmt->bind_param("i", $so_id);
    $subtotal_stmt->execute();
    $subtotal_result = $subtotal_stmt->get_result();
    $subtotal = $subtotal_result->fetch_assoc()['subtotal'] ?? 0;
    $subtotal_stmt->close();
    
    // Get tax rate from settings
    $tax_rate_sql = "SELECT setting_value FROM system_settings WHERE setting_name = 'tax_rate'";
    $tax_rate_result = $conn->query($tax_rate_sql);
    $tax_rate_row = $tax_rate_result->fetch_assoc();
    $tax_rate = $tax_rate_row ? (float)$tax_rate_row['setting_value'] : 0.10;
    
    // Calculate tax and total
    $tax_amount = $subtotal * $tax_rate;
    $total_amount = $subtotal + $tax_amount;
    
    // Update order with new totals
    $update_sql = "UPDATE sales_orders SET 
                  subtotal = ?,
                  tax_amount = ?,
                  total_amount = ?
                  WHERE so_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("dddi", $subtotal, $tax_amount, $total_amount, $so_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Fetch customers for dropdown
$customers = [];
$cust_result = $conn->query("SELECT customer_id, name FROM customers WHERE status = 'active' ORDER BY name");
while ($row = $cust_result->fetch_assoc()) {
    $customers[$row['customer_id']] = $row['name'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Sales Order #<?php echo $order['order_number']; ?></title>
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
        
        .btn-save {
            background-color: #28a745;
            color: white;
        }
        
        .btn-save:hover {
            background-color: #218838;
        }
        
        .btn-add {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-add:hover {
            background-color: #138496;
        }
        
        .btn-remove {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-remove:hover {
            background-color: #c82333;
        }
        
        .btn-update {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-update:hover {
            background-color: #e0a800;
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
            max-width: 500px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-col {
            flex: 1;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Edit Sales Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
    <div>
        <a href="sales_orders.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="update_order" value="1">
    
    <div class="form-row">
        <div class="form-col">
            <div class="form-group">
                <label for="customer_id">Customer:</label>
                <select id="customer_id" name="customer_id" required>
                    <?php foreach ($customers as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($order['customer_id'] == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-col">
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="draft" <?php echo ($order['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="confirmed" <?php echo ($order['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-col">
            <div class="form-group">
                <label for="order_date">Order Date:</label>
                <input type="date" id="order_date" name="order_date" value="<?php echo htmlspecialchars($order['order_date']); ?>" required>
            </div>
        </div>
        
        <div class="form-col">
            <div class="form-group">
                <label for="required_date">Required Date:</label>
                <input type="date" id="required_date" name="required_date" value="<?php echo htmlspecialchars($order['required_date']); ?>">
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="notes">Notes:</label>
        <textarea id="notes" name="notes"><?php echo htmlspecialchars($order['notes']); ?></textarea>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn btn-save">
            <i class="fas fa-save"></i> Save Order Details
        </button>
    </div>
</form>

<h3>Order Items</h3>

<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Actions</th>
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
                    <td class="action-buttons">
                        <button class="btn btn-update" onclick="openEditItemModal(
                            <?php echo $item['so_item_id']; ?>,
                            <?php echo $item['quantity']; ?>,
                            <?php echo $item['unit_price']; ?>
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="remove_item" value="1">
                            <input type="hidden" name="so_item_id" value="<?php echo $item['so_item_id']; ?>">
                            <button type="submit" class="btn btn-remove" onclick="return confirm('Are you sure you want to remove this item?');">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No items in this order</td>
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
        <span class="summary-label">Tax (<?php echo ($order['subtotal'] > 0 ? round($order['tax_amount'] / $order['subtotal'] * 100, 2) : 0); ?>%):</span>
        <span class="summary-value">Rs <?php echo number_format($order['tax_amount'], 2); ?></span>
    </div>
    <div class="summary-row total-row">
        <span class="summary-label">Total Amount:</span>
        <span class="summary-value">Rs <?php echo number_format($order['total_amount'], 2); ?></span>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <h3>Add Item to Order</h3>
        <form method="POST" action="">
            <input type="hidden" name="add_item" value="1">
            
            <div class="form-group">
                <label for="product_id">Product:</label>
                <select id="product_id" name="product_id" required onchange="updateUnitPrice(this)">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $id => $product): ?>
                        <option value="<?php echo $id; ?>" data-price="<?php echo $product['unit_price']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['sku']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                    </div>
                </div>
                
                <div class="form-col">
                    <div class="form-group">
                        <label for="unit_price">Unit Price (Rs):</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-add">
                    <i class="fas fa-plus"></i> Add Item
                </button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal">
    <div class="modal-content">
        <h3>Edit Order Item</h3>
        <form method="POST" action="">
            <input type="hidden" name="update_item" value="1">
            <input type="hidden" id="edit_so_item_id" name="so_item_id">
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="edit_quantity">Quantity:</label>
                        <input type="number" id="edit_quantity" name="quantity" min="1" required>
                    </div>
                </div>
                
                <div class="form-col">
                    <div class="form-group">
                        <label for="edit_unit_price">Unit Price (Rs):</label>
                        <input type="number" id="edit_unit_price" name="unit_price" step="0.01" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-update">
                    <i class="fas fa-save"></i> Update Item
                </button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="form-group">
    <button class="btn btn-add" onclick="document.getElementById('addItemModal').style.display='flex'">
        <i class="fas fa-plus"></i> Add Item
    </button>
</div>

<script>
// Function to update unit price when product is selected
function updateUnitPrice(select) {
    if (select.value) {
        var selectedOption = select.options[select.selectedIndex];
        var price = selectedOption.getAttribute('data-price');
        document.getElementById('unit_price').value = price;
    }
}

// Function to open edit item modal with data
function openEditItemModal(soItemId, quantity, unitPrice) {
    document.getElementById('edit_so_item_id').value = soItemId;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('edit_unit_price').value = unitPrice;
    document.getElementById('editItemModal').style.display = 'flex';
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