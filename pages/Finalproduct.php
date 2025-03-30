<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

// Fetch final product details from the database
$sql_final_product_details = "SELECT * FROM final_product_details";
$result = $conn->query($sql_final_product_details);

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

    button#deleteProductBtn {
        background-color: rgb(170, 64, 75);
        color: white;
        margin-top: 10px;
    }

    button#deleteProductBtn:hover {
        background-color: rgb(255, 0, 25);
    }
</style>

<table id="inventoryTable">
    <thead>
        <tr>
            <th>Product Id</th>
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
            while($row = $result->fetch_assoc()) {
                echo "<tr data-id='".$row["Product_ID"]."'>
                    <td>".$row["Product_ID"]."</td>
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
            echo "<tr><td colspan='8'>No final product details found</td></tr>";
        }
        ?>
    </tbody>
</table>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Edit Final Product</h2>
        <form id="editForm">
            <input type="hidden" id="editProductID" name="Product_ID">
            <label for="editItemID">Item ID:</label>
            <input type="text" id="editItemID" name="Item_ID">
            <label for="editQuantity">Quantity:</label>
            <input type="text" id="editQuantity" name="Quantity">
            <label for="editUnit">Unit:</label>
            <input type="text" id="editUnit" name="unit">
            <label for="editType">Type:</label>
            <input type="text" id="editType" name="type">
            <button type="submit">Update</button>
            <button type="button" id="deleteProductBtn">Delete</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const closeModalBtn = document.querySelector(".close-btn");
    const editForm = document.getElementById("editForm");
    const deleteProductBtn = document.getElementById("deleteProductBtn");

    // Edit button handler
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            document.getElementById("editProductID").value = row.dataset.id;
            document.getElementById("editItemID").value = row.cells[1].textContent;
            document.getElementById("editQuantity").value = row.cells[2].textContent;
            document.getElementById("editUnit").value = row.cells[3].textContent;
            document.getElementById("editType").value = row.cells[4].textContent;
            modal.classList.add("show");
        });
    });

    // Close modal on clicking close button
    closeModalBtn.addEventListener("click", () => { modal.classList.remove("show"); });

    // Form submit (update product)
    editForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(editForm);
        formData.append("updateProduct", true);

        fetch("update_product.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-id='${formData.get("Product_ID")}'`);
                row.cells[1].textContent = formData.get("Item_ID");
                row.cells[2].textContent = formData.get("Quantity");
                row.cells[3].textContent = formData.get("unit");
                row.cells[4].textContent = formData.get("type");
                modal.classList.remove("show");
            } else {
                alert("Failed to update the product details: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    });

    // Delete product handler
    deleteProductBtn.addEventListener('click', function () {
        const productID = document.getElementById("editProductID").value;

        fetch("delete_product.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ deleteProduct: true, Product_ID: productID })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.classList.remove("show");
                document.querySelector(`tr[data-id='${productID}']`).remove();
            } else {
                alert("Failed to delete the product: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    });
});
</script>

<?php
// Close connection
$conn->close();
include '../addphp/footer.php';
?>
