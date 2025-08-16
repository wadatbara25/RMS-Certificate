<?php
session_start();

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit();
}

$serverName = "adminmanaer.database.windows.net";
$connectionOptions = [
    "Database" => "StudentAllForPayment",
    "Uid" => "admini",
    "PWD" => "P@ssw0rd",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات: " . print_r(sqlsrv_errors(), true));
}

$facultyFilter = $_GET['faculty'] ?? '';
$batchFilter = $_GET['batch'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$perPage = (int)($_GET['limit'] ?? 10);
$page = max(1, (int)($_GET['page'] ?? 1));

$sql = "SELECT StudentID, StudentName, StudyFees, RegistrationFees, FacultyName, BatchName, SemesterID FROM dbo.tbl_StudentAllForPayment WHERE 1=1";
$params = [];

if ($facultyFilter !== '') {
    $sql .= " AND FacultyName = ?";
    $params[] = $facultyFilter;
}
if ($batchFilter !== '') {
    $sql .= " AND BatchName = ?";
    $params[] = $batchFilter;
}

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("خطأ في تنفيذ الاستعلام: " . print_r(sqlsrv_errors(), true));
}

$students = [];
$facultyList = [];
$batchList = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $students[] = $row;
    if (!in_array($row['FacultyName'], $facultyList) && $row['FacultyName'] !== '') {
        $facultyList[] = $row['FacultyName'];
    }
    if (!in_array($row['BatchName'], $batchList) && $row['BatchName'] !== '') {
        $batchList[] = $row['BatchName'];
    }
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

sort($facultyList);
sort($batchList);

if ($keyword !== '') {
    $students = array_filter($students, fn($s) =>
        stripos($s['StudentName'], $keyword) !== false || stripos($s['StudentID'], $keyword) !== false
    );
}

$totalRecords = count($students);
$totalPages = ceil($totalRecords / $perPage);
$start = ($page - 1) * $perPage;
$studentsPage = array_slice($students, $start, $perPage);

$totalStudyFees = array_sum(array_map(fn($s) => $s['StudyFees'] ?? 0, $students));
$totalRegistrationFees = array_sum(array_map(fn($s) => $s['RegistrationFees'] ?? 0, $students));
$totalCombinedFees = $totalStudyFees + $totalRegistrationFees;

if (isset($_GET['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=students_fees.xls");
    echo "<table border='1'>";
    echo "<tr><th>رقم الطالب</th><th>اسم الطالب</th><th>الرسوم الدراسية</th><th>رسوم التسجيل</th><th>المجموع</th><th>الفصل الدراسي</th></tr>";
    foreach ($students as $student) {
        $study = $student['StudyFees'] ?? 0;
        $reg = $student['RegistrationFees'] ?? 0;
        echo "<tr>";
        echo "<td>" . e($student['StudentID']) . "</td>";
        echo "<td>" . e($student['StudentName']) . "</td>";
        echo "<td>" . number_format($study) . "</td>";
        echo "<td>" . number_format($reg) . "</td>";
        echo "<td>" . number_format($study + $reg) . "</td>";
        echo "<td>" . e($student['SemesterID']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>بيانات الطلاب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
<div class="container">

    <h3 class="mb-4">بيانات الطلاب </h3>

    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
            <select name="faculty" class="form-select">
                <option value="">كل الكليات</option>
                <?php foreach ($facultyList as $f): ?>
                    <option value="<?= e($f) ?>" <?= $facultyFilter === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="batch" class="form-select">
                <option value="">كل الدفعات</option>
                <?php foreach ($batchList as $b): ?>
                    <option value="<?= e($b) ?>" <?= $batchFilter === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="keyword" class="form-control" placeholder="بحث بالاسم أو الرقم" value="<?= e($keyword) ?>" />
        </div>
        <div class="col-md-1">
            <select name="limit" class="form-select">
                <?php foreach ([10,20,50,100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">تصفية</button>
            <a href="?" class="btn btn-secondary">إعادة</a>
        </div>
    </form>

    <div class="mb-3">
        <a href="?faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>&export_excel=1" class="btn btn-success">تصدير إلى Excel</a>
    </div>

    <?php if (empty($studentsPage)): ?>
        <div class="alert alert-warning">⚠️ لا توجد بيانات طلاب.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                <tr>
                    <th>رقم الطالب</th>
                    <th>اسم الطالب</th>
                    <th>الرسوم الدراسية</th>
                    <th>رسوم التسجيل</th>
                    <th>المجموع</th>
                    <th>الفصل الدراسي</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($studentsPage as $student): ?>
                    <tr>
                        <td><?= e($student['StudentID'] ?? '') ?></td>
                        <td><?= e($student['StudentName'] ?? '') ?></td>
                        <td><?= number_format($student['StudyFees'] ?? 0) ?></td>
                        <td><?= number_format($student['RegistrationFees'] ?? 0) ?></td>
                        <td><?= number_format(($student['StudyFees'] ?? 0) + ($student['RegistrationFees'] ?? 0)) ?></td>
                        <td><?= e($student['SemesterID'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary">
                <tr>
                    <th colspan="2">الإجمالي (عدد الطلاب: <?= $totalRecords ?>)</th>
                    <th><?= number_format($totalStudyFees) ?></th>
                    <th><?= number_format($totalRegistrationFees) ?></th>
                    <th><?= number_format($totalCombinedFees) ?></th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>

        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>">&laquo; السابق</a>
                </li>

                <?php
                $range = 2;
                $startPage = max(1, $page - $range);
                $endPage = min($totalPages, $page + $range);
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>">التالي &raquo;</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>
</body>
</html>
