<?php
// --------------------------------------------------
// إعداد عام (وضع تطوير/إنتاج)
define('DEV_MODE', false); // غيّره إلى false في الاستضافة الفعلية بعد الحل

// تفعيل عرض الأخطاء في DEV_MODE فقط
if (DEV_MODE) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

// بدء الجلسة بأمان (تأكد أنه لا يوجد إخراج قبل هذا)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connection.php';

// --------------------------------------------------
// دوال مساعدة

function logDebug($msg) {
    $logFile = __DIR__ . '/debug.log';
    // لا تكتب إذا لم يُمكن الكتابة
    @file_put_contents($logFile, "[" . date('c') . "] " . $msg . "\n", FILE_APPEND);
}

function renderErrorPage($message) {
    $details = '';
    if (DEV_MODE) {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $where = $bt[1]['file'] ?? '';
        $line = $bt[1]['line'] ?? '';
        $details = "<div style='margin-top:10px; font-size:12px; color:#555;'>[من: " . htmlspecialchars($where) . " سطر: " . htmlspecialchars($line) . "]</div>";
    }
    // رأس HTML مصحح (accessibility + viewport)
    echo '<!DOCTYPE html>
    <html lang="ar">
    <head>
        <meta charset="UTF-8">
        <title>خطأ</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: "Almarai", sans-serif; background-color: #f9f9f9; color: #c00; text-align: center; direction: rtl; padding: 100px 20px; }
            .error-box { display: inline-block; background: #ffeaea; border: 2px solid #f99; padding: 20px 40px; border-radius: 10px; font-size: 20px; max-width: 600px; }
            a { color: blue; text-decoration: none; }
            pre { background:#f0f0f0; padding:10px; overflow:auto; text-align:left; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ خطأ في البيانات</h1>
            <p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . $details .
            '<br><p><a href="javascript:history.back();">🔙 العودة للخلف</a></p>
        </div>
    </body>
    </html>';
    exit();
}

function safeRedirect($url) {
    // تنظيف واستيعاب إعادة التوجيه داخل نفس الموقع فقط
    $clean = filter_var($url, FILTER_SANITIZE_URL);
    // منع هجمات حقن هيدر: قبول مسارات محلية فقط
    if (!preg_match('#^([./a-zA-Z0-9_\-?=&]+)$#', $clean)) {
        $clean = 'login.php';
    }
    header("Location: " . $clean);
    exit();
}

function getMimeType($file) {
    if (function_exists('mime_content_type')) {
        $type = @mime_content_type($file);
        if ($type !== false) {
            return $type;
        }
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $type = finfo_file($finfo, $file);
            finfo_close($finfo);
            return $type;
        }
    }
    return '';
}

// التحقق من امتدادات أساسية
if (!extension_loaded('sqlsrv')) {
    logDebug("امتداد sqlsrv غير مفعل.");
    renderErrorPage("الامتداد المطلوب للاتصال بقاعدة البيانات (sqlsrv) غير مفعل في الاستضافة.");
}
if (!extension_loaded('gd') && !extension_loaded('gd2')) {
    logDebug("امتداد GD غير مفعل.");
    renderErrorPage("امتداد معالجة الصور (GD) غير مفعل.");
}
if (!function_exists('finfo_open') && !function_exists('mime_content_type')) {
    logDebug("لا يوجد دعم لفحص النوع MIME.");
    renderErrorPage("لا يمكن تحديد نوع الملف المرفوع بسبب نقص امتدادات نظام الملفات.");
}

// --------------------------------------------------
// تحقق من تسجيل الدخول
if (empty($_SESSION["username"])) {
    safeRedirect("login.php");
}

$selectedServer = $_SESSION["server"] ?? null;
$id = isset($_GET["id"]) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_GET["id"]) : null;
if (!$id) {
    renderErrorPage("لم يتم تحديد معرف الطالب.");
}

// اتصال بقاعدة البيانات
$conn = connectToDatabase($selectedServer);
if (!$conn) {
    $errors = sqlsrv_errors();
    $detail = $errors ? print_r($errors, true) : 'لا توجد تفاصيل إضافية.';
    logDebug("فشل الاتصال بقاعدة البيانات: " . $detail);
    renderErrorPage("فشل الاتصال بقاعدة البيانات. التفاصيل: " . $detail);
}

