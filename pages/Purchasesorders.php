<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sql_purchase_details = "SELECT * FROM purchase_details";

// Execute the query
$result = $conn->query($sql_purchase_details);



?>

<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Purchase_ID</th>
                            <th>Po_Codes</th>
                            <th>Supplier_ID</th>
                            <th>Amount</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    if ($result->num_rows > 0) {
                         // Output data of each row
                     while($row = $result->fetch_assoc()) {

             
                     echo "<tr>
                        <td>".$row["Purchase_ID"]."</td>
                        <td>".$row["Po_codes"]."</td>
                        <td>".$row["Supplier_ID"]."</td>
                        <td>".$row["Amount"]."</td>
                        <td>".$row["Price"]."</td>
                        <td>".$row["Discount"]."</td>
                        <td>".$row["Tax"]."</td>
                        <td>".$row["Date"]."</td>
                       <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        
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
include '../addphp/footer.php';
?>