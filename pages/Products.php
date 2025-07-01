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

// Filtering setup
$filter_sku = isset($_GET['filter_sku']) ? $_GET['filter_sku'] : '';
$filter_name = isset($_GET['filter_name']) ? $_GET['filter_name'] : '';
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Handle product creation
        $sku = $_POST['sku'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $unit_price = $_POST['unit_price'];
        $cost_price = $_POST['cost_price'];
        $reorder_level = $_POST['reorder_level'];
        $unit_of_measure = $_POST['unit_of_measure'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "INSERT INTO products (sku, name, description, category_id, unit_price, cost_price, reorder_level, unit_of_measure, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssiddiss", $sku, $name, $description, $category_id, $unit_price, $cost_price, $reorder_level, $unit_of_measure, $is_active);
            if ($stmt->execute()) {
                $message = 'Product created successfully!';
                $messageType = 'success';
                // Reset to first page after creation
                $current_page = 1;
                $offset = 0;
            } else {
                $message = 'Error creating product: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['update'])) {
        // Handle product update
        $product_id = $_POST['product_id'];
        $sku = $_POST['sku'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $unit_price = $_POST['unit_price'];
        $cost_price = $_POST['cost_price'];
        $reorder_level = $_POST['reorder_level'];
        $unit_of_measure = $_POST['unit_of_measure'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE products SET 
                sku = ?, 
                name = ?, 
                description = ?, 
                category_id = ?, 
                unit_price = ?, 
                cost_price = ?, 
                reorder_level = ?, 
                unit_of_measure = ?, 
                is_active = ? 
                WHERE product_id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssiddissi", $sku, $name, $description, $category_id, $unit_price, $cost_price, $reorder_level, $unit_of_measure, $is_active, $product_id);
            if ($stmt->execute()) {
                $message = 'Product updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating product: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    } elseif (isset($_POST['create_category'])) {
        // Handle category creation
        $category_name = $_POST['category_name'];
        $category_description = $_POST['category_description'];
        
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $category_name, $category_description);
            if ($stmt->execute()) {
                $message = 'Category created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error creating category: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error: ' . $conn->error;
            $messageType = 'error';
        }
    }
}

// Fetch categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
while ($row = $cat_result->fetch_assoc()) {
    $categories[$row['category_id']] = $row['name'];
}

// Build filter conditions
$filter_conditions = [];
$filter_params = [];
$filter_types = '';

if (!empty($filter_sku)) {
    $filter_conditions[] = "p.sku LIKE ?";
    $filter_params[] = "%$filter_sku%";
    $filter_types .= 's';
}

if (!empty($filter_name)) {
    $filter_conditions[] = "p.name LIKE ?";
    $filter_params[] = "%$filter_name%";
    $filter_types .= 's';
}

if (!empty($filter_category)) {
    $filter_conditions[] = "p.category_id = ?";
    $filter_params[] = $filter_category;
    $filter_types .= 'i';
}

if ($filter_status !== '') {
    $filter_conditions[] = "p.is_active = ?";
    $filter_params[] = ($filter_status === 'active') ? 1 : 0;
    $filter_types .= 'i';
}

$where_clause = empty($filter_conditions) ? '' : "WHERE " . implode(" AND ", $filter_conditions);

// Fetch total number of products with filters
$count_sql = "SELECT COUNT(*) AS total FROM products p $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($filter_params)) {
    $count_stmt->bind_param($filter_types, ...$filter_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated products with category names and filters
$sql_products = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id
                $where_clause
                ORDER BY p.name
                LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql_products);

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
    <title>Product Management</title>
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
    <h2>Product Management</h2>
    <div>
        <button class="btn btn-create" onclick="document.getElementById('createModal').style.display='flex'">
            <i class="fas fa-plus"></i> New Product
        </button>
        <button class="btn btn-create" onclick="document.getElementById('createCategoryModal').style.display='flex'" style="margin-left: 10px;">
            <i class="fas fa-tag"></i> New Category
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
                <label for="filter_sku">ID :</label>
                <input type="text" id="filter_sku" name="filter_sku" value="<?php echo htmlspecialchars($filter_sku); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_name">Name:</label>
                <input type="text" id="filter_name" name="filter_name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_category">Category:</label>
                <select id="filter_category" name="filter_category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_category == $id) ? 'selected' : ''; ?>>
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
            <th>ID </th>
            <th>Name</th>
            <th>Category</th>
            <th>Unit Price</th>
            <th>Cost Price</th>
            <th>Reorder Level</th>
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
                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                    <td>Rs <?php echo number_format($row['unit_price'], 2); ?></td>
                    <td>Rs <?php echo number_format($row['cost_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['reorder_level']); ?></td>
                    <td>
                        <span class="status <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button class="btn btn-edit" onclick="openEditModal(
                            <?php echo $row['product_id']; ?>,
                            '<?php echo addslashes($row['sku']); ?>',
                            '<?php echo addslashes($row['name']); ?>',
                            `<?php echo addslashes(str_replace(["\r", "\n"], '', $row['description'])); ?>`,
                            <?php echo $row['category_id'] ?? 'null'; ?>,
                            <?php echo $row['unit_price']; ?>,
                            <?php echo $row['cost_price']; ?>,
                            <?php echo $row['reorder_level']; ?>,
                            '<?php echo addslashes($row['unit_of_measure']); ?>',
                            <?php echo $row['is_active']; ?>
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No products found</td>
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

<!-- Create Product Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <h3>Create New Product</h3>
        <form method="POST" action="">
            <input type="hidden" name="create" value="1">
            
            <div class="form-group">
                <label for="createSku">ID :</label>
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
                <label for="createCategory">Category:</label>
                <select id="createCategory" name="category_id">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="createUnitPrice">Unit Price (Rs):</label>
                <input type="number" id="createUnitPrice" name="unit_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="createCostPrice">Cost Price (Rs):</label>
                <input type="number" id="createCostPrice" name="cost_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="createReorderLevel">Reorder Level:</label>
                <input type="number" id="createReorderLevel" name="reorder_level" required>
            </div>
            
            <div class="form-group">
                <label for="createUnitOfMeasure">Unit of Measure:</label>
                <input type="text" id="createUnitOfMeasure" name="unit_of_measure" value="pieces">
            </div>
            
            <div class="form-group checkbox-container">
                <input type="checkbox" id="createIsActive" name="is_active" checked>
                <label for="createIsActive">Active Product</label>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Create</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Product</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="editProductId" name="product_id">
            
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
                <label for="editCategory">Category:</label>
                <select id="editCategory" name="category_id">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="editUnitPrice">Unit Price (Rs):</label>
                <input type="number" id="editUnitPrice" name="unit_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="editCostPrice">Cost Price (Rs):</label>
                <input type="number" id="editCostPrice" name="cost_price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="editReorderLevel">Reorder Level:</label>
                <input type="number" id="editReorderLevel" name="reorder_level" required>
            </div>
            
            <div class="form-group">
                <label for="editUnitOfMeasure">Unit of Measure:</label>
                <input type="text" id="editUnitOfMeasure" name="unit_of_measure">
            </div>
            
            <div class="form-group checkbox-container">
                <input type="checkbox" id="editIsActive" name="is_active">
                <label for="editIsActive">Active Product</label>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Update</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Category Modal -->
<div id="createCategoryModal" class="modal">
    <div class="modal-content">
        <h3>Create New Category</h3>
        <form method="POST" action="">
            <input type="hidden" name="create_category" value="1">
            
            <div class="form-group">
                <label for="category_name">Category Name:</label>
                <input type="text" id="category_name" name="category_name" required>
            </div>
            
            <div class="form-group">
                <label for="category_description">Description:</label>
                <textarea id="category_description" name="category_description"></textarea>
            </div>
            
            <div class="form-group action-buttons">
                <button type="submit" class="btn btn-submit">Create</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Function to open edit modal with data
function openEditModal(productId, sku, name, description, categoryId, unitPrice, costPrice, reorderLevel, unitOfMeasure, isActive) {
    document.getElementById('editProductId').value = productId;
    document.getElementById('editSku').value = sku;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description;
    document.getElementById('editCategory').value = categoryId;
    document.getElementById('editUnitPrice').value = unitPrice;
    document.getElementById('editCostPrice').value = costPrice;
    document.getElementById('editReorderLevel').value = reorderLevel;
    document.getElementById('editUnitOfMeasure').value = unitOfMeasure;
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