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
        // Handle back order creation
        $customerID = $_POST['Customer_ID'];
        $itemID = $_POST['Item_ID'];
        $quantity = $_POST['Quantity'];
        
        // Validate foreign keys before insertion
        if (!validateCustomerExists($conn, $customerID)) {
            $message = 'Error: Customer ID does not exist';
            $messageType = 'error';
        } elseif (!validateItemExists($conn, $itemID)) {
            $message = 'Error: Item ID does not exist';
            $messageType = 'error';
        } else {
            $sql = "INSERT INTO back_order_details (Customer_ID, Item_ID, Quantity, Date_created, Date_updated) 
                    VALUES (?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iii", $customerID, $itemID, $quantity);
                if ($stmt->execute()) {
                    $message = 'Back order created successfully!';
                    $messageType = 'success';
                    // Reset to first page after creation
                    $current_page = 1;
                    $offset = 0;
                } else {
                    $message = 'Error creating back order: ' . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            } else {
                $message = 'Database error: ' . $conn->error;
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['update'])) {
        // Handle back order update
        $backOrderID = $_POST['Back_Order_ID'];
        $customerID = $_POST['Customer_ID'];
        $itemID = $_POST['Item_ID'];
        $quantity = $_POST['Quantity'];
        
        // Validate foreign keys before update
        if (!validateCustomerExists($conn, $customerID)) {
            $message = 'Error: Customer ID does not exist';
            $messageType = 'error';
        } elseif (!validateItemExists($conn, $itemID)) {
            $message = 'Error: Item ID does not exist';
            $messageType = 'error';
        } else {
            $sql = "UPDATE back_order_details SET 
                    Customer_ID = ?, 
                    Item_ID = ?, 
                    Quantity = ?, 
                    Date_updated = NOW() 
                    WHERE Back_Order_ID = ?";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iiii", $customerID, $itemID, $quantity, $backOrderID);
                if ($stmt->execute()) {
                    $message = 'Back order updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating back order: ' . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            } else {
                $message = 'Database error: ' . $conn->error;
                $messageType = 'error';
            }
        }
    }
}

// Fetch total number of records
$count_result = $conn->query("SELECT COUNT(*) AS total FROM back_order_details");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated back order details
$sql_back_order_details = "SELECT * FROM back_order_details LIMIT $offset, $records_per_page";
$result = $conn->query($sql_back_order_details);

// Fetch customers and items for dropdowns
$customers = $conn->query("SELECT Customer_ID, Name FROM customer_details");
$items = $conn->query("SELECT Item_ID, Name FROM item_details");

// Validation functions
function validateCustomerExists($conn, $customerID) {
    $stmt = $conn->prepare("SELECT Customer_ID FROM customer_details WHERE Customer_ID = ?");
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function validateItemExists($conn, $itemID) {
    $stmt = $conn->prepare("SELECT Item_ID FROM item_details WHERE Item_ID = ?");
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Helper functions
function getCustomerName($conn, $customerID) {
    $stmt = $conn->prepare("SELECT Name FROM customer_details WHERE Customer_ID = ?");
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['Name'] ?? 'Unknown';
}

function getItemName($conn, $itemID) {
    $stmt = $conn->prepare("SELECT Name FROM item_details WHERE Item_ID = ?");
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['Name'] ?? 'Unknown';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Back Order Management</title>
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
    <h2>Back Order Management</h2>
    <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
        <i class="fas fa-plus"></i> Create New Back Order
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
            <th>Back Order ID</th>
            <th>Customer</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Date Created</th>
            <th>Date Updated</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                $customerName = getCustomerName($conn, $row['Customer_ID']);
                $itemName = getItemName($conn, $row['Item_ID']);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Back_Order_ID']); ?></td>
                    <td><?php echo htmlspecialchars($customerName); ?></td>
                    <td><?php echo htmlspecialchars($itemName); ?></td>
                    <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
                    <td><?php echo htmlspecialchars($row['Date_created']); ?></td>
                    <td><?php echo $row['Date_updated'] ? htmlspecialchars($row['Date_updated']) : 'NULL'; ?></td>
                    <td class="action-buttons">
                        <button class="btn btn-edit" onclick="openEditModal(
                            '<?php echo $row['Back_Order_ID']; ?>',
                            '<?php echo $row['Customer_ID']; ?>',
                            '<?php echo $row['Item_ID']; ?>',
                            '<?php echo $row['Quantity']; ?>'
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No back orders found</td>
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
        <h3>Create New Back Order</h3>
        <form method="POST" action="">
            <input type="hidden" name="create" value="1">
            
            <div class="form-group">
                <label for="createCustomerID">Customer:</label>
                <select id="createCustomerID" name="Customer_ID" required>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                        <option value="<?php echo $customer['Customer_ID']; ?>">
                            <?php echo htmlspecialchars($customer['Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="createItemID">Item:</label>
                <select id="createItemID" name="Item_ID" required>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <option value="<?php echo $item['Item_ID']; ?>">
                            <?php echo htmlspecialchars($item['Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="createQuantity">Quantity:</label>
                <input type="number" id="createQuantity" name="Quantity" required>
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
        <h3>Edit Back Order</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="editBackOrderID" name="Back_Order_ID">
            
            <div class="form-group">
                <label for="editCustomerID">Customer:</label>
                <select id="editCustomerID" name="Customer_ID" required>
                    <?php 
                    // Reset pointer for customers result
                    $customers->data_seek(0);
                    while ($customer = $customers->fetch_assoc()): ?>
                        <option value="<?php echo $customer['Customer_ID']; ?>">
                            <?php echo htmlspecialchars($customer['Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="editItemID">Item:</label>
                <select id="editItemID" name="Item_ID" required>
                    <?php 
                    // Reset pointer for items result
                    $items->data_seek(0);
                    while ($item = $items->fetch_assoc()): ?>
                        <option value="<?php echo $item['Item_ID']; ?>">
                            <?php echo htmlspecialchars($item['Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="editQuantity">Quantity:</label>
                <input type="number" id="editQuantity" name="Quantity" required>
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
function openEditModal(backOrderId, customerId, itemId, quantity) {
    document.getElementById('editBackOrderID').value = backOrderId;
    
    // Set dropdown values
    document.getElementById('editCustomerID').value = customerId;
    document.getElementById('editItemID').value = itemId;
    
    document.getElementById('editQuantity').value = quantity;
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