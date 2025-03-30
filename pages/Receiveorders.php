<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sql_receiving_order_details = "SELECT * FROM receiving_order_details";

// Execute the query
$result = $conn->query($sql_receiving_order_details);


?>

<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Receiving Order ID</th>
                            <th>Form ID</th>
                            <th>Form Order</th>
                            <th>Amount</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    if ($result->num_rows > 0) {
                         // Output data of each row
                     while($row = $result->fetch_assoc()) {

             
                     echo "<tr>
                        <td>".$row["Receiving_Order_ID"]."</td>
                        <td>".$row["form_ID"]."</td>
                        <td>".$row["form_order"]."</td>
                        <td>".$row["Amount"]."</td>
                        <td>".$row["Discount"]."</td>
                        <td>".$row["Tax"]."</td>
                        <td>".$row["Date"]."</td>
                       
                         </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No back orders found</td></tr>";
        }
        ?>

        </tbody>
     </table>




<?php
include '../addphp/footer.php';
?>