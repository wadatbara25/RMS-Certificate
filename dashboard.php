<?php
session_start();

// تحقق من تسجيل الدخول
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];

// مثال إحصائيات وهمية (يجب ربطها لاحقًا بقاعدة البيانات)
$totalUsers = 128;
$totalCertificates = 350;
$certificatesToday = 12;
$totalAdmins = 5;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة التحكم - نظام الشهادات</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

  <style>
    body {
      margin: 0;
      font-family: 'Cairo', sans-serif;
      background-color: #f4f6f9;
      display: flex;
      height: 100vh;
    }

    .sidebar {
      width: 250px;
      background-color: #2c3e50;
      color: white;
      display: flex;
      flex-direction: column;
      padding: 20px;
    }

    .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo img {
      width: 80px;
    }

    .sidebar h2 {
      text-align: center;
      font-size: 20px;
      margin-bottom: 30px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar ul li {
      margin: 10px 0;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px 15px;
      border-radius: 6px;
      transition: background-color 0.3s;
    }

    .sidebar ul li a:hover {
      background-color: #1a252f;
    }

    .main-content {
      flex-grow: 1;
      padding: 30px;
      overflow-y: auto;
    }

    .main-content h1 {
      margin-top: 0;
      font-size: 28px;
      margin-bottom: 25px;
      color: #333;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background-color: #ffffff;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 20px;
      text-align: center;
    }

    .stat-card h3 {
      font-size: 16px;
      color: #777;
      margin-bottom: 10px;
    }

    .stat-card .value {
      font-size: 32px;
      font-weight: bold;
      color: #2c3e50;
    }

    .card {
      background-color: white;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .card h2 {
      margin-top: 0;
      font-size: 20px;
      margin-bottom: 10px;
      color: #2c3e50;
    }

    .card p {
      color: #555;
      font-size: 15px;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="logo">
      <img src="img/AdminLTELogo.png" alt="شعار">
    </div>
    <h2>نظام الشهادات</h2>
    <ul>
      <li><a href="#">لوحة التحكم</a></li>
      <li><a href="student.php">المستخدمين</a></li>
      <li><a href="#">الإعدادات</a></li>
      <li><a href="#">التقارير</a></li>
      <li><a href="logout.php">تسجيل الخروج</a></li>
    </ul>
  </div>

  <div class="main-content">
    <h1>مرحباً، <?php echo htmlspecialchars($username); ?> 👋</h1>

    <!-- بطاقات الإحصائيات -->
    <div class="stats">
      <div class="stat-card">
        <h3>عدد المستخدمين</h3>
        <div class="value"><?php echo $totalUsers; ?></div>
      </div>
      <div class="stat-card">
        <h3>عدد الشهادات</h3>
        <div class="value"><?php echo $totalCertificates; ?></div>
      </div>
      <div class="stat-card">
        <h3>الشهادات اليوم</h3>
        <div class="value"><?php echo $certificatesToday; ?></div>
      </div>
      <div class="stat-card">
        <h3>عدد الإداريين</h3>
        <div class="value"><?php echo $totalAdmins; ?></div>
      </div>
    </div>

    <!-- الكروت الأخرى -->
    <div class="card">
      <h2>نظرة عامة على لوحة التحكم</h2>
      <p>مرحبا بك في نظام إدارة الشهادات. يمكنك من خلال هذه اللوحة إدارة البيانات، الوصول للتقارير، وتعديل الإعدادات.</p>
    </div>

    <div class="card">
      <h2>المستخدمين</h2>
      <p>عرض، تعديل أو حذف بيانات المستخدمين.</p>
    </div>

    <div class="card">
      <h2>الإعدادات</h2>
      <p>تخصيص إعدادات النظام حسب الحاجة.</p>
    </div>
  </div>
</body>

</html>