<?php
include '../addphp/navbar.php';
require_once '../config/db_config.php';

$sqlforname = "SELECT system_short_name FROM system_settings WHERE id = 1";
$resultforname = $conn->query($sqlforname);

if ($resultforname && $resultforname->num_rows > 0) {
    $row = $resultforname->fetch_assoc(); 
    $companyName = $row['system_short_name'];
    
}

// Initialize variables
$systemName = "Stock Management System-MGS Garment";
$systemShortName = "MGS Garment-SMS";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $systemName = $_POST["system_name"];
    $systemShortName = $_POST["system_short_name"];

    // Prepare update statement (assuming you have a table named 'system_settings')
    $sql = "UPDATE system_settings SET 
            system_name = ?, 
            system_short_name = ? 
            WHERE id = 1"; // Assuming there's only one row with ID 1
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $systemName, $systemShortName);

    if ($stmt->execute()) {
        echo "<script>window.location.href = 'Setting.php';</script>";
    } else {
        echo "<script>alert('Error updating system information: " . $conn->error . "');</script>";
    }
    $stmt->close();
}

// Fetch current system settings (assuming you have a table named 'system_settings')
$sql = "SELECT * FROM system_settings WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $systemName = $row['system_name'];
    $systemShortName = $row['system_short_name'];
}
?>

<link rel="stylesheet" href="../styles/setting_style.css">

<div class="system-info-container">
    <h1 class="system-title"><?php echo htmlspecialchars($systemName); ?> - <?php echo htmlspecialchars($companyName); ?></h1>
    
    <div class="info-section">
        <h2>System Information</h2>
        
        <form method="POST" class="info-card">
            <div class="info-group">
                <span>System Name</span><br><br>
                <input type="text" name="system_name" value="<?php echo htmlspecialchars($systemName); ?>"   placeholder="System Name">
            </div>
            
            <div class="info-group">
            <span>System Short-Name</span><br><br>
                <input type="text" name="system_short_name" value="<?php echo htmlspecialchars($systemShortName); ?>" maxlength="13" placeholder="System Short Name">
            </div>
            
            <button type="submit" class="update-btn">Update</button>
        </form>
    </div>
</div>

<?php
// Close connection
$conn->close();


?>