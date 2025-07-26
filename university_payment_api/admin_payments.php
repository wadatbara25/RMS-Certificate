<?php
session_start(); // ابدأ الجلسة مرة واحدة في بداية الملف

// يمكنك تفعيل تقارير الأخطاء للمساعدة في التطوير
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../db_connection.php'; // تضمين ملف الاتصال بقاعدة البيانات

// دالة مساعدة لتأمين النصوص
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// تحقق من تسجيل الدخول
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit();
}

$selectedServer = $_SESSION['server'] ?? '1';
$username = $_SESSION['username'] ?? '';

// جلب بيانات المستخدم (لأغراض عرض الكلية مثلاً)
// تعتمد هذه الدوال على وجودها في db_connection.php
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

// تهيئة متغيرات الفلاتر
$searchQuery = $_POST["search_query"] ?? "";
$paymentStatusFilter = $_POST["payment_status"] ?? "";
$startDateFilter = $_POST["start_date"] ?? "";
$endDateFilter = $_POST["end_date"] ?? "";

// =============================================================
//  منطق جلب المدفوعات والفلاتر مباشرة داخل admin_payments.php
//  لأن الدوال المخصصة غير موجودة في db_connection.php المعطى
// =============================================================

$payments = []; // تهيئة مصفوفة المدفوعات
$errorMessage = '';

// إنشاء اتصال جديد لقاعدة البيانات للدفعات
// سنستخدم دالة connectToDatabase الموجودة في db_connection.php
$conn = connectToDatabase($selectedServer);

