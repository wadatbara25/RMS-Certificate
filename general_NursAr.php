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

$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);
$Signatures = getAllSignatures($selectedServer, $id);

if ($Certificate === null) renderErrorPage("لم يتم العثور على بيانات الشهادة للطالب.");
if ($row === null) renderErrorPage("لم يتم العثور على بيانات الطالب.");
if ($Signatures === null) renderErrorPage("لم يتم العثور على بيانات التوقيعات.");

$GradDate = $Certificate['GraduationDate']->format('Y/m/d');
$DateNow = date("Y/m/d");

function divition($gpa){
    switch (true){
        case ($gpa >= 3.50): return 'الأولى';
        case ($gpa >= 3.00): return 'الثانية - القسم الأول';
        case ($gpa >= 2.50): return 'الثانية - القسم الثاني';
        default: return 'الثالثة';
    }
}

function divitionG($gpa){
    switch (true){
        case ($gpa >= 3.50): return 'الأولى';
        case ($gpa >= 2.50): return 'الثانية';
        default: return 'الثالثة';
    }
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Almarai|Sofia|Trirong">

<style>
   table.T1{
       border: 0px solid black;
        padding: 0px;
        background-color: #ffffff;
        text-align: center;       
    }
  table.T2 {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0px;
        text-align:center;
        font-size:12px;
    }
    table.T2, th.T2, td.T2 {
        border: 0px solid black;
        padding: 0px;
        text-align: center;
    }

    .total-row {
        font-weight: bold;
        background-color: #e0e0e0;
    }

    hr.new1 {
        border-top: 1px dashed red;
    }

    #printBtn {
        display: block;
        margin: 15px auto 30px auto;
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
      #printBtn {
        display: none !important;
      }
      body {
        margin: 0;
        background-color: white;
      }
    }
</style>

<script>
function printCertificate() {
    const btn = document.getElementById('printBtn');
    btn.style.display = 'none';  // إخفاء الزر فور الضغط
    window.print();
    btn.style.display = 'block'; // إعادة ظهوره بعد الطباعة
}
</script>
</head>
<body>

<button id="printBtn" onclick="printCertificate()">طباعة الشهادة 🖨️</button>

<table class="T2" border="0" padding="0" border-spacing="0" align="center" width="90%">
  <tr align="left">
    <!--<td><img width="120" height="120" src="data:image/jpeg;base64,<?php echo base64_encode($Certificate['Photo']) ?>" /> </td>
-->
    <td style="width: 120px; height: 120px; padding: 0;">
  <img 
    src="data:image/jpeg;base64,<?= base64_encode($Certificate['Photo']) ?>" 
    alt="صورة الطالب" 
    style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; display: block; margin: 0 auto;"
  />
</td>

    <th></th>
    <th></th>
  </tr>
  <tr align="left">
    <td><h5><?php echo htmlspecialchars($Certificate['AdmissionFormNo']); ?>:الرقم الجامعي</h5></td>
    <td colspan="2"></td>
  </tr>
  <tr align="center">
    <td colspan="3"><b style="font-family: 'Almarai', sans-serif; font-size:40px;">شهـادة</b></td>
  </tr>
  <tr align="center"><td colspan="3"><br></td></tr>
  <tr align="right">
    <td colspan="3"><b style="font-family: 'Almarai', sans-serif; font-size:20px;">: نشهد بأن مجلس الأساتذة قد منح</b></td>
  </tr>
  <tr align="right">
    <td><h2>الجنسية: <u><?php echo htmlspecialchars($Certificate['StudentNationality']);?></u> </h2></td>
    <td colspan="2"><h2><u> <?php echo htmlspecialchars($Certificate['StudentName']);?> </u></h2></td>
  </tr>
  <tr align="center">
    <td colspan="3"><h2><?php echo htmlspecialchars($Certificate['DegreeNameAr']);?></h2></td>
  </tr>
  <tr align="right">
    <td colspan="3"><h2>&nbsp;<u><?php echo $Class.':'.$message;?></u></h2></td>
  </tr>
  <tr align="right">
    <td colspan="3"><h2>الكلية:<?php echo htmlspecialchars($Certificate['FacultyName']);?></h2></td>
  </tr>
  <tr align="right">
    <th colspan="3"><h2>التخصص :&nbsp;<u><?php echo htmlspecialchars($Certificate['DepartmentName']);?> </u></h2></th>
  </tr>
  <tr align="right">
    <th colspan="3"><h2>&nbsp;<u><?php echo $GradDate;?> :تاريخ  منح الدرجة</u></h2></th>
  </tr>
  <tr align="right">
    <th colspan="3"><h2>&nbsp;<u><?php echo $DateNow;?> :تاريخ  اصدار الشهادة</u></h2></th>
  </tr>
  <tr align="center">
    <td colspan="2"><img width="100" height="100" src="img/<?php echo htmlspecialchars($Signatures['ImgDeann']);?>"></td>
    <td><img width="100" height="100" src="img/<?php echo htmlspecialchars($Signatures['Imgregg']);?>"></td>
  </tr>
  <tr align="center">
    <th colspan="2"><h2><i><?php echo htmlspecialchars($Signatures['FacultyDean_NameA']);?></i></h2></th>
    <th nowrap><h2><i><?php echo htmlspecialchars($Signatures['FacultyRegistrar_NameA']);?></i></h2></th>
  </tr>
  <tr align="center">
    <th colspan="2"><h2>عميد الكلية</h2></th>
    <th><h2>مسجل الكلية</h2></th>
  </tr>
  <tr align="center">
    <th colspan="3"><h2><i><?php echo htmlspecialchars($Signatures['AcademicAffairsDean_NameA']);?></i></h2></th>
  </tr>
  <tr align="center">
    <th colspan="3"><h2>أمين الشؤون العلمية</h2></th>
  </tr>
</table>

</body>
</html>

<?php sqlsrv_close($conn); ?>
