<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';


$sql_customer_details = "SELECT * FROM customer_details";

// Execute the query
$result = $conn->query($sql_customer_details);

?>


<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Customer Id</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Contacts</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Date_updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
        if ($result->num_rows > 0) {
            // Output data of each row
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>".$row["Customer_ID"]."</td>
                    <td>".$row["Name"]."</td>
                    <td>".$row["Address"]."</td>
                    <td>".$row["Contacts"]."</td>
                    <td>".$row["Status"]."</td>
                    <td>".$row["Date_created"]."</td>
                    <td>".($row["Date_updated"] ? $row["Date_updated"] : "NULL")."</td>
                    <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        <button class='btn-delete'><i class='fas fa-trash'></i></button>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>Customer found</td></tr>";
        }
        ?>
                    </tbody>
                </table>



<?php

// Close connection
$conn->close();

include '../addphp/footer.php';

?>








