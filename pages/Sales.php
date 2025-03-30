<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sql_sales_list = "SELECT * FROM sales_list";

// Execute the query
$result = $conn->query($sql_sales_list);

?>

<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Sale Id</th>
                            <th>Item Id</th>
                            <th>Sales Codes</th>
                            <th>Client</th>
                            <th>Price</th>
                            <th>Amount</th>
                            <th>Stock Id</th>
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
                    <td>".$row["Sales_ID"]."</td>
                    <td>".$row["Item_ID"]."</td>
                    <td>".$row["Sales_codes"]."</td>
                    <td>".$row["Client"]."</td>
                    <td>".$row["Price"]."</td>
                    <td>".$row["Amount"]."</td>
                    <td>".$row["Stock_ID"]."</td>
                    <td>".$row["Date"]."</td>
                    <td>
                        <button class='btn-edit'><i class='fas fa-edit'></i></button>
                        <button class='btn-delete'><i class='fas fa-trash'></i></button>
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