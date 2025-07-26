<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedServer = $_POST['server'] ?? null;
    $username       = trim($_POST['username'] ?? '');
    $password       = trim($_POST['password'] ?? '');

    if (!$selectedServer || !$username || !$password) {
        header("Location: index.html?error=MissingCredentials");
        exit;
    }

    $conn = connectToDatabase($selectedServer);

    if ($conn === false) {
        die("خطأ في الاتصال بقاعدة البيانات.");
    }

    $sql = "SELECT * FROM Users WHERE UserLogIn = ? AND UserPassword = ?";
    $params = [$username, $password];  // ⚠️ يجب تحسين التحقق لاحقاً باستخدام تشفير كلمات المرور
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die("خطأ في الاستعلام: " . print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_has_rows($stmt)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['server']   = $selectedServer;

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        header("Location: dashboard.php");
        exit;
    } else {
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        header("Location: index.html?error=InvalidCredentials");
        exit;
    }
} else {
    header("Location: index.html");
    exit;
}
?>
