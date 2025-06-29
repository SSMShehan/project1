<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Fetch inventory valuation data
$sql = "SELECT * FROM vw_inventory_valuation ORDER BY total_value DESC";
$result = $conn->query($sql);

// Calculate totals
$total_value = $conn->query("SELECT calculate_inventory_value() as value")->fetch_assoc()['value'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventory Valuation Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            
            color: #333;
        }
        
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
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
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
        
        .total-row {
            font-weight: bold;
            background-color: #e9ecef;
        }
        
        .value-positive {
            color: #28a745;
        }
        
        .summary-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .summary-card h3 {
            margin-top: 0;
            color: #6c757d;
            font-size: 1rem;
        }
        
        .summary-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .summary-card .label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h2>Inventory Valuation Report</h2>
    <div>
        <a href="inventory.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
    </div>
</div>

<div class="summary-card">
    <h3>Total Inventory Value</h3>
    <div class="value">Rs <?php echo number_format($total_value, 2); ?></div>
    <div class="label">Current valuation of all inventory items</div>
</div>

<table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Products</th>
            <th>Total Quantity</th>
            <th>Inventory Value</th>
            <th>Potential Revenue</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td><?php echo $row['product_count']; ?></td>
            <td><?php echo number_format($row['total_quantity']); ?></td>
            <td class="value-positive">Rs <?php echo number_format($row['total_value'], 2); ?></td>
            <td class="value-positive">Rs <?php echo number_format($row['potential_revenue'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
<?php $conn->close(); ?>