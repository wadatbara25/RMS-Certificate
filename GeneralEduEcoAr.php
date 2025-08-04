 <?php
session_start();
include 'db_connection.php';

// معالجة رفع الصورة وتحسينها
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_photo"])) {
    $studentId = $_GET['id'] ?? null;
    if (!$studentId) {
        renderErrorPage("لا يمكن رفع الصورة بدون معرف الطالب.");
    }

    $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $studentId);
    $targetDir = __DIR__ . "/saved_images";
    $targetFile = $targetDir . "/$safeId.jpg";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageTmp = $_FILES["student_photo"]["tmp_name"];
    $fileType = mime_content_type($imageTmp);
    $allowedTypes = ['image/jpeg', 'image/png'];

    if (!in_array($fileType, $allowedTypes)) {
        renderErrorPage("⚠️ الصيغة غير مدعومة. يُرجى رفع صورة بصيغة JPG أو PNG.");
    }

    // تحميل الصورة الأصلية
    $srcImage = null;
    if ($fileType === 'image/jpeg') {
        $srcImage = @imagecreatefromjpeg($imageTmp);
    } elseif ($fileType === 'image/png') {
        $srcImage = @imagecreatefrompng($imageTmp);
    }

    if (!$srcImage) {
        renderErrorPage("⚠️ تعذر قراءة الصورة المرفوعة.");
    }

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

    imagecopyresampled($resizedImage, $srcImage, $xOffset, $yOffset, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    if (!imagejpeg($resizedImage, $targetFile, 85)) {
        renderErrorPage("⚠️ فشل حفظ الصورة بعد التحجيم والتحسين.");
    }

    imagedestroy($srcImage);
    imagedestroy($resizedImage);

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// تحقق من تسجيل الدخول
if (empty($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$selectedServer = $_SESSION["server"];
$id = $_GET["id"] ?? null;

function renderErrorPage($message) {
    echo '
    <!DOCTYPE html>
    <html lang="ar">
    <head>
        <meta charset="UTF-8">
        <title>خطأ</title>
        <style>
            body { font-family: "Almarai", sans-serif; background-color: #f9f9f9; color: #c00; text-align: center; direction: rtl; padding: 100px 20px; }
            .error-box { display: inline-block; background: #ffeaea; border: 2px solid #f99; padding: 20px 40px; border-radius: 10px; font-size: 20px; }
            a { color: blue; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ خطأ في البيانات</h1>
            <p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>
            <br>
            <p><a href="javascript:history.back();">🔙 العودة للخلف</a></p>
        </div>
    </body>
    </html>';
    exit();
}

if (!$id) {
    renderErrorPage("لم يتم تحديد معرف الطالب.");
}

$conn = connectToDatabase($selectedServer);
if (!$conn) {
    renderErrorPage("فشل الاتصال بقاعدة البيانات.");
}

$sql = "SELECT * FROM TranscriptF(?) ORDER BY SemesterID, SubjectNameEng";
$params = [$id];
$TRRR = sqlsrv_query($conn, $sql, $params);
if (!$TRRR) {
    renderErrorPage("فشل في جلب السجل الأكاديمي.");
}

$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);

$facultyId = $Certificate['FacultyID'] ?? null;
if (!$facultyId) {
    renderErrorPage("لم يتم تحديد الكلية المرتبطة بالطالب.");
}

$Signatures = getAllSignatures($selectedServer, $facultyId);
if (!$Signatures) renderErrorPage("لم يتم العثور على بيانات التوقيعات.");
if (!$Certificate) renderErrorPage("لم يتم العثور على بيانات الشهادة للطالب.");
if (!$row) renderErrorPage("لم يتم العثور على بيانات الطالب.");

$GradDate = $Certificate['GraduationDate']->format('Y/m/d');
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
$isHonorDegree = str_contains($Certificate['DegreeNameAr'], $General);
$message = $isHonorDegree ? divition($Certificate['CGPA']) : divitionG($Certificate['CGPA']);
$Class = $isHonorDegree ? 'المرتبة' : 'الدرجة';
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>شهادة عامة عربي</title>
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

<?php
$studentId = $Certificate['StudentID'] ?? $id;
$safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $studentId);
$imagePath = "saved_images/$safeId.jpg";
?>

<?php if (file_exists($imagePath)): ?>
    <div style="width: 120px; height: 120px; margin-bottom: 10px;">
        <img  src="<?= htmlspecialchars($imagePath) ?>" class="student-photo"
            style="width: 100%; height: 100%; object-fit: contain; border: 0px solid #000; border-radius: 2px;"  alt="صورة الطالب" />
    </div>
<?php else: ?>
    <div style="width: 120px; margin-bottom: 10px; text-align: center;">
        <span style="color: gray; font-size: 14px;">📷 لا توجد صورة</span>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="student_photo" accept="image/*" required>
            <input type="submit" name="upload_photo" value="رفع صورة">
        </form>
    </div>
<?php endif; ?>

<h5><?= htmlspecialchars($Certificate['AdmissionFormNo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:الرقم الجامعي</h5>

<div align="center"><b style="font-family:'Droid Arabic Kufi'; font-size:24px;">شهـادة</b></div>
<div align="right"><b style="font-family: 'Amiri'; font-size:28px;">: نشهد بأن مجلس الأساتذة قد منح</b></div>
<div align="right">
    <b style="font-family:'Droid Arabic Kufi'; font-size:16px;">
        <?= htmlspecialchars($Certificate['StudentName'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        - الجنسية: <?= htmlspecialchars($Certificate['StudentNationality'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </b>
</div>

<table align="right" style="font-family:'Droid Arabic Kufi'; font-size:16px" dir="rtl">
    <tr><td></td><td><div align="center"><b>درجة <?= htmlspecialchars($Certificate['DegreeNameAr']) ?></b></div></td></tr>
    <tr><td>الكليـة:</td><td><?= htmlspecialchars($Certificate['FacultyName']) ?></td></tr>
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"><?= htmlspecialchars($Class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>:</td><td><u><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></b></td></tr>
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"> التخصص</td><td><u><?= htmlspecialchars($Certificate['SpecializationName'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></b></td></tr>    <tr><td>تاريخ منح الدرجة:</td><td><u><?= $GradDate ?></u></td></tr>
    <tr><td>تاريخ إصدار الشهادة:</td><td><u><?= $DateNow ?></u></td></tr>
</table>

<table width="100%">
    <tr align="center">
        <td colspan="2"><img src="img/<?= htmlspecialchars($Signatures['ImgDeann'] ?? 'not-found.png') ?>"></td>
        <td><img src="img/<?= htmlspecialchars($Signatures['Imgregg'] ?? 'not-found.png') ?>"></td>
    </tr>
    <tr align="center">
        <th colspan="2"><?= htmlspecialchars($Signatures['FacultyDean_NameA'] ?? '') ?></th>
        <th><?= htmlspecialchars($Signatures['FacultyRegistrar_NameA'] ?? '') ?></th>
    </tr>
    <tr align="center"><th colspan="2">عميد الكلية</th><th>مسجل الكلية</th></tr>
    <tr align="center"><td colspan="3"><br><br></td></tr>
    <tr align="center">
        <th colspan="3"><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameA'] ?? '') ?></th>
    </tr>
    <tr align="center"><th colspan="3">أمين الشؤون العلمية</th></tr>
</table>

</body>
</html>

<?php sqlsrv_close($conn); ?>


