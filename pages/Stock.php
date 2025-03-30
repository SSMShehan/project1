<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sql_stock_details = "SELECT * FROM stock_details";

// Execute the query
$result = $conn->query($sql_stock_details);

?>

<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Stock Id</th>
                            <th>Item Id</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Type</th>
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
                echo "<tr>
                    <td>".$row["Stock_ID"]."</td>
                    <td>".$row["Item_ID"]."</td>
                    <td>".$row["Quantity"]."</td>
                    <td>".$row["unit"]."</td>
                    <td>".$row["type"]."</td>
                    <td>".$row["Date_created"]."</td>
                    <td>".($row["Date_updated"] ? $row["Date_updated"] : "NULL")."</td>
                    <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No Stock details found</td></tr>";
        }
        ?>
                    </tbody>
                </table>



<?php
// Close connection
$conn->close();

include '../addphp/footer.php';

?>