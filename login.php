<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedServer = $_POST['server'];

    // Establish database connection
    $conn = connectToDatabase($selectedServer);

    // Collect form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials against database
    $sql = "SELECT * FROM Users WHERE UserLogIn = ? AND UserPassword = ?";
    $params = array($username, $password);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Check if a row was returned
    if (sqlsrv_has_rows($stmt)) {
        // Password is correct, set session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION["server"] = $selectedServer;
        // Redirect to dashboard
        header("Location: dashboard.php");
        header("Location: student.php");
        exit;

      

    } else {
        // Password is incorrect or username not found
        header("Location: index.html?error=InvalidCredentials");
        exit;
    }

    // Close statement and connection
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

} else {
    // If someone tries to access login.php directly, redirect them to the login page
    header("Location: index.html");
    exit;
}
?>
