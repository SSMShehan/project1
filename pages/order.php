<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sql_order_details = "SELECT * FROM order_details";

// Execute the query
$result = $conn->query($sql_order_details);
?>


<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer ID</th>
                            <th>Item ID</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Date Created</th>
                            <th>Date Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
        if ($result->num_rows > 0) {
            // Output data of each row
            while($row = $result->fetch_assoc()) {
                // Determine status class based on the Status value
                
                echo "<tr>
                    <td>".$row["Order_ID"]."</td>
                    <td>".$row["Customer_ID"]."</td>
                    <td>".$row["Item_ID"]."</td>
                    <td>$".number_format($row["Price"], 2)."</td>
                    <td>".$row["Quantity"]."</td>
                    <td>".$row["Date_created"]."</td>
                    <td>".($row["Date_updated"] ? $row["Date_updated"] : "NULL")."</td>
                  <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No items found</td></tr>";
        }
        ?>
    

                    </tbody>
                </table>



<?php
include '../addphp/footer.php';

?>