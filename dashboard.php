<?php
session_start();
require_once 'db_connection.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit;
}

$username = $_SESSION['username'];
$selectedServer = $_SESSION['server'] ?? '1'; // القيمة الافتراضية للسيرفر

// جلب بيانات المستخدم حسب اسم الدخول
$user = getUserByUsername($selectedServer, $username);

// جلب اسم الكلية حسب FacultyID
$facultyName = 'غير معروف';
if (!empty($user['FacultyID'])) {
    $faculty = getFacultyById($selectedServer, $user['FacultyID']);
    if ($faculty && isset($faculty['FacultyName'])) {
        $facultyName = $faculty['FacultyName'];
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>نظام الشهادات - لوحة التحكم</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            direction: rtl;
            background: #f4f6f9;
        }
        .sidebar {
            position: fixed;
            top: 0; right: 0;
            width: 260px; height: 100vh;
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 30px 20px;
            box-sizing: border-box;
            overflow-y: auto;
            box-shadow: 3px 0 8px rgba(0,0,0,0.1);
            z-index: 1000;
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
            margin-bottom: 30px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            margin-bottom: 15px;
        }
        .sidebar ul li a {
            color: #ecf0f1;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #34495e;
        }
        .main-content {
            margin-right: 280px;
            padding: 40px 30px;
            min-height: 100vh;
        }
        h1 {
            margin-top: 0;
            color: #34495e;
        }
        h3 {
            margin-top: 5px;
            color: #555;
            font-weight: normal;
        }
        .card {
            background: white;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>


    <main class="main-content" role="main">
        <h1>مرحباً، <?= htmlspecialchars($username); ?></h1>
        <h3>الكلية: <?= htmlspecialchars($facultyName); ?></h3>

        <div class="card">
            <h2>احصائيات الكلية</h2>
        </div>
        <div class="card">
            <h2>##</h2>
        </div>
        <div class="card">
            <h2>##</h2>
        </div>
    </main>
</body>
</html>