if ($conn) {
    $sql = "SELECT ID, RRN, TransactionID, Amount, StatusCode, StatusDescription, ReceivedAt
            FROM ReceivedPaymentsFromBank
            WHERE 1=1"; // شرط صحيح دائما للسماح بإضافة شروط لاحقة

    $params = [];

    // تطبيق فلتر البحث العام
    if (!empty($searchQuery)) {
        $search = '%' . $searchQuery . '%';
        $sql .= " AND (CAST(ID AS NVARCHAR(MAX)) LIKE ? OR RRN LIKE ? OR TransactionID LIKE ? OR StatusDescription LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    // تطبيق فلتر حالة الدفع
    if (!empty($paymentStatusFilter)) {
        $sql .= " AND StatusCode = ?";
        $params[] = $paymentStatusFilter;
    }

    // تطبيق فلتر تاريخ البدء
    if (!empty($startDateFilter)) {
        // تأكد من أن التاريخ المدخل بصيغة YYYY-MM-DD
        $sql .= " AND ReceivedAt >= ?";
        $params[] = $startDateFilter;
    }

    // تطبيق فلتر تاريخ الانتهاء
    if (!empty($endDateFilter)) {
        // تأكد من أن التاريخ المدخل بصيغة YYYY-MM-DD
        // أضف ' 23:59:59.999' لجلب المدفوعات حتى نهاية اليوم المحدد
        $sql .= " AND ReceivedAt <= ?";
        $params[] = $endDateFilter . ' 23:59:59.999';
    }

    $sql .= " ORDER BY ReceivedAt DESC"; // الترتيب الافتراضي حسب تاريخ الاستلام

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $errorMessage = "خطأ في جلب بيانات المدفوعات: " . print_r(sqlsrv_errors(), true);
        error_log($errorMessage); // تسجيل الخطأ لمراجعته
    } else {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $payments[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn); // إغلاق الاتصال بعد الانتهاء من الاستعلام
} else {
    $errorMessage = "فشل الاتصال بقاعدة البيانات لجلب المدفوعات.";
}


// جلب حالات الدفع الفريدة مباشرة (لأن getUniquePaymentStatusCodes() غير موجودة في db_connection.php)
$uniquePaymentStatuses = [];
$conn_status = connectToDatabase($selectedServer); // اتصال جديد لجلب الحالات
if ($conn_status) {
    $sql_status = "SELECT DISTINCT StatusCode FROM ReceivedPaymentsFromBank WHERE StatusCode IS NOT NULL AND StatusCode != '' ORDER BY StatusCode";
    $stmt_status = sqlsrv_query($conn_status, $sql_status);
    if ($stmt_status === false) {
        error_log("Error fetching unique payment statuses: " . print_r(sqlsrv_errors(), true));
    } else {
        while ($row_status = sqlsrv_fetch_array($stmt_status, SQLSRV_FETCH_ASSOC)) {
            $uniquePaymentStatuses[] = $row_status['StatusCode'];
        }
        sqlsrv_free_stmt($stmt_status);
    }
    sqlsrv_close($conn_status); // إغلاق الاتصال
}


// رسالة الخطأ النهائية إذا لم يكن هناك نتائج
if (empty($payments) && ($_SERVER["REQUEST_METHOD"] === "POST" || !empty($searchQuery) || !empty($paymentStatusFilter) || !empty($startDateFilter) || !empty($endDateFilter))) {
    // هذه الرسالة تظهر إذا تم تطبيق فلاتر ولم يتم العثور على نتائج
    $errorMessage = "لا توجد نتائج لعرضها بالمعايير المحددة.";
} elseif (empty($payments) && empty($errorMessage)) {
    // هذه الرسالة تظهر إذا لم يتم تطبيق فلاتر والجدول فارغ تمامًا ولم يحدث خطأ في الاتصال
    $errorMessage = "لا توجد مدفوعات لعرضها في النظام.";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>قائمة المدفوعات البنكية</title>
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
        h1, h3 {
            color: #34495e;
        }
        .table-responsive {
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        /* ستايل النافذة المنبثقة */
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
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .filter-form .row > div {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include '../sidebar.php'; // تأكد أن sidebar.php موجود في نفس المجلد أو قم بتعديل المسار ?>
<main class="main-content" role="main">
    <h3>المدفوعات البنكية المستلمة</h3>

    <div class="filter-form">
        <form method="POST" action="admin_payments.php">
            <div class="row">
                <div class="col-md-4">
                    <label for="search_query" class="form-label">بحث (المعرف، رقم RRN، رقم العملية، وصف الحالة):</label>
                    <input type="text" class="form-control" id="search_query" name="search_query" value="<?= e($searchQuery) ?>" placeholder="أدخل كلمة البحث" />
                </div>
                <div class="col-md-4">
                    <label for="payment_status" class="form-label">حالة الدفعة:</label>
                    <select class="form-select" id="payment_status" name="payment_status">
                        <option value="">كل الحالات</option>
                        <?php foreach ($uniquePaymentStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($paymentStatusFilter === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="start_date" class="form-label">من تاريخ الاستلام:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDateFilter) ?>" />
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">إلى تاريخ الاستلام:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDateFilter) ?>" />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">بحث وتصفية</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table id="paymentsTable" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>RRN</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Status Code</th>
                    <th>Status Description</th>
                    <th>Received At</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e($payment["ID"] ?? '') ?></td>
                            <td><?= e($payment["RRN"] ?? '') ?></td>
                            <td><?= e($payment["TransactionID"] ?? '') ?></td>
                            <td><?= e($payment["Amount"] ?? '') ?></td>
                            <td><?= e($payment["StatusCode"] ?? '') ?></td>
                            <td><?= e($payment["StatusDescription"] ?? '') ?></td>
                            <td><?= isset($payment["ReceivedAt"]) ? e($payment["ReceivedAt"]->format('Y-m-d H:i:s')) : '' ?></td>
                            <td>
                                <a href="payment_details_bank.php?id=<?= urlencode($payment["ID"] ?? '') ?>" class="btn btn-sm btn-info">تفاصيل</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">لا توجد بيانات متاحة.</td>
                    </tr>
                <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#paymentsTable').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
        },
        searching: false, // تعطيل مربع البحث الافتراضي في DataTables
        ordering: true,   // تم تفعيل الترتيب للسماح بالفرز على الأعمدة المعروضة
    });
});
</script>
</body>
</html>