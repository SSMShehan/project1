<?php
include '../addphp/navbar.php';
?>
        <link rel="stylesheet" href="../styles/dashboard_style.css">
    <div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
        <h1>Welcome to Stock Management System - MGS Garment</h1>
    </div>

    <!-- Cards Grid -->
    <div class="cards-grid">
        <!-- Items Card -->
            <a href="Item.php">
                <div class="dashboard-card">
                
                    <div class="card-icon1">
                        <i class="fa-solid fa-shirt"></i>
                    </div>
                    <div class="card-content">
                        <h3>Items</h3>
                        <div class="card-value">125</div>
                    </div>
                
                </div>
            </a>
        

        <!-- Orders Card -->
        <a href="Orders.php">
            <div class="dashboard-card">
            <div class="card-icon2">
            <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-content">
                <h3>Orders</h3>
                <div class="card-value">18</div>
            </div>
            </div>
        </a>

        <!-- Backorders Card -->
        <a href="Backorders.php">
            <div class="dashboard-card warning">
            <div class="card-icon3">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="card-content">
                <h3>Backorders</h3>
                <div class="card-value">5</div>
            </div>
            </div>
        </a>

        <!-- Stocks Card -->
        <a href="Stock.php">
            <div class="dashboard-card">
            <div class="card-icon4">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="card-content">
                <h3>Stocks</h3>
                <div class="card-value">342</div>
            </div>
            </div>
        </a>

        <!-- Purchase Orders Card -->
        <a href="Purchasesorders.php">
            <div class="dashboard-card">
            <div class="card-icon5">
            <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="card-content">
                <h3>Purchase Orders</h3>
                <div class="card-value">7</div>
            </div>
            </div>
        </a>

        <!-- Receive Orders Card -->
        <a href="Receviveorders.php">
            <div class="dashboard-card highlight">
            <div class="card-icon6">
            <i class="fas fa-truck"></i>
            </div>
            <div class="card-content">
                <h3>Receive Orders</h3>
                <div class="card-value">4</div>
            </div>
            </div>
        </a>

        <!-- Final Products Card -->
        <a href=Finalproduct.php">
            <div class="dashboard-card">
            <div class="card-icon7">
            <i class="fa-solid fa-check-to-slot"></i>
            </div>
            <div class="card-content">
                <h3>Final Products</h3>
                <div class="card-value">89</div>
            </div>
            </div>
        </a>

        <!-- Sales Card -->
        <a href="Sales.php">
            <div class="dashboard-card success">
                <div class="card-icon8">
                <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3>Sales</h3>
                    <div class="card-value">2</div>
                </div>
            </div>
        </a>

        <!-- Users Card -->
        <a href="Users.php">
            <div class="dashboard-card">
                <div class="card-icon9">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3>Users</h3>
                    <div class="card-value">4</div>
                </div>
            </div>
        </a>
    </div>
    </div>
    </div>
    
    <!-- <script src="../js/index_script.js"></script> -->
</body>
</html>