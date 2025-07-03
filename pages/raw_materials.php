<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Pagination setup
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Filtering setup
$filter_sku = isset($_GET['filter_sku']) ? $_GET['filter_sku'] : '';
$filter_name = isset($_GET['filter_name']) ? $_GET['filter_name'] : '';
$filter_supplier = isset($_GET['filter_supplier']) ? $_GET['filter_supplier'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Handle raw material creation
        $sku = $_POST['sku'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $unit_of_measure = $_POST['unit_of_measure'];
        $cost_per_unit = $_POST['cost_per_unit'];
        $reorder_level = $_POST['reorder_level'];
        $supplier_id = $_POST['supplier_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "INSERT INTO raw_materials (sku, name, description, unit_of_measure, cost_per_unit, reorder_level, supplier_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssdiii", $sku, $name, $description, $unit_of_measure, $cost_per_unit, $reorder_level, $supplier_id, $is_active);
            if ($stmt->execute()) {
                $material_id = $stmt->insert_id;
                
                // Initialize inventory record
                $inventory_sql = "INSERT INTO raw_material_inventory (material_id, quantity_on_hand, quantity_allocated) 
                                  VALUES (?, 0, 0)";
                $inventory_stmt = $conn->prepare($inventory_sql);
                $inventory_stmt->bind_param("i", $material_id);
                $inventory_stmt->execute();
                $inventory_stmt->close();
                
                $message = 'Raw material created successfully!';
                $messageType = 'success';
                // Reset to first page after creation
                $current_page = 1;
                $offset = 0;
            } else {
                $message = 'Error creating raw material: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['update'])) {
        // Handle raw material update
        $material_id = $_POST['material_id'];
        $sku = $_POST['sku'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $unit_of_measure = $_POST['unit_of_measure'];
        $cost_per_unit = $_POST['cost_per_unit'];
        $reorder_level = $_POST['reorder_level'];
        $supplier_id = $_POST['supplier_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE raw_materials SET 
                sku = ?, 
                name = ?, 
                description = ?, 
                unit_of_measure = ?, 
                cost_per_unit = ?, 
                reorder_level = ?, 
                supplier_id = ?, 
                is_active = ? 
                WHERE material_id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssdiiii", $sku, $name, $description, $unit_of_measure, $cost_per_unit, $reorder_level, $supplier_id, $is_active, $material_id);
            if ($stmt->execute()) {
                $message = 'Raw material updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating raw material: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete'])) {
        // Handle raw material deletion
        $material_id = (int)$_POST['material_id'];
        
        // Check if the raw material is used in any recipes
        $check_sql = "SELECT COUNT(*) AS count FROM product_recipes WHERE material_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $material_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($row['count'] > 0) {
            $message = 'Cannot delete: Raw material is used in product recipes.';
            $messageType = 'error';
        } else {
            // First, delete the inventory record
            $delete_inventory_sql = "DELETE FROM raw_material_inventory WHERE material_id = ?";
            $delete_inventory_stmt = $conn->prepare($delete_inventory_sql);
            $delete_inventory_stmt->bind_param("i", $material_id);
            $delete_inventory_stmt->execute();
            $delete_inventory_stmt->close();
            
            // Then delete the raw material
            $delete_sql = "DELETE FROM raw_materials WHERE material_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $material_id);
            if ($delete_stmt->execute()) {
                $message = 'Raw material deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error deleting raw material: ' . $delete_stmt->error;
                $messageType = 'error';
            }
            $delete_stmt->close();
        }
    }
}

// Fetch suppliers for dropdown
$suppliers = [];
$supplier_result = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
while ($row = $supplier_result->fetch_assoc()) {
    $suppliers[$row['supplier_id']] = $row['name'];
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_sku)) {
    $filter_conditions[] = "rm.sku LIKE ?";
    $filter_params[] = "%$filter_sku%";
    $filter_types .= 's';
}

if (!empty($filter_name)) {
    $filter_conditions[] = "rm.name LIKE ?";
    $filter_params[] = "%$filter_name%";
    $filter_types .= 's';
}

if (!empty($filter_supplier)) {
    $filter_conditions[] = "rm.supplier_id = ?";
    $filter_params[] = $filter_supplier;
    $filter_types .= 'i';
}

if ($filter_status !== '') {
    $filter_conditions[] = "rm.is_active = ?";
    $filter_params[] = ($filter_status === 'active') ? 1 : 0;
    $filter_types .= 'i';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of raw materials with filters
$count_sql = "SELECT COUNT(*) AS total FROM raw_materials rm $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated raw materials with supplier names and inventory data
$sql_raw_materials = "SELECT rm.*, s.name as supplier_name,
                     (SELECT quantity_on_hand FROM raw_material_inventory WHERE material_id = rm.material_id) as quantity_on_hand,
                     (SELECT quantity_allocated FROM raw_material_inventory WHERE material_id = rm.material_id) as quantity_allocated
                FROM raw_materials rm 
                LEFT JOIN suppliers s ON rm.supplier_id = s.supplier_id
                $where_clause
                ORDER BY rm.name
                LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql_raw_materials);

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
    <title>Raw Materials Management</title>
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
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
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
    <h2>Raw Materials Management</h2>
    <div>
        <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
            <i class="fas fa-plus"></i> New Raw Material
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
                <label for="filter_sku">SKU:</label>
                <input type="text" id="filter_sku" name="filter_sku" value="<?php echo htmlspecialchars($filter_sku); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_name">Name:</label>
                <input type="text" id="filter_name" name="filter_name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_supplier">Supplier:</label>
                <select id="filter_supplier" name="filter_supplier">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_supplier == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
            <th>SKU</th>
            <th>Name</th>
            <th>Description</th>
            <th>Unit</th>
            <th>Cost/Unit (Rs)</th>
            <th>Reorder Level</th>
            <th>Supplier</th>
            <th>On Hand</th>
            <th>Allocated</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo htmlspecialchars($row['unit_of_measure']); ?></td>
                    <td>Rs <?php echo number_format($row['cost_per_unit'], 2); ?></td>
                    <td><?php echo $row['reorder_level']; ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><?php echo $row['quantity_on_hand']; ?></td>
                    <td><?php echo $row['quantity_allocated']; ?></td>
                    <td>
                        <span class="status <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button class="btn btn-edit" onclick="openEditModal(
                            <?php echo $row['material_id']; ?>,
                            '<?php echo addslashes($row['sku']); ?>',
                            '<?php echo addslashes($row['name']); ?>',
                            `<?php echo addslashes(str_replace(["\r", "\n"], '', $row['description'])); ?>`,
                            '<?php echo addslashes($row['unit_of_measure']); ?>',
                            <?php echo $row['cost_per_unit']; ?>,
                            <?php echo $row['reorder_level']; ?>,
                            <?php echo $row['supplier_id'] ?? 'null'; ?>,
                            <?php echo $row['is_active']; ?>
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-delete" onclick="confirmDelete(<?php echo $row['material_id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11">No raw materials found</td>
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

<!-- Create Raw Material Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <h3>Create New Raw Material</h3>
        <form method="POST" action="">
            <input type="hidden" name="create" value="1">
            
            <div class="form-group">
                <label for="createSku">SKU:</label>
                <input type="text" id="createSku" name="sku" required>
            </div>
            
            <div class="form-group">
                <label for="createName">Name:</label>
                <input type="text" id="createName" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="createDescription">Description:</label>
                <textarea id="createDescription" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label for="createUnitOfMeasure">Unit of Measure:</label>
                <input type="text" id="createUnitOfMeasure" name="unit_of_measure" required>
            </div>
            
            <div class="form-group">
                <label for="createCostPerUnit">Cost per Unit (Rs):</label>
                <input type="number" id="createCostPerUnit" name="cost_per_unit" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="createReorderLevel">Reorder Level:</label>
                <input type="number" id="createReorderLevel" name="reorder_level" required>
            </div>
            
            <div class="form-group">
                <label for="createSupplier">Supplier:</label>
                <select id="createSupplier" name="supplier_id">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group checkbox-container">
                <input type="checkbox" id="createIsActive" name="is_active" checked>
                <label for="createIsActive">Active</label>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Create</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Raw Material Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Raw Material</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="editMaterialId" name="material_id">
            
            <div class="form-group">
                <label for="editSku">SKU:</label>
                <input type="text" id="editSku" name="sku" required>
            </div>
            
            <div class="form-group">
                <label for="editName">Name:</label>
                <input type="text" id="editName" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="editDescription">Description:</label>
                <textarea id="editDescription" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label for="editUnitOfMeasure">Unit of Measure:</label>
                <input type="text" id="editUnitOfMeasure" name="unit_of_measure" required>
            </div>
            
            <div class="form-group">
                <label for="editCostPerUnit">Cost per Unit (Rs):</label>
                <input type="number" id="editCostPerUnit" name="cost_per_unit" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="editReorderLevel">Reorder Level:</label>
                <input type="number" id="editReorderLevel" name="reorder_level" required>
            </div>
            
            <div class="form-group">
                <label for="editSupplier">Supplier:</label>
                <select id="editSupplier" name="supplier_id">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group checkbox-container">
                <input type="checkbox" id="editIsActive" name="is_active">
                <label for="editIsActive">Active</label>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Update</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this raw material?</p>
        <form method="POST" action="">
            <input type="hidden" name="delete" value="1">
            <input type="hidden" id="deleteMaterialId" name="material_id">
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-delete">Delete</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Function to open edit modal with data
function openEditModal(materialId, sku, name, description, unitOfMeasure, costPerUnit, reorderLevel, supplierId, isActive) {
    document.getElementById('editMaterialId').value = materialId;
    document.getElementById('editSku').value = sku;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description;
    document.getElementById('editUnitOfMeasure').value = unitOfMeasure;
    document.getElementById('editCostPerUnit').value = costPerUnit;
    document.getElementById('editReorderLevel').value = reorderLevel;
    document.getElementById('editSupplier').value = supplierId;
    document.getElementById('editIsActive').checked = (isActive == 1);
    document.getElementById('editModal').style.display = 'flex';
}

// Function to open delete confirmation modal
function confirmDelete(materialId) {
    document.getElementById('deleteMaterialId').value = materialId;
    document.getElementById('deleteModal').style.display = 'flex';
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
$stmt->close();
$conn->close();
?>
</body>
</html>