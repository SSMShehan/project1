<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Filtering setup
$filter_name = isset($_GET['filter_name']) ? $_GET['filter_name'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Handle supplier creation
        $name = $_POST['name'];
        $contact_person = $_POST['contact_person'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $tax_id = $_POST['tax_id'];
        $payment_terms = $_POST['payment_terms'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address, tax_id, payment_terms, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssssi", $name, $contact_person, $email, $phone, $address, $tax_id, $payment_terms, $is_active);
            if ($stmt->execute()) {
                $message = 'Supplier created successfully!';
                $messageType = 'success';
                // Reset to first page after creation
                $current_page = 1;
                $offset = 0;
            } else {
                $message = 'Error creating supplier: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['update'])) {
        // Handle supplier update
        $supplier_id = $_POST['supplier_id'];
        $name = $_POST['name'];
        $contact_person = $_POST['contact_person'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $tax_id = $_POST['tax_id'];
        $payment_terms = $_POST['payment_terms'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE suppliers SET 
                name = ?, 
                contact_person = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                tax_id = ?, 
                payment_terms = ?, 
                is_active = ? 
                WHERE supplier_id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssssii", $name, $contact_person, $email, $phone, $address, $tax_id, $payment_terms, $is_active, $supplier_id);
            if ($stmt->execute()) {
                $message = 'Supplier updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating supplier: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    }
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_name)) {
    $filter_conditions[] = "name LIKE ?";
    $filter_params[] = "%$filter_name%";
    $filter_types .= 's';
}

if ($filter_status !== '') {
    $filter_conditions[] = "is_active = ?";
    $filter_params[] = ($filter_status === 'active') ? 1 : 0;
    $filter_types .= 'i';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of suppliers with filters
$count_sql = "SELECT COUNT(*) AS total FROM suppliers $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated suppliers with filters
$sql_suppliers = "SELECT * FROM suppliers $where_clause ORDER BY name LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql_suppliers);

if (!empty($filter_params)) {
    $stmt->bind_param($filter_types, ...$filter_params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Suppliers Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Similar styles to product management page */
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
            max-width: 600px;
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
        
        .active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
        }
        
        .checkbox-container input {
            width: auto;
            margin-right: 10px;
        }
        
        .filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-filter {
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-filter:hover {
            background-color: #5a6268;
        }
        
        .btn-reset {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-reset:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Suppliers Management</h2>
    <div>
        <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
            <i class="fas fa-plus"></i> New Supplier
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Filter Form -->
<div class="filter-container">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter_name">Supplier Name:</label>
                <input type="text" id="filter_name" name="filter_name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_status">Status:</label>
                <select id="filter_status" name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo ($filter_status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($filter_status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="?" class="btn-reset">
                <i class="fas fa-times"></i> Reset
            </a>
        </div>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Contact Person</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td>
                        <span class="status <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button class="btn btn-edit" onclick="openEditModal(
                            <?php echo $row['supplier_id']; ?>,
                            '<?php echo addslashes($row['name']); ?>',
                            '<?php echo addslashes($row['contact_person']); ?>',
                            '<?php echo addslashes($row['email']); ?>',
                            '<?php echo addslashes($row['phone']); ?>',
                            `<?php echo addslashes(str_replace(["\r", "\n"], '', $row['address'])); ?>`,
                            '<?php echo addslashes($row['tax_id']); ?>',
                            '<?php echo addslashes($row['payment_terms']); ?>',
                            <?php echo $row['is_active']; ?>
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No suppliers found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination Navigation -->
<div class="pagination">
    <?php if ($current_page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">&laquo; First</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">&lsaquo; Prev</a>
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
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor;
    
    if ($end_page < $total_pages) {
        echo '<span>...</span>';
    }
    ?>
    
    <?php if ($current_page < $total_pages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next &rsaquo;</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">Last &raquo;</a>
    <?php else: ?>
        <span class="disabled">Next &rsaquo;</span>
        <span class="disabled">Last &raquo;</span>
    <?php endif; ?>
</div>

<!-- Create Supplier Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <h3>Create New Supplier</h3>
        <form method="POST" action="">
            <input type="hidden" name="create" value="1">
            
            <div class="form-group">
                <label for="createName">Supplier Name *</label>
                <input type="text" id="createName" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="createContactPerson">Contact Person</label>
                <input type="text" id="createContactPerson" name="contact_person">
            </div>
            
            <div class="form-group">
                <label for="createEmail">Email</label>
                <input type="email" id="createEmail" name="email">
            </div>
            
            <div class="form-group">
                <label for="createPhone">Phone</label>
                <input type="text" id="createPhone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="createAddress">Address</label>
                <textarea id="createAddress" name="address"></textarea>
            </div>
            
            <div class="form-group">
                <label for="createTaxId">Tax ID</label>
                <input type="text" id="createTaxId" name="tax_id">
            </div>
            
            <div class="form-group">
                <label for="createPaymentTerms">Payment Terms</label>
                <input type="text" id="createPaymentTerms" name="payment_terms">
            </div>
            
            <div class="form-group checkbox-container">
                <input type="checkbox" id="createIsActive" name="is_active" checked>
                <label for="createIsActive">Active Supplier</label>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Create</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Supplier</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="editSupplierId" name="supplier_id">
            
            <div class="form-group">
                <label for="editName">Supplier Name *</label>
                <input type="text" id="editName" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="editContactPerson">Contact Person</label>
                <input type="text" id="editContactPerson" name="contact_person">
            </div>
            
            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email">
            </div>
            
            <div class="form-group">
                <label for="editPhone">Phone</label>
                <input type="text" id="editPhone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="editAddress">Address</label>
                <textarea id="editAddress" name="address"></textarea>
            </div>
            
            <div class="form-group">
                <label for="editTaxId">Tax ID</label>
                <input type="text" id="editTaxId" name="tax_id">
            </div>
            
            <div class="form-group">
                <label for="editPaymentTerms">Payment Terms</label>
                <input type="text" id="editPaymentTerms" name="payment_terms">
            </div>
            
            <div class="form-group checkbox-container">
                <input type="checkbox" id="editIsActive" name="is_active">
                <label for="editIsActive">Active Supplier</label>
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
function openEditModal(supplierId, name, contactPerson, email, phone, address, taxId, paymentTerms, isActive) {
    document.getElementById('editSupplierId').value = supplierId;
    document.getElementById('editName').value = name;
    document.getElementById('editContactPerson').value = contactPerson;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editAddress').value = address;
    document.getElementById('editTaxId').value = taxId;
    document.getElementById('editPaymentTerms').value = paymentTerms;
    document.getElementById('editIsActive').checked = (isActive == 1);
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