// --------------------------------------------------
// معالجة رفع الصورة وتحسينها
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_photo"])) {
    $studentId = $id;
    if (!$studentId) {
        renderErrorPage("لا يمكن رفع الصورة بدون معرف الطالب.");
    }

    $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $studentId);
    $targetDir = __DIR__ . "/saved_images";
    $targetFile = $targetDir . "/$safeId.jpg";

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        logDebug("فشل إنشاء مجلد الصور: $targetDir");
        renderErrorPage("فشل إنشاء مجلد حفظ الصور.");
    }

    if (!isset($_FILES["student_photo"])) {
        renderErrorPage("لم يتم إرسال ملف الصورة.");
    }

    $file = $_FILES["student_photo"];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        logDebug("خطأ في رفع الملف: code=" . $file['error']);
        renderErrorPage("حدث خطأ أثناء رفع الملف. رمز الخطأ: " . $file['error']);
    }

    $imageTmp = $file["tmp_name"];
    if (!is_uploaded_file($imageTmp)) {
        renderErrorPage("الملف المرفوع غير صالح.");
    }

    $fileType = getMimeType($imageTmp);
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($fileType, $allowedTypes, true)) {
        logDebug("نوع ملف غير مدعوم: $fileType");
        renderErrorPage("⚠️ الصيغة غير مدعومة. يُرجى رفع صورة بصيغة JPG أو PNG.");
    }

    // إنشاء الصورة من المصدر
    $srcImage = null;
    if ($fileType === 'image/jpeg') {
        $srcImage = @imagecreatefromjpeg($imageTmp);
    } elseif ($fileType === 'image/png') {
        $srcImage = @imagecreatefrompng($imageTmp);
    }

    if (!$srcImage) {
        logDebug("فشل قراءة الصورة من المصدر، type=$fileType");
        renderErrorPage("⚠️ تعذر قراءة الصورة المرفوعة.");
    }

    // تحجيم داخل مربع ثابت
    $srcWidth = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);
    $targetSize = 300;
    $scale = min($targetSize / $srcWidth, $targetSize / $srcHeight);
    $newWidth = (int)($srcWidth * $scale);
    $newHeight = (int)($srcHeight * $scale);
    $xOffset = (int)(($targetSize - $newWidth) / 2);
    $yOffset = (int)(($targetSize - $newHeight) / 2);

    $resizedImage = imagecreatetruecolor($targetSize, $targetSize);
    $white = imagecolorallocate($resizedImage, 255, 255, 255);
    imagefill($resizedImage, 0, 0, $white);
    imagecopyresampled(
        $resizedImage,
        $srcImage,
        $xOffset,
        $yOffset,
        0,
        0,
        $newWidth,
        $newHeight,
        $srcWidth,
        $srcHeight
    );

    // حفظ JPG بجودة 85
    if (!imagejpeg($resizedImage, $targetFile, 85)) {
        logDebug("فشل حفظ الصورة المحسنة إلى: $targetFile");
        imagedestroy($srcImage);
        imagedestroy($resizedImage);
        renderErrorPage("⚠️ فشل حفظ الصورة بعد التحجيم والتحسين.");
    }

    imagedestroy($srcImage);
    imagedestroy($resizedImage);

    // إعادة تحميل بدون POST
    safeRedirect($_SERVER['REQUEST_URI']);
}

// --------------------------------------------------
// جلب السجل الأكاديمي
$sql = "SELECT * FROM TranscriptF(?) ORDER BY SemesterID, SubjectNameEng";
$params = [$id];
$TRRR = sqlsrv_query($conn, $sql, $params);
if (!$TRRR) {
    $err = sqlsrv_errors();
    logDebug("فشل استعلام TranscriptF: " . print_r($err, true));
    renderErrorPage("فشل في جلب السجل الأكاديمي.");
}

// جلب الشهادة والطالب والتوقيعات
$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);
$facultyId = $Certificate['FacultyID'] ?? null;

if (!$facultyId) {
    renderErrorPage("لم يتم تحديد الكلية المرتبطة بالطالب.");
}

$Signatures = getAllSignatures($selectedServer, $facultyId);
if (!$Signatures) {
    renderErrorPage("لم يتم العثور على بيانات التوقيعات.");
}
if (!$Certificate) {
    renderErrorPage("لم يتم العثور على بيانات الشهادة للطالب.");
}
if (!$row) {
    renderErrorPage("لم يتم العثور على بيانات الطالب.");
}

