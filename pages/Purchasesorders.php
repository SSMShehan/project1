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
        // Handle purchase creation
        $poCodes = $_POST['Po_codes'];
        $supplierID = $_POST['Supplier_ID'];
        $date = $_POST['Date'];
        
        // Validate supplier exists before insertion
        if (!validateSupplierExists($conn, $supplierID)) {
            $message = 'Error: Supplier ID does not exist';
            $messageType = 'error';
        } else {
            $sql = "INSERT INTO purchase_details (Po_codes, Supplier_ID, Date) 
                    VALUES (?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sis", $poCodes, $supplierID, $date);
                if ($stmt->execute()) {
                    $message = 'Purchase record created successfully!';
                    $messageType = 'success';
                    // Reset to first page after creation
                    $current_page = 1;
                    $offset = 0;
                } else {
                    $message = 'Error creating purchase record: ' . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            } else {
                $message = 'Database error: ' . $conn->error;
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['update'])) {
        // Handle purchase update
        $purchaseID = $_POST['Purchase_ID'];
        $poCodes = $_POST['Po_codes'];
        $supplierID = $_POST['Supplier_ID'];
        $date = $_POST['Date'];
        
        // Validate supplier exists before update
        if (!validateSupplierExists($conn, $supplierID)) {
            $message = 'Error: Supplier ID does not exist';
            $messageType = 'error';
        } else {
            $sql = "UPDATE purchase_details SET 
                    Po_codes = ?, 
                    Supplier_ID = ?, 
                    Date = ?
                    WHERE Purchase_ID = ?";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sisi", $poCodes, $supplierID, $date, $purchaseID);
                if ($stmt->execute()) {
                    $message = 'Purchase record updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating purchase record: ' . $stmt->error;
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
$count_result = $conn->query("SELECT COUNT(*) AS total FROM purchase_details");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated purchase details with calculated quantity and total price
$sql_purchase_details = "SELECT pd.*, 
                        COALESCE(SUM(rod.Quantity), 0) AS Total_Quantity,
                        COALESCE(SUM(rod.Amount), 0) AS Total_Price
                        FROM purchase_details pd
                        LEFT JOIN receiving_order_details rod ON pd.Supplier_ID = rod.Supplier_ID
                        GROUP BY pd.Purchase_ID
                        LIMIT $offset, $records_per_page";
$result = $conn->query($sql_purchase_details);

// Fetch suppliers for dropdown
$suppliers = $conn->query("SELECT Supplier_ID, Names FROM supplier_details");

// Validation function
function validateSupplierExists($conn, $supplierID) {
    $stmt = $conn->prepare("SELECT Supplier_ID FROM supplier_details WHERE Supplier_ID = ?");
    $stmt->bind_param("i", $supplierID);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Helper function to get supplier name
function getSupplierName($conn, $supplierID) {
    $stmt = $conn->prepare("SELECT Names FROM supplier_details WHERE Supplier_ID = ?");
    $stmt->bind_param("i", $supplierID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['Names'] ?? 'Unknown';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Management</title>
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
    <h2>Purchase Management</h2>
    <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
        <i class="fas fa-plus"></i> Create New Purchase
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
            <th>Purchase ID</th>
            <th>PO Codes</th>
            <th>Supplier</th>
            <th>Total Quantity</th>
            <th>Total Price</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                $supplierName = getSupplierName($conn, $row['Supplier_ID']);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Purchase_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['Po_codes']); ?></td>
                    <td><?php echo htmlspecialchars($supplierName); ?></td>
                    <td><?php echo htmlspecialchars($row['Total_Quantity']); ?></td>
                    <td>Rs <?php echo number_format($row['Total_Price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['Date']); ?></td>
                    <td class="action-buttons">
                        <button class="btn btn-edit" onclick="openEditModal(
                            '<?php echo $row['Purchase_ID']; ?>',
                            '<?php echo htmlspecialchars($row['Po_codes'], ENT_QUOTES); ?>',
                            '<?php echo $row['Supplier_ID']; ?>',
                            '<?php echo $row['Date']; ?>'
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No purchase records found</td>
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
        <h3>Create New Purchase</h3>
        <form method="POST" action="">
            <input type="hidden" name="create" value="1">
            
            <div class="form-group">
                <label for="createPoCodes">PO Codes:</label>
                <input type="text" id="createPoCodes" name="Po_codes" required>
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
                <label for="createDate">Date:</label>
                <input type="date" id="createDate" name="Date" required>
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
        <h3>Edit Purchase</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="editPurchaseID" name="Purchase_ID">
            
            <div class="form-group">
                <label for="editPoCodes">PO Codes:</label>
                <input type="text" id="editPoCodes" name="Po_codes" required>
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
                <label for="editDate">Date:</label>
                <input type="date" id="editDate" name="Date" required>
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
function openEditModal(purchaseId, poCodes, supplierId, date) {
    document.getElementById('editPurchaseID').value = purchaseId;
    document.getElementById('editPoCodes').value = poCodes;
    document.getElementById('editSupplierID').value = supplierId;
    document.getElementById('editDate').value = date;
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