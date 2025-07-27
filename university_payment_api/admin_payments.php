<?php
session_start();
require_once '../db_connection.php';

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit();
}

$facultyFilter = $_GET["faculty"] ?? "";
$batchFilter = $_GET["batch"] ?? "";
$keyword = $_GET["keyword"] ?? "";
$perPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$studentDataFromAllServers = [];
$totalStudyFees = 0;
$totalRegistrationFees = 0;
$totalCombinedFees = 0;

$facultyList = [];
$batchList = [];

require_once '../db_connection.php';
global $servers;
$serverList = array_keys($servers);

foreach ($serverList as $srv) {
    $conn = connectToDatabase($srv);
    if ($conn) {
        $sql = "SELECT StudentID, StudentName, StudyFees, RegistrationFees, FacultyName, BatchName, SemesterID FROM dbo.StudentAllForPayment() WHERE 1=1";
        $params = [];

        if (!empty($facultyFilter)) {
            $sql .= " AND FacultyName = ?";
            $params[] = $facultyFilter;
        }
        if (!empty($batchFilter)) {
            $sql .= " AND BatchName = ?";
            $params[] = $batchFilter;
        }

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            error_log("خطأ في استعلام StudentAllForPayment من السيرفر $srv: " . print_r(sqlsrv_errors(), true));
            continue;
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['Server'] = $srv;
            $studentDataFromAllServers[] = $row;

            if (!in_array($row['FacultyName'], $facultyList) && !empty($row['FacultyName'])) {
                $facultyList[] = $row['FacultyName'];
            }
            if (!in_array($row['BatchName'], $batchList) && !empty($row['BatchName'])) {
                $batchList[] = $row['BatchName'];
            }
        }
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
    }
}

sort($facultyList);
sort($batchList);

if (!empty($keyword)) {
    $studentDataFromAllServers = array_filter($studentDataFromAllServers, function($student) use ($keyword) {
        return stripos($student['StudentName'], $keyword) !== false ||
               stripos($student['StudentID'], $keyword) !== false;
    });
}

foreach ($studentDataFromAllServers as $row) {
    $study = $row['StudyFees'] ?? 0;
    $reg = $row['RegistrationFees'] ?? 0;
    $totalStudyFees += $study;
    $totalRegistrationFees += $reg;
    $totalCombinedFees += ($study + $reg);
}

if (isset($_GET['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=students_fees.xls");
    echo "<table border='1'>";
    echo "<tr><th>رقم الطالب</th><th>اسم الطالب</th><th>الرسوم الدراسية</th><th>رسوم التسجيل</th><th>المجموع</th><th>الفصل الدراسي</th></tr>";
    foreach ($studentDataFromAllServers as $student) {
        $study = $student['StudyFees'] ?? 0;
        $reg = $student['RegistrationFees'] ?? 0;
        echo "<tr>";
        echo "<td>" . e($student['StudentID']) . "</td>";
        echo "<td>" . e($student['StudentName']) . "</td>";
        echo "<td>" . number_format($study, 0, '.', '') . "</td>";
        echo "<td>" . number_format($reg, 0, '.', '') . "</td>";
        echo "<td>" . number_format(($study + $reg), 0, '.', '') . "</td>";
        echo "<td>" . e($student['SemesterID']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalRecords = count($studentDataFromAllServers);
$totalPages = ceil($totalRecords / $perPage);
$start = ($page - 1) * $perPage;
$studentDataFromAllServers = array_slice($studentDataFromAllServers, $start, $perPage);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بيانات الطلاب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body dir="rtl" class="p-4">
    <div class="container">
        <h3 class="mb-4">بيانات الطلاب من الدالة StudentAllForPayment</h3>

        <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
                <select name="faculty" class="form-select">
                    <option value="">كل الكليات</option>
                    <?php foreach ($facultyList as $faculty): ?>
                        <option value="<?= e($faculty) ?>" <?= $facultyFilter == $faculty ? 'selected' : '' ?>><?= e($faculty) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="batch" class="form-select">
                    <option value="">كل الدفعات</option>
                    <?php foreach ($batchList as $batch): ?>
                        <option value="<?= e($batch) ?>" <?= $batchFilter == $batch ? 'selected' : '' ?>><?= e($batch) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="keyword" class="form-control" placeholder="بحث بالاسم أو الرقم" value="<?= e($keyword) ?>">
            </div>
            <div class="col-md-1">
                <select name="limit" class="form-select">
                    <?php foreach ([10, 20, 50, 100] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($perPage == $opt ? 'selected' : '') ?>><?= $opt ?></option>
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

        <?php if (empty($studentDataFromAllServers)): ?>
            <div class="alert alert-warning">⚠️ لا توجد بيانات طلاب مسترجعة من أي سيرفر.</div>
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
                        <?php foreach ($studentDataFromAllServers as $student): ?>
                            <tr>
                                <td><?= e($student['StudentID'] ?? '') ?></td>
                                <td><?= e($student['StudentName'] ?? '') ?></td>
                                <td><?= number_format($student['StudyFees'] ?? 0, 0, '.', '') ?></td>
                                <td><?= number_format($student['RegistrationFees'] ?? 0, 0, '.', '') ?></td>
                                <td><?= number_format(($student['StudyFees'] ?? 0) + ($student['RegistrationFees'] ?? 0), 0, '.', '') ?></td>
                                <td><?= e($student['SemesterID'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="2">الإجمالي (عدد الطلاب: <?= $totalRecords ?>)</th>
                            <th><?= number_format($totalStudyFees, 0, '.', '') ?></th>
                            <th><?= number_format($totalRegistrationFees, 0, '.', '') ?></th>
                            <th><?= number_format($totalCombinedFees, 0, '.', '') ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <nav>
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>">&laquo; السابق</a>
                    </li>
                    <?php
                    $range = 2;
                    $startPage = max(1, $page - $range);
                    $endPage = min($totalPages, $page + $range);
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&faculty=<?= urlencode($facultyFilter) ?>&batch=<?= urlencode($batchFilter) ?>&keyword=<?= urlencode($keyword) ?>&limit=<?= $perPage ?>">التالي &raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</body>
</html>
