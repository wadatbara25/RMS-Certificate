<?php
// sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// يمكن وضع تحميل بيانات الطالب هنا إذا لزم الأمر

?>

<link rel="stylesheet" href="css/sidebar.css" />

<aside class="sidebar" role="navigation" aria-label="القائمة الجانبية">
    <div class="logo" aria-label="شعار النظام">
        <img src="../img/AdminLTELogo.png" alt="شعار نظام الشهادات" />
    </div>
    <h2>نظام بيانات الطلاب</h2>
    <ul>
        <li><a href="../student.php">طباعة الشهادات</a></li>
        <li><a href="../university_payment_api/admin_payments.php">الرسوم الدراسية</a></li>
        <li><a href="#">التقارير</a></li>
        <li><a href="../dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">لوحة التحكم</a></li>
        <li><a href="../logout.php">تسجيل الخروج</a></li>
    </ul>
</aside>
</head>
          
