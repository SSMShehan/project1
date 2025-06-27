<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "inventory_management_system";

$conn = new mysqli('localhost', 'root', '', 'inventory_management_system', 3307); // Create connection

// Check connection
if ($conn->connect_error) 
{
    die("Connection failed: " . $conn->connect_error);
}

?>





