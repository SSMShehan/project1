<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Initialize message variables
$message = '';
$messageType = '';

// Fetch products that can be manufactured
$products = [];
$prod_result = $conn->query("SELECT p.product_id, p.sku, p.name, p.manufacturing_cost, 
                            c.name as category_name
                            FROM products p
                            JOIN categories c ON p.category_id = c.category_id
                            WHERE p.manufacturing_cost > 0
                            ORDER BY p.name");
while ($row = $prod_result->fetch_assoc()) {
    $products[$row['product_id']] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $notes = $_POST['notes'];
    
    // Validate
    if (empty($product_id)) {
        $message = 'Product is required';
        $messageType = 'error';
    } elseif ($quantity <= 0) {
        $message = 'Quantity must be greater than 0';
        $messageType = 'error';
    } else {
        try {
            $conn->begin_transaction();
            
            // Generate MO number
            $mo_number = 'MO-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create manufacturing order
            $stmt = $conn->prepare("INSERT INTO manufacturing_orders 
                                  (mo_number, product_id, quantity, status, notes, created_by) 
                                  VALUES (?, ?, ?, 'planned', ?, ?)");
         
            $stmt->bind_param("siisi", $mo_number, $product_id, $quantity, $notes, $user_id);
            $stmt->execute();
            $mo_id = $conn->insert_id;
            $stmt->close();
            
            // Fetch product recipe
            $recipe_stmt = $conn->prepare("SELECT material_id, quantity_required 
                                         FROM product_recipes 
                                         WHERE product_id = ?");
            $recipe_stmt->bind_param("i", $product_id);
            $recipe_stmt->execute();
            $recipe_result = $recipe_stmt->get_result();
            
            // Calculate total cost
            $total_cost = 0;
            $materials_used = [];
            
            while ($recipe_item = $recipe_result->fetch_assoc()) {
                $material_id = $recipe_item['material_id'];
                $required_qty = $recipe_item['quantity_required'] * $quantity;
                
                // Get material cost
                $mat_stmt = $conn->prepare("SELECT cost_per_unit FROM raw_materials WHERE material_id = ?");
                $mat_stmt->bind_param("i", $material_id);
                $mat_stmt->execute();
                $mat_result = $mat_stmt->get_result();
                $material = $mat_result->fetch_assoc();
                $mat_stmt->close();
                
                $material_cost = $required_qty * $material['cost_per_unit'];
                $total_cost += $material_cost;
                
                $materials_used[] = [
                    'material_id' => $material_id,
                    'quantity' => $required_qty,
                    'cost' => $material_cost
                ];
            }
            $recipe_stmt->close();
            
            // Add labor cost (30% of manufacturing cost)
            $labor_cost = ($products[$product_id]['manufacturing_cost'] * $quantity) * 0.3;
            $total_cost += $labor_cost;
            
            // Update MO with total cost
            $update_stmt = $conn->prepare("UPDATE manufacturing_orders 
                                         SET total_cost = ?
                                         WHERE mo_id = ?");
            $update_stmt->bind_param("di", $total_cost, $mo_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $conn->commit();
            
            $message = 'Manufacturing order created successfully!';
            $messageType = 'success';
            
            // Redirect to details page
            header("Location: manufacturing_order_details.php?id=$mo_id");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error creating manufacturing order: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Create Manufacturing Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .recipe-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .recipe-table th, .recipe-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .recipe-table th {
            background-color: #f2f2f2;
        }
        
        .amount {
            text-align: right;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Create Manufacturing Order</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="product_id">Product *</label>
            <select id="product_id" name="product_id" required>
                <option value="">-- Select Product --</option>
                <?php foreach ($products as $id => $product): ?>
                    <option value="<?php echo $id; ?>" 
                            data-cost="<?php echo $product['manufacturing_cost']; ?>">
                        <?php echo htmlspecialchars($product['name'] . ' (' . $product['sku'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity *</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"></textarea>
        </div>
        
        <h3>Required Raw Materials</h3>
        <table class="recipe-table" id="recipeTable">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>SKU</th>
                    <th>Quantity Required</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Available Stock</th>
                </tr>
            </thead>
            <tbody id="recipeTableBody">
                <!-- Will be populated by JavaScript -->
            </tbody>
        </table>
        
        <div class="form-group">
            <label>Estimated Manufacturing Cost per Unit: Rs <span id="unitCost">0.00</span></label>
        </div>
        <div class="form-group">
            <label>Total Estimated Cost: Rs <span id="totalCost">0.00</span></label>
        </div>
        
        <div class="form-group action-buttons">
            <button type="submit" class="btn btn-submit">Create Manufacturing Order</button>
            <a href="manufacturing_orders.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const recipeTableBody = document.getElementById('recipeTableBody');
    const unitCostDisplay = document.getElementById('unitCost');
    const totalCostDisplay = document.getElementById('totalCost');
    
    // Fetch recipe when product changes
    productSelect.addEventListener('change', function() {
        const productId = this.value;
        if (!productId) {
            recipeTableBody.innerHTML = '';
            unitCostDisplay.textContent = '0.00';
            totalCostDisplay.textContent = '0.00';
            return;
        }
        
        fetch(`../api/get_product_recipe.php?product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                recipeTableBody.innerHTML = '';
                let totalCost = 0;
                
                data.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.material_name}</td>
                        <td>${item.material_sku}</td>
                        <td>${item.quantity_required} ${item.unit_of_measure}</td>
                        <td>Rs ${parseFloat(item.cost_per_unit).toFixed(2)}</td>
                        <td>Rs ${(item.quantity_required * item.cost_per_unit).toFixed(2)}</td>
                        <td>${item.available_stock} ${item.unit_of_measure}</td>
                    `;
                    recipeTableBody.appendChild(row);
                    
                    // Calculate material cost per unit
                    totalCost += item.quantity_required * item.cost_per_unit;
                });
                
                // Add labor cost (30% of manufacturing cost)
                const laborCost = parseFloat(productSelect.options[productSelect.selectedIndex].dataset.cost) * 0.3;
                totalCost += laborCost;
                
                unitCostDisplay.textContent = totalCost.toFixed(2);
                calculateTotalCost();
            });
    });
    
    // Calculate total cost when quantity changes
    quantityInput.addEventListener('input', calculateTotalCost);
    
    function calculateTotalCost() {
        const quantity = parseInt(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostDisplay.textContent) || 0;
        totalCostDisplay.textContent = (unitCost * quantity).toFixed(2);
    }
    
    // Trigger initial calculation if product is selected
    if (productSelect.value) {
        productSelect.dispatchEvent(new Event('change'));
    }
});
</script>

</body>
</html>

<?php 
$conn->close();
?>