$GradDate = $Certificate['GraduationDate'] instanceof DateTimeInterface
    ? $Certificate['GraduationDate']->format('Y/m/d')
    : htmlspecialchars($Certificate['GraduationDate'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$DateNow = date("Y/m/d");

function divition($gpa) {
    return match (true) {
        $gpa >= 3.50 => 'الأولى',
        $gpa >= 3.00 => 'الثانية - القسم الأول',
        $gpa >= 2.50 => 'الثانية - القسم الثاني',
        default => 'الثالثة'
    };
}

function divitionG($gpa) {
    return match (true) {
        $gpa >= 3.50 => 'الأولى',
        $gpa >= 2.50 => 'الثانية',
        default => 'الثالثة'
    };
}

$General = 'شرف';
$isHonorDegree = isset($Certificate['DegreeNameAr']) && str_contains($Certificate['DegreeNameAr'], $General);
$message = $isHonorDegree ? divition($Certificate['CGPA']) : divitionG($Certificate['CGPA']);
$Class = $isHonorDegree ? 'المرتبة' : 'الدرجة';

$studentId = $Certificate['StudentID'] ?? $id;
$safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $studentId);
$imagePath = "saved_images/$safeId.jpg";
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>شهادة عامة عربي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="include/css/style.css">
    <style>
        #printBtn {
            display: block;
            margin: 20px auto;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Almarai', sans-serif;
        }
        #printBtn:hover { background-color: #0056b3; }
        @media print { #printBtn { display: none !important; } }

        table img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            image-rendering: crisp-edges;
            display: block;
            margin: 0 auto;
            filter: brightness(1.1) contrast(1.1);
        }

        form input[type="file"],
        form input[type="submit"] {
            margin-top: 5px;
            font-size: 14px;
        }
    </style>
    <script>
        function printCertificate() {
            const btn = document.getElementById('printBtn');
            btn.style.display = 'none';
            window.print();
            btn.style.display = 'block';
        }
    </script>
</head>
<body>

<button id="printBtn" onclick="printCertificate()">🖨️ طباعة الشهادة</button>

<?php if (file_exists($imagePath)): ?>
    <div style="width: 120px; height: 120px; margin-bottom: 10px;">
        <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="student-photo"
             style="width: 100%; height: 100%; object-fit: contain; border: 0px solid #000; border-radius: 2px;"
             alt="صورة الطالب" />
    </div>
<?php else: ?>
    <div style="width: 120px; margin-bottom: 10px; text-align: center;">
        <span style="color: gray; font-size: 14px;">📷 لا توجد صورة</span>
        <form action="" method="post" enctype="multipart/form-data" aria-label="رفع صورة الطالب">
            <input type="file" name="student_photo" accept="image/jpeg,image/png" required>
            <input type="submit" name="upload_photo" value="رفع صورة">
        </form>
    </div>
<?php endif; ?>

<h5><?= htmlspecialchars($Certificate['AdmissionFormNo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:الرقم الجامعي</h5>

<div align="center"><b style="font-family:'Droid Arabic Kufi'; font-size:24px;">شهـادة</b></div>
<div align="right"><b style="font-family: 'Amiri'; font-size:28px;">: نشهد بأن مجلس الأساتذة قد منح</b></div>
<div align="right">
    <b style="font-family:'Droid Arabic Kufi'; font-size:16px;">
        <?= htmlspecialchars($Certificate['StudentName'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        - الجنسية: <?= htmlspecialchars($Certificate['StudentNationality'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </b>
</div>

<table align="right" style="font-family:'Droid Arabic Kufi'; font-size:16px" dir="rtl">
    <tr>
        <td></td>
        <td><div align="center"><b>درجة <?= htmlspecialchars($Certificate['DegreeNameAr'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></div></td>
    </tr>
    <tr><td>الكليـة:</td><td><?= htmlspecialchars($Certificate['FacultyName'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td></tr>
    <tr>
        <td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"><?= htmlspecialchars($Class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:</b></td>
        <td><b><u><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></b></td>
    </tr>
    <tr>
        <td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"> التخصص</b></td>
        <td><b><u><?= htmlspecialchars($Certificate['SpecializationName'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></b></td>
    </tr>
    <tr><td>تاريخ منح الدرجة:</td><td><u><?= $GradDate ?></u></td></tr>
    <tr><td>تاريخ إصدار الشهادة:</td><td><u><?= $DateNow ?></u></td></tr>
</table>

<table width="100%">
    <tr align="center">
        <td colspan="2">
            <img src="img/<?= htmlspecialchars($Signatures['ImgDeann'] ?? 'not-found.png', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="توقيع عميد الكلية">
        </td>
        <td>
            <img src="img/<?= htmlspecialchars($Signatures['Imgregg'] ?? 'not-found.png', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="توقيع مسجل الكلية">
        </td>
    </tr>
    <tr align="center">
        <th colspan="2"><?= htmlspecialchars($Signatures['FacultyDean_NameA'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
        <th><?= htmlspecialchars($Signatures['FacultyRegistrar_NameA'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
    </tr>
    <tr align="center"><th colspan="2">عميد الكلية</th><th>مسجل الكلية</th></tr>
    <tr align="center"><td colspan="3"><br><br></td></tr>
    <tr align="center">
        <th colspan="3"><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameA'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
    </tr>
    <tr align="center"><th colspan="3">أمين الشؤون العلمية</th></tr>
</table>

</body>
</html>

<?php
sqlsrv_close($conn);
?>
