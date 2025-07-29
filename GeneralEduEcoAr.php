<?php
session_start();
include 'db_connection.php';

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
            body {
                font-family: "Almarai", sans-serif;
                background-color: #f9f9f9;
                color: #c00;
                text-align: center;
                direction: rtl;
                padding: 100px 20px;
            }
            .error-box {
                display: inline-block;
                background: #ffeaea;
                border: 2px solid #f99;
                padding: 20px 40px;
                border-radius: 10px;
                font-size: 20px;
            }
            a {
                color: blue;
                text-decoration: none;
            }
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
            transition: background-color 0.3s ease;
        }
        #printBtn:hover {
            background-color: #0056b3;
        }
        @media print {
            #printBtn { display: none !important; }
        }

        table img {
    width: 100px;
    height: 100px;
    object-fit: contain; /* يحافظ على نسبة الأبعاد بدون تمديد */
    image-rendering: crisp-edges;
    -webkit-image-rendering: crisp-edges;
    image-rendering: pixelated;
    border: none; /* إزالة أي إطار */
    display: block; /* لمنع أي مسافات تحت الصورة */
    margin: 0 auto; /* لو تريد الصور في الوسط داخل الخلايا */
    filter: brightness(1.1) contrast(1.1);
        
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

<?php if (!empty($Certificate['Photo'])): ?>
    <div style="width: 120px; height: 120px; margin-bottom: 10px;">
        <img class="student-photo"
            style="width: 100%; height: 100%; object-fit: contain; border: 0px solid #000; border-radius: 2px;"
            src="data:image/jpeg;base64,<?= base64_encode($Certificate['Photo']) ?>" 
            alt="صورة الطالب"
        />
    </div>
<?php endif; ?>

<h5><?= htmlspecialchars($Certificate['AdmissionFormNo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:الرقم الجامعي</h5>

<div align="center">
    <b style="font-family:'Droid Arabic Kufi'; font-size:24px;">شهـادة </b>
</div>

<div align="right">
    <b style="font-family: 'Amiri'; font-size:28px;">: نشهد بأن مجلس الأساتذة قد منح</b>
</div>

<div align="right">
    <b style="font-family:'Droid Arabic Kufi'; font-size:16px;">
        <?= htmlspecialchars($Certificate['StudentName'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        - الجنسية:
        <?= htmlspecialchars($Certificate['StudentNationality'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
</b>
    

</div>
<table  align="right" style="font-family:'Droid Arabic Kufi'; font-size:16px" dir="rtl" >
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"> </td><td><div align="center"><b style="font-family:'Droid Arabic Kufi'; font-size:16px;">درجة <?= htmlspecialchars($Certificate['DegreeNameAr'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></div></td></tr>

    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;">الكليـة: </td><td><?= htmlspecialchars($Certificate['FacultyName'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></td></tr>
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"><?= htmlspecialchars($Class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>:</td><td><u><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></b></td></tr>
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"> التخصص</td><td><u><?= htmlspecialchars($Certificate['SpecializationName'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></b></td></tr>
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;"> تاريخ  منح الدرجة:</td><td><u><?= $GradDate ?></u></b></td></tr>
    <tr><td><b style="font-family:'Droid Arabic Kufi'; font-size:16px;">تاريخ  اصدار الشهادة:</td><td><u><?= $DateNow ?> </u></b></td></tr>
</table>
<table width="100%">
    <tr align="center">
        <td colspan="2">
            <img width="100" height="100" src="img/<?= htmlspecialchars($Signatures['ImgDeann'] ?? 'not-found.png') ?>" alt="توقيع عميد الكلية">
        </td>
        <td>
            <img width="100" height="100" src="img/<?= htmlspecialchars($Signatures['Imgregg'] ?? 'not-found.png') ?>" alt="توقيع مسجل الكلية">
        </td>
    </tr>
    <tr align="center">
        <th colspan="2"><b style="font-family:'Amiri'; font-size:16px;"><?= htmlspecialchars($Signatures['FacultyDean_NameA'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></th>
        <th nowrap><b style="font-family:'Amiri'; font-size:16px;"><?= htmlspecialchars($Signatures['FacultyRegistrar_NameA'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></th>
    </tr>
    <tr align="center">
        <th colspan="2"><b style="font-family:'Droid Arabic Kufi'; font-size:16px;">عميد الكلية</b></th>
        <th><b style="font-family:'Droid Arabic Kufi'; font-size:16px;">مسجل الكلية</b></th>
    </tr>
     <tr align="center">
        <td colspan="3"><b><br><br><br> </b></td>   
    </tr>  
    <tr align="center">
        <th colspan="3"><b style="font-family:'Amiri'; font-size:16px;"><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameA'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></th>
    </tr>
    <tr align="center">
        <th colspan="3"><b style="font-family:'Droid Arabic Kufi'; font-size:16px;">أمين الشؤون العلمية</b></th>
    </tr>
</table>

</body>
</html>

<?php sqlsrv_close($conn); ?>
