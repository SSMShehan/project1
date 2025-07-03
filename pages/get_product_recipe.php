<?php
require_once '../config/db_config.php';

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id) {
    echo json_encode([]);
    exit();
}

$recipe = [];

// Get recipe items
$stmt = $conn->prepare("SELECT pr.*, rm.sku AS material_sku, rm.name AS material_name, 
                       rm.cost_per_unit, rm.unit_of_measure,
                       rmi.quantity_on_hand AS available_stock
                       FROM product_recipes pr
                       JOIN raw_materials rm ON pr.material_id = rm.material_id
                       LEFT JOIN raw_material_inventory rmi ON rm.material_id = rmi.material_id
                       WHERE pr.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recipe[] = $row;
}

echo json_encode($recipe);

$stmt->close();
$conn->close();
?>