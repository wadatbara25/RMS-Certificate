<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$selectedServer = $_SESSION["server"];
$id = isset($_GET["id"]) ? $_GET["id"] : null;

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
            <p>' . htmlspecialchars($message) . '</p>
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
if ($conn === false) {
    renderErrorPage("فشل الاتصال بقاعدة البيانات.");
}

$sql = "SELECT * FROM TranscriptF('$id') ORDER BY SemesterID, SubjectNameEng";
$TRRR = sqlsrv_query($conn, $sql);

if ($TRRR === false) {
    renderErrorPage("فشل في جلب السجل الأكاديمي.");
}

$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);
$Signatures = getAllSignatures($selectedServer, $id);

if ($Certificate === null) renderErrorPage("لم يتم العثور على بيانات الشهادة للطالب.");
if ($row === null) renderErrorPage("لم يتم العثور على بيانات الطالب.");
if ($Signatures === null) renderErrorPage("لم يتم العثور على بيانات التوقيعات.");

$GradDate = $Certificate['GraduationDate']->format('Y/m/d');
$DateNow = date("Y/m/d");

function divition($gpa){
    if ($gpa >= 3.50) return 'الأولى';
    if ($gpa >= 3.00) return 'الثانية - القسم الأول';
    if ($gpa >= 2.50) return 'الثانية - القسم الثاني';
    return 'الثالثة';
}

function divitionG($gpa){
    if ($gpa >= 3.50) return 'الأولى';
    if ($gpa >= 2.50) return 'الثانية';
    return 'الثالثة';
}

$General = 'شرف';
$isHonorDegree = str_contains($Certificate['DegreeNameAr'], $General);

if ($isHonorDegree) {
    $message = divition($Certificate['CGPA']);
    $Class = 'المرتبة';
} else {
    $message = divitionG($Certificate['CGPA']);
    $Class = 'الدرجة';
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
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
</style>
<script>
function printCertificate() {
    document.getElementById('printBtn').style.display = 'none';
    window.print();
    document.getElementById('printBtn').style.display = 'block';
}
</script>
</head>
<body>

<button id="printBtn" onclick="printCertificate()">🖨️ طباعة الشهادة</button>

<div style="width: 120px; height: 120px; margin-bottom: 10px;">
  <img 
    src="data:image/jpeg;base64,<?php echo base64_encode($Certificate['Photo']) ?>" 
    alt="صورة الطالب"
    style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 2px solid #ccc; display: block;"
  />
</div>

<h5><?php echo htmlspecialchars($Certificate['AdmissionFormNo']); ?>:الرقم الجامعي</h5>

<div align="center">
    <b style="font-family:'Droid Arabic Kufi'; font-size:24px;">شهـادة </b>
</div>

<div align="right">
    <b style="font-family: 'arabtype'; font-size:30px;">: نشهد بأن مجلس الأساتذة قد منح</b>
</div>

<div align="right">
    <h2>
        <u><?php echo htmlspecialchars($Certificate['StudentName']); ?></u>
        - الجنسية:
        <u><?php echo htmlspecialchars($Certificate['StudentNationality']); ?></u>
    </h2>
</div>

<div align="right" style="font-family: 'arabtype'; font-size:28px;">
    <div align="center">
        <b>درجة <?php echo htmlspecialchars($Certificate['DegreeNameAr']); ?></b>
    </div>

    <div><b>الكلية: <?php echo htmlspecialchars($Certificate['FacultyName']); ?></b></div>

    <div><b>&nbsp;<u><?php echo $Class.':'.$message; ?></u></b></div>

    <div><b>التخصص :&nbsp;<u><?php echo htmlspecialchars($Certificate['DepartmentName']); ?> </u></b></div>

    <div><b>&nbsp;<u><?php echo $GradDate; ?> :تاريخ  منح الدرجة</u></b></div>

    <div><b>&nbsp;<u><?php echo $DateNow; ?> :تاريخ  اصدار الشهادة</u></b></div>
</div>

<table width="100%">
    <tr align="center">
        <td colspan="2">
            <img width="100" height="100" src="img/<?php echo htmlspecialchars($Signatures['ImgDeann']); ?>">
        </td>
        <td>
            <img width="100" height="100" src="img/<?php echo htmlspecialchars($Signatures['Imgregg']); ?>">
        </td>
    </tr>
    <tr align="center">
        <th colspan="2"><h2><i><?php echo htmlspecialchars($Signatures['FacultyDean_NameA']); ?></i></h2></th>
        <th nowrap><h2><i><?php echo htmlspecialchars($Signatures['FacultyRegistrar_NameA']); ?></i></h2></th>
    </tr>
    <tr align="center">
        <th colspan="2"><b>عميد الكلية</b></th>
        <th><b>مسجل الكلية</b></th>
    </tr>
    <tr align="center">
        <th colspan="3"><h2><i><?php echo htmlspecialchars($Signatures['AcademicAffairsDean_NameA']); ?></i></h2></th>
    </tr>
    <tr align="center">
        <th colspan="3"><b>أمين الشؤون العلمية</b></th>
    </tr>
</table>

</body>
</html>

<?php
sqlsrv_close($conn);
?>
