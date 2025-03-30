<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';


$sql_back_order_details = "SELECT * FROM back_order_details";

// Execute the query
$result = $conn->query($sql_back_order_details);

?>


<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Back Order Id</th>
                            <th>Customer Id</th>
                            <th>Item Id</th>
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
                echo "<tr>
                    <td>".$row["Back_Order_ID"]."</td>
                    <td>".$row["Customer_ID"]."</td>
                    <td>".$row["Item_ID"]."</td>
                    <td>".$row["Quantity"]."</td>
                    <td>".$row["Date_created"]."</td>
                    <td>".($row["Date_updated"] ? $row["Date_updated"] : "NULL")."</td>
                    <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        <button class='btn-delete'><i class='fas fa-trash'></i></button>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No back orders found</td></tr>";
        }
        ?>
                    </tbody>
                </table>



<?php

// Close connection
$conn->close();

include '../addphp/footer.php';
/*require_once 'config/db_config.php';*/
?>