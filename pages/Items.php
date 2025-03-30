<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sql_item_details = "SELECT * FROM item_details";

// Execute the query
$result = $conn->query($sql_item_details);



?>



<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item No</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php
                    if ($result->num_rows > 0) {
                         // Output data of each row
                     while($row = $result->fetch_assoc()) {

                     // Determine status class based on the Status value
                    $statusClass = strtolower($row["Status"]) === 'active' ? 'in-stock' : 'out-of-stock';

                     echo "<tr>
                        <td>".$row["Item_ID"]."</td>
                        <td>".$row["Name"]."</td>
                        <td>".$row["Description"]."</td>
                        <td>".$row["Cost"]."</td>
                        <td><span class='status $statusClass'>".$row["Status"]."</span></td>
                        <td>".$row["Date_created"]."</td>
                        <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        
                    </td>
                       
                         </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No back orders found</td></tr>";
        }
        ?>

                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>

                         
                            
                 

                    </tbody>
                </table>



<?php
include '../addphp/footer.php';
?>