<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Handle back order update request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["updateBackOrder"])) {
    $backOrderID = $_POST["Back_Order_ID"];
    $customerID = $_POST["Customer_ID"];
    $itemID = $_POST["Item_ID"];
    $quantity = $_POST["Quantity"];

    $sql = "UPDATE back_order_details SET Customer_ID = ?, Item_ID = ?, Quantity = ?, Date_updated = NOW() WHERE Back_Order_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $customerID, $itemID, $quantity, $backOrderID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }

    $stmt->close();
    exit;
}

// Handle back order delete request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["deleteBackOrder"])) {
    $backOrderID = $_POST["Back_Order_ID"];

    $sql = "DELETE FROM back_order_details WHERE Back_Order_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $backOrderID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }

    $stmt->close();
    exit;
}

// Fetch back order details
$sql_back_order_details = "SELECT * FROM back_order_details";
$result = $conn->query($sql_back_order_details);
?>

<style>
    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s ease-in-out;
    }

    .modal.show {
        display: flex;
        opacity: 1;
    }

    .modal-content {
        background-color: #fff;
        width: 50%;
        max-width: 500px;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
        position: relative;
        transform: translateY(-20px);
        transition: transform 0.3s ease-in-out;
    }

    .modal.show .modal-content {
        transform: translateY(0);
    }

    /* Close Button */
    .close-btn {
        position: absolute;
        top: 12px;
        right: 15px;
        font-size: 22px;
        font-weight: bold;
        cursor: pointer;
        color: #666;
        transition: color 0.3s ease-in-out;
    }

    .close-btn:hover {
        color: #000;
    }

    /* Form Styling */
    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    label {
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }

    input, select {
        width: 100%;
        padding: 10px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 5px;
        transition: all 0.3s ease-in-out;
    }

    input:focus, select:focus {
        border-color: #007bff;
        box-shadow: 0px 0px 5px rgba(0, 123, 255, 0.5);
        outline: none;
    }

    /* Buttons */
    button {
        padding: 12px;
        font-size: 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease-in-out;
    }

    button[type="submit"] {
        background-color: #007bff;
        color: white;
    }

    button[type="submit"]:hover {
        background-color: #0056b3;
    }

    button#deleteBackOrderBtn {
        background-color:rgb(170, 64, 75);
        color: white;
        margin-top: 10px;
    }

    button#deleteBackOrderBtn:hover {
        background-color:rgb(255, 0, 25);
    }
</style>

<table id="backOrderTable">
    <thead>
        <tr>
            <th>Back Order ID</th>
            <th>Customer ID</th>
            <th>Item ID</th>
            <th>Quantity</th>
            <th>Date Created</th>
            <th>Date Updated</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr data-id="<?= $row["Back_Order_ID"] ?>">
                <td><?= $row["Back_Order_ID"] ?></td>
                <td><?= $row["Customer_ID"] ?></td>
                <td><?= $row["Item_ID"] ?></td>
                <td><?= $row["Quantity"] ?></td>
                <td><?= $row["Date_created"] ?></td>
                <td><?= $row["Date_updated"] ?: "NULL" ?></td>
                <td>
                    <button class='btn-edit'><i class='fas fa-edit'></i></button>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Edit Back Order</h2>
        <form id="editForm">
            <input type="hidden" id="editBackOrderID" name="Back_Order_ID">
            <label for="editCustomerID">Customer ID:</label>
            <input type="text" id="editCustomerID" name="Customer_ID">
            <label for="editItemID">Item ID:</label>
            <input type="text" id="editItemID" name="Item_ID">
            <label for="editQuantity">Quantity:</label>
            <input type="text" id="editQuantity" name="Quantity">
            <button type="submit">Update</button>
            <button type="button" id="deleteBackOrderBtn">Delete</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const closeModalBtn = document.querySelector(".close-btn");
    const editForm = document.getElementById("editForm");
    const deleteBackOrderBtn = document.getElementById("deleteBackOrderBtn");

    // Edit button handler
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            document.getElementById("editBackOrderID").value = row.dataset.id;
            document.getElementById("editCustomerID").value = row.cells[1].textContent;
            document.getElementById("editItemID").value = row.cells[2].textContent;
            document.getElementById("editQuantity").value = row.cells[3].textContent;
            modal.classList.add("show");
        });
    });

    // Close modal on clicking close button
    closeModalBtn.addEventListener("click", () => { modal.classList.remove("show"); });

    // Form submit (update back order)
    editForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(editForm);
        formData.append("updateBackOrder", true);

        fetch("back_orders.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-id='${formData.get("Back_Order_ID")}'`);
                row.cells[1].textContent = formData.get("Customer_ID");
                row.cells[2].textContent = formData.get("Item_ID");
                row.cells[3].textContent = formData.get("Quantity");
                modal.classList.remove("show");
            } else {
                alert("Failed to update the back order: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    });

    // Delete back order handler
    deleteBackOrderBtn.addEventListener('click', function () {
        const backOrderID = document.getElementById("editBackOrderID").value;

        fetch("back_orders.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ deleteBackOrder: true, Back_Order_ID: backOrderID })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.classList.remove("show");
                document.querySelector(`tr[data-id='${backOrderID}']`).remove();
            } else {
                alert("Failed to delete the back order: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    });
});
</script>

<?php include '../addphp/footer.php'; ?>
