<?php
include '../addphp/navbar.php';

?>

<link rel="stylesheet" href="../styles/setting_style.css">

<div class="system-info-container">
        <h1 class="system-title">Stock Management System - MGS Garment</h1>
        
        <div class="info-section">
            <h2>System Information</h2>
            
            <div class="info-card">
                <div class="info-group">
                    <label>System Name</label>
                    <div class="info-value">Stock Management System-MGS Garment</div>
                </div>
                
                <div class="info-group">
                    <label>System Short name</label>
                    <div class="info-value">MGS Garment-SMS</div>
                </div>
                
                <button class="update-btn">Update</button>
            </div>
        </div>
    </div>


<?php

// Close connection
$conn->close();

include '../addphp/footer.php';

?>