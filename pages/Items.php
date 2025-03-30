<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Handle item update request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["updateItem"])) {
    $itemID = $_POST["Item_ID"];
    $name = $_POST["Name"];
    $description = $_POST["Description"];
    $cost = $_POST["Cost"];
    $status = $_POST["Status"];

    $sql = "UPDATE item_details SET Name = ?, Description = ?, Cost = ?, Status = ? WHERE Item_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdsi', $name, $description, $cost, $status, $itemID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }

    $stmt->close();
    exit;
}

// Handle item delete request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["deleteItem"])) {
    $itemID = $_POST["Item_ID"];

    $sql = "DELETE FROM item_details WHERE Item_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $itemID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }

    $stmt->close();
    exit;
}

// Fetch item details
$sql_item_details = "SELECT * FROM item_details";
$result = $conn->query($sql_item_details);
?>

<!-- The rest of your HTML code remains the same, including the table -->
<head>
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

button#deleteItemBtn {
    background-color:rgb(170, 64, 75);
    color: white;
    margin-top: 10px;
}

button#deleteItemBtn:hover {
    background-color:rgb(255, 0, 25);
}

    </style>
</head>

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
        <?php while ($row = $result->fetch_assoc()) : ?>
        <tr data-id="<?= $row["Item_ID"] ?>">
            <td><?= $row["Item_ID"] ?></td>
            <td><?= $row["Name"] ?></td>
            <td><?= $row["Description"] ?></td>
            <td><?= $row["Cost"] ?></td>
            <td><span class='status <?= strtolower($row["Status"]) === 'active' ? 'in-stock' : 'out-of-stock' ?>'><?= $row["Status"] ?></span></td>
            <td><?= $row["Date_created"] ?></td>
            <td>
                <button class='btn-edit'><i class='fas fa-edit'></i></button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Edit Modal with Delete Button -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Edit Item</h2>
        <form id="editForm">
            <input type="hidden" id="editItemID" name="Item_ID">
            <label for="editItemName">Item Name:</label>
            <input type="text" id="editItemName" name="Name">
            <label for="editItemDescription">Description:</label>
            <input type="text" id="editItemDescription" name="Description">
            <label for="editItemCost">Price:</label>
            <input type="text" id="editItemCost" name="Cost">
            <label for="editItemStatus">Status:</label>
            <select id="editItemStatus" name="Status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button type="submit">Update</button>
            <button type="button" id="deleteItemBtn" >Delete</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const closeModalBtn = document.querySelector(".close-btn");
    const editForm = document.getElementById("editForm");
    const deleteItemBtn = document.getElementById("deleteItemBtn");

    // Edit item handler
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            document.getElementById("editItemID").value = row.dataset.id;
            document.getElementById("editItemName").value = row.cells[1].textContent;
            document.getElementById("editItemDescription").value = row.cells[2].textContent;
            document.getElementById("editItemCost").value = row.cells[3].textContent;
            document.getElementById("editItemStatus").value = row.cells[4].textContent.trim().toLowerCase();
            modal.classList.add("show");
        });
    });

    closeModalBtn.addEventListener("click", () => { modal.classList.remove("show"); });

    // Edit form submission handler
    editForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(editForm);
        formData.append("updateItem", true);

        fetch("items.php", { method: "POST", body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-id='${formData.get("Item_ID")}'`);
                row.cells[1].textContent = formData.get("Name");
                row.cells[2].textContent = formData.get("Description");
                row.cells[3].textContent = formData.get("Cost");
                row.cells[4].innerHTML = `<span class='status ${formData.get("Status") === "active" ? "in-stock" : "out-of-stock"}'>${formData.get("Status")}</span>`;
                modal.classList.remove("show");
            } else {
                alert("Failed to update the item: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    });

    // Delete item handler inside the modal
    deleteItemBtn.addEventListener('click', function () {
        const itemId = document.getElementById("editItemID").value;

        // Send the delete request via AJAX
        fetch("items.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ deleteItem: true, itemID: itemId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the modal
                modal.classList.remove("show");
                // Remove the item row from the table
                const row = document.querySelector(`tr[data-id='${itemId}']`);
                row.remove();
            } else {
                alert("Failed to delete the item: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    });
});


</script>

<?php
include '../addphp/footer.php';
?>
