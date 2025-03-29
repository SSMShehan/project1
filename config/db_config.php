<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ims db";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name); // Create connection

// Check connection
if ($conn->connect_error) 
{
    die("Connection failed: " . $conn->connect_error);
}

?>
