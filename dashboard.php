<?php
session_start();

// Check if user is logged in, otherwise redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html");
    exit;
}

// Display dashboard content
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الشهادات</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            direction: rtl;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }
        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar .logo img {
            width: 100px;
        }
        .sidebar h2 {
            text-align: center;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li {
            padding: 15px 0;
            text-align: center;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .sidebar ul li a:hover {
            background-color: #34495e;
        }
        .main-content {
            flex-grow: 1;
            padding: 20px;
            box-sizing: border-box;
        }
        .main-content h1 {
            margin-top: 0;
        }
        .card {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="img/AdminLTELogo.png">
        </div>
        <h2>نظام الشهادات </h2>
        <ul>
            <li><a href="#">لوحة التحكم</a></li>
            <li><a href="student.php">المستخدمين</a></li>
            <li><a href="#">الإعدادات</a></li>
            <li><a href="#">التقارير</a></li>
            <li><a href="logout.php">تسجيل الخروج</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>مرحباً، <?php echo htmlspecialchars($username);?></h1>
        <div class="card">
            <h2>نظرة عامة على لوحة التحكم</h2>
            <p>هذه هي القالب الأساسي للوحة الإدارة. قم بتخصيصه ليناسب احتياجاتك.</p>
        </div>
        <div class="card">
            <h2>المستخدمين</h2>
            <p>قم بإدارة المستخدمين هنا.</p>
        </div>
        <div class="card">
            <h2>الإعدادات</h2>
            <p>قم بتعديل إعداداتك هنا.</p>
        </div>
    </div>
</body>
</html>

