<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = ''; // 'success' or 'error'

// Pagination setup
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Handle receiving order creation
        $formID = $_POST['form_ID'];
        $supplierID = $_POST['Supplier_ID'];
        $quantity = $_POST['Quantity'];
        $unitPrice = $_POST['Unit_price'];
        $discount = $_POST['Discount'] ?? 0;
        $tax = $_POST['Tax'] ?? 0;
        
        // Calculate amount
        $amount = ($unitPrice * $quantity) + $tax - $discount;
        
        $sql = "INSERT INTO receiving_order_details 
                (form_ID, Supplier_ID, Quantity, Unit_price, Discount, Tax, Amount, Date_created, Date_updated) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("siidddd", $formID, $supplierID, $quantity, $unitPrice, $discount, $tax, $amount);
            if ($stmt->execute()) {
                $message = 'Receiving order created successfully!';
                $messageType = 'success';
                // Reset to first page after creation
                $current_page = 1;
                $offset = 0;
            } else {
                $message = 'Error creating receiving order: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['update'])) {
        // Handle receiving order update
        $receivingOrderID = $_POST['Receiving_Order_ID'];
        $formID = $_POST['form_ID'];
        $supplierID = $_POST['Supplier_ID'];
        $quantity = $_POST['Quantity'];
        $unitPrice = $_POST['Unit_price'];
        $discount = $_POST['Discount'] ?? 0;
        $tax = $_POST['Tax'] ?? 0;
        
        // Calculate new amount
        $amount = ($unitPrice * $quantity) + $tax - $discount;
        
        $sql = "UPDATE receiving_order_details SET 
                form_ID = ?, 
                Supplier_ID = ?, 
                Quantity = ?,
                Unit_price = ?,
                Discount = ?, 
                Tax = ?, 
                Amount = ?,
                Date_updated = NOW() 
                WHERE Receiving_Order_ID = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("siiddddi", $formID, $supplierID, $quantity, $unitPrice, $discount, $tax, $amount, $receivingOrderID);
            if ($stmt->execute()) {
                $message = 'Receiving order updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating receiving order: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    }
}

// Fetch total number of records
$count_result = $conn->query("SELECT COUNT(*) AS total FROM receiving_order_details");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated receiving order details with supplier names
$sql_receiving_order_details = "SELECT rod.*, sd.Names AS Supplier_Name 
                               FROM receiving_order_details rod
                               LEFT JOIN supplier_details sd ON rod.Supplier_ID = sd.Supplier_ID
                               LIMIT $offset, $records_per_page";
$result = $conn->query($sql_receiving_order_details);

// Fetch suppliers for dropdown
$suppliers = $conn->query("SELECT Supplier_ID, Names FROM supplier_details");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receiving Order Management</title>
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
            max-width: 500px;
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
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
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
    </style>
</head>
<body>

<div class="header-container">
    <h2>Receiving Order Management</h2>
    <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
        <i class="fas fa-plus"></i> Create New Receiving Order
    </button>
</div>

<?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Receiving Order ID</th>
            <th>Form ID</th>
            <th>Supplier</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Discount</th>
            <th>Tax</th>
            <th>Amount</th>
            <th>Date Created</th>
            <th>Date Updated</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Receiving_Order_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['form_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['Supplier_Name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
                    <td>Rs <?php echo number_format($row['Unit_price'], 2); ?></td>
                    <td>Rs <?php echo number_format($row['Discount'], 2); ?></td>
                    <td>Rs <?php echo number_format($row['Tax'], 2); ?></td>
                    <td>Rs <?php echo number_format($row['Amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['Date_created']); ?></td>
                    <td><?php echo !empty($row['Date_updated']) ? htmlspecialchars($row['Date_updated']) : 'N/A'; ?></td>
                    <td class="action-buttons">
                        <button class="btn btn-edit" onclick="openEditModal(
                            '<?php echo $row['Receiving_Order_ID']; ?>',
                            '<?php echo htmlspecialchars($row['form_ID'], ENT_QUOTES); ?>',
                            '<?php echo $row['Supplier_ID']; ?>',
                            '<?php echo $row['Quantity']; ?>',
                            '<?php echo $row['Unit_price']; ?>',
                            '<?php echo $row['Discount']; ?>',
                            '<?php echo $row['Tax']; ?>'
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11">No receiving orders found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination Navigation -->
<div class="pagination">
    <?php if ($current_page > 1): ?>
        <a href="?page=1">&laquo; First</a>
        <a href="?page=<?php echo $current_page - 1; ?>">&lsaquo; Prev</a>
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
        <a href="?page=<?php echo $i; ?>" <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor;
    
    if ($end_page < $total_pages) {
        echo '<span>...</span>';
    }
    ?>
    
    <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?php echo $current_page + 1; ?>">Next &rsaquo;</a>
        <a href="?page=<?php echo $total_pages; ?>">Last &raquo;</a>
    <?php else: ?>
        <span class="disabled">Next &rsaquo;</span>
        <span class="disabled">Last &raquo;</span>
    <?php endif; ?>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <h3>Create New Receiving Order</h3>
        <form method="POST" action="">
            <input type="hidden" name="create" value="1">
            
            <div class="form-group">
                <label for="createFormID">Form ID:</label>
                <input type="text" id="createFormID" name="form_ID" required>
            </div>
            
            <div class="form-group">
                <label for="createSupplierID">Supplier:</label>
                <select id="createSupplierID" name="Supplier_ID" required>
                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['Supplier_ID']; ?>">
                            <?php echo htmlspecialchars($supplier['Names']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="createQuantity">Quantity:</label>
                <input type="number" id="createQuantity" name="Quantity" required>
            </div>
            
            <div class="form-group">
                <label for="createUnitPrice">Unit Price:</label>
                <input type="number" id="createUnitPrice" name="Unit_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="createDiscount">Discount (Rs):</label>
                <input type="number" id="createDiscount" name="Discount" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="createTax">Tax (Rs):</label>
                <input type="number" id="createTax" name="Tax" step="0.01">
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Create</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Receiving Order</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="editReceivingOrderID" name="Receiving_Order_ID">
            
            <div class="form-group">
                <label for="editFormID">Form ID:</label>
                <input type="text" id="editFormID" name="form_ID" required>
            </div>
            
            <div class="form-group">
                <label for="editSupplierID">Supplier:</label>
                <select id="editSupplierID" name="Supplier_ID" required>
                    <?php 
                    $suppliers->data_seek(0);
                    while ($supplier = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['Supplier_ID']; ?>">
                            <?php echo htmlspecialchars($supplier['Names']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="editQuantity">Quantity:</label>
                <input type="number" id="editQuantity" name="Quantity" required>
            </div>
            
            <div class="form-group">
                <label for="editUnitPrice">Unit Price:</label>
                <input type="number" id="editUnitPrice" name="Unit_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="editDiscount">Discount (Rs):</label>
                <input type="number" id="editDiscount" name="Discount" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="editTax">Tax (Rs):</label>
                <input type="number" id="editTax" name="Tax" step="0.01">
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Update</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Function to open edit modal with data
function openEditModal(receivingOrderId, formId, supplierId, quantity, unitPrice, discount, tax) {
    document.getElementById('editReceivingOrderID').value = receivingOrderId;
    document.getElementById('editFormID').value = formId;
    document.getElementById('editSupplierID').value = supplierId;
    document.getElementById('editQuantity').value = quantity;
    document.getElementById('editUnitPrice').value = unitPrice;
    document.getElementById('editDiscount').value = discount;
    document.getElementById('editTax').value = tax;
    document.getElementById('editModal').style.display = 'flex';
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