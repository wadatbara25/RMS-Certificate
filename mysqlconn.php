<?php
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$database = "uofb_senate";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query
$sql = "SELECT *,CONCAT(N1,' ',N2,' ',N3,' ',N4) as name FROM bscdip";
$result = $conn->query($sql);



// Close connection
$conn->close();
?>
