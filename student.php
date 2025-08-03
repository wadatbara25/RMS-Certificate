<?php
session_start();
require_once 'db_connection.php';

// دالة تأمين النصوص
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// التحقق من تسجيل الدخول
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit();
}

$selectedServer = $_SESSION['server'] ?? '1';
$username = $_SESSION['username'] ?? '';

// جلب بيانات الكلية
$user = getUserByUsername($selectedServer, $username);
$facultyName = 'غير معروف';
$facultyID = '';
if (!empty($user['FacultyID'])) {
    $faculty = getFacultyById($selectedServer, $user['FacultyID']);
    if ($faculty && isset($faculty['FacultyName'])) {
        $facultyName = $faculty['FacultyName'];
        $facultyID = $faculty['FacultyID'];
    }
}

// البحث عن الطلاب
$searchQuery = $_POST["search_query"] ?? "";
$users = ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($searchQuery))
    ? searchUsers($selectedServer, $searchQuery)
    : getAllStudents($selectedServer);

$errorMessage = '';
if (empty($users)) {
    $errorMessage = "لا توجد نتائج لعرضها.";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>قائمة الطلاب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            direction: rtl;
            background-color: #f4f6f9;
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
        .table-responsive {
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .popup {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            z-index: 9999;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .popup-content {
            background: #fff;
            padding: 25px 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 350px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            font-size: 16px;
        }
        .popup-content button {
            margin-top: 15px;
            background-color: #d9534f;
            border: none;
            padding: 10px 20px;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }
        .popup-content button:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content" role="main">
    <h3>قائمة طلاب كلية: <?= e($facultyName) ?></h3>

    <div class="table-responsive">
        <table id="studentsTable" class="table table-striped table-bordered" >
            <thead class="table-dark" >
                <tr >
                    <th rowspan="2" class="text-center">رقم الطالب</th>
                    <th rowspan="2" class="text-center">الاسم</th>
                    <th colspan="2" class="text-center">العامة</th>
                    <th colspan="2" class="text-center">التفاصيل</th>
                    <th colspan="2" class="text-center">السجل</th>
                </tr>
                <tr>
                    <th>عربي</th>
                    <th>إنجليزي</th>
                    <th>عربي</th>
                    <th>إنجليزي</th>
                    <th>عربي</th>
                    <th>إنجليزي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user["StudentID"] ?? '') ?></td>
                        <td><?= e($user["StudentName"] ?? '') ?></td>
                        <td><a class="btn btn-success btn-sm" href="<?= getFacilityLink($facultyID, 'ar', 'general') ?>?id=<?= urlencode($user["StudentID"] ?? '') ?>">عربية</a></td>
                        <td><a class="btn btn-info btn-sm" href="<?= getFacilityLink($facultyID, 'en', 'general') ?>?id=<?= urlencode($user["StudentID"] ?? '') ?>">إنجليزية</a></td>
                        <td><a class="btn btn-success btn-sm" href="<?= getFacilityLink($facultyID, 'ar', 'transcript') ?>?id=<?= urlencode($user["StudentID"] ?? '') ?>">عربي</a></td>
                        <td><a class="btn btn-info btn-sm" href="<?= getFacilityLink($facultyID, 'en', 'transcript') ?>?id=<?= urlencode($user["StudentID"] ?? '') ?>">إنجليزي</a></td>
                        <td><a class="btn btn-info btn-sm" href="AcademicAr.php?id=<?= urlencode($user["StudentID"] ?? '') ?>">ع</a></td>
                        <td><a class="btn btn-info btn-sm" href="AcademicEn.php?id=<?= urlencode($user["StudentID"] ?? '') ?>">En</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php if ($errorMessage !== ''): ?>
<div id="errorPopup" class="popup">
    <div class="popup-content" role="alert" aria-live="assertive" aria-atomic="true">
        <h3>تنبيه</h3>
        <p><?= e($errorMessage) ?></p>
        <button type="button" onclick="document.getElementById('errorPopup').style.display='none'">إغلاق</button>
    </div>
</div>
<?php endif; ?>

<!-- سكريبتات -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#studentsTable').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
        }
    });
});
</script>
</body>
</html>
