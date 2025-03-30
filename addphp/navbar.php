<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="../styles/index_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <nav class="navbar1">
        <div class="left-section">
            <div class="logo">
                <span class="logo-icon">G</span>
                <span class="logo-text">MGS Garment</span>
            </div>
            <button class="back-btn">&#8592;</button>
            <h1 class="dboard">Dashboard</h1>
        </div>

        <div class="right-section">
            <input type="text" class="search" placeholder="Search" />
            <button class="notif-btn">&#128276;</button>
            <div class="profile">
                <span class="profile-icon">G</span>
                <div>
                    <strong>John Alex</strong><br>
                    <small>Product manager</small>
                </div>
            </div>
        </div>
    </nav>


    <div class="dashboard-container">
        <aside class="sidebar">
            <nav>
                <ul class="menu-items">
                    <li class="active"><a href="#" ><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="items.php"><i class="fa-solid fa-shirt"></i></i> Items</a></li>
                    <li><a href="order.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="Backorders.php"><i class="fas fa-exchange-alt"></i> Backorders</a></li>
                    <li><a href="Stock.php"><i class="fas fa-boxes"></i> Stock</a></li>
                    <li><a href="Purchasesorders.php"><i class="fas fa-file-invoice-dollar"></i> Purchases orders</a></li>
                    <li><a href="Receiveorders.php"><i class="fas fa-truck"></i> Receive orders</a></li>
                    <li><a href="Finalproduct.php"><i class="fa-solid fa-check-to-slot"></i> Final product</a></li>
                    <li><a href="Sales.php"><i class="fas fa-chart-line"></i> Sales</a></li>
                    <li><a href="Users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="Setting.php"><i class="fas fa-cog"></i> Setting</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="table-container">
                <div class="table-header">
                </div>

    <script src="../js/index_script.js"></script>
</body>
</html>