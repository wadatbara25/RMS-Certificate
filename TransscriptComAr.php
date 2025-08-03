<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$selectedServer = $_SESSION["server"];
$id = $_GET["id"] ?? null;

if (!$id) {
    die("Invalid ID");
}

$conn = connectToDatabase($selectedServer);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

$sql = "SELECT * FROM TranscriptF(?) ORDER BY SemesterID, SubjectCode";
$params = [$id];
$TRRR = sqlsrv_query($conn, $sql, $params);
if (!$TRRR) {
    die(print_r(sqlsrv_errors(), true));
}

$data = [];
while ($row = sqlsrv_fetch_array($TRRR, SQLSRV_FETCH_ASSOC)) {
    $data[$row['SemesterID']][] = $row;
}

$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);
$facultyId = $Certificate['FacultyID'] ?? null;
$Signatures = getAllSignatures($selectedServer, $facultyId);

if (!$Certificate || !$row || !$Signatures) {
    echo "No Result Found";
    exit();
}

$GradDate = isset($Certificate['GraduationDate']) ? $Certificate['GraduationDate']->format('Y/m/d') : 'N/A';
$AddDate = isset($Certificate['AdmissionDate']) ? $Certificate['AdmissionDate']->format('Y/m/d') : 'N/A';
$DateNow = date("d/m/Y");

function divition($gpa) {
    return $gpa >= 3.50 ? 'First class' : ($gpa >= 2.50 ? 'Two' : 'Three');
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Transcript</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            direction: rtl;
        }
        table.T1 {
            width: 90%;
            margin: auto;
            background-color: #fff;
            border: 0;
        }
        table.T2 {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: center;
        }
        .T2 th, .T2 td {
            border: 1px solid black;
            padding: 2px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .student-photo, .signature {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border: none;
            filter: brightness(1.1) contrast(1.1);
        }
        hr.new1 {
            border-top: 1px dashed red;
        }
    </style>
</head>
<body>

<table class="T1" align="center" width="90%"  border="0" dir="ltr" >
    <tr align="left">
<?php
$safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $Certificate['StudentID'] ?? $id);
$imagePath = "saved_images/$safeId.jpg";
?>

<td colspan="2">
    <?php if (file_exists($imagePath)): ?>
        <img class="student-photo" src="<?= htmlspecialchars($imagePath) ?>" style="width: 100px; height: 100px; object-fit: contain; border-radius: 6px;" alt="صورة الطالب" />
    <?php else: ?>
        <span style="color: gray; font-size: 14px;">📷 لا توجد صورة</span>
    <?php endif; ?>
</td>
        <td></td>
        <td></td>
      
    </tr>
    <tr align="left" >
         <td><b style="font-family:'TimeNews'; font-size:11px;"><?= $Certificate['AdmissionFormNo'] ?></b><b style="font-family:'TimeNews'; font-size:11px;">:الرقم الجامعي</b></td>
        <td ></td>
         <td ></td>
       
    </tr>
    <tr align="center"><td colspan="3"><b> كلية <?= $Certificate['FacultyName'] ?></b></td></tr>
    <tr align="center"><td colspan="3"><b><?= $Certificate['DegreeNameAr'] ?></b></td></tr>
    <tr align="center"><td colspan="3"><b>شهـادة تفـاصيـل<hr class="new1"></b></td></tr>
    <tr align="right">
        <td><b>الجنسية: <u><?= $Certificate['StudentNationality'] ?></u></b></td>
        <td colspan="2"><b>الاسم: <u><?= $Certificate['StudentName'] ?></u></b></td>
    </tr>
    <tr align="right">
        <th><u><?= $GradDate ?> :تاريخ التخرج</u></th>
        <th colspan="2"><u><?= $AddDate ?> :تاريخ الالتحاق</u></th>
    </tr>
    <tr align="right">
        <th><u><?= $Certificate['C_Hours'] ?>: الساعات المعتمدة الكلية</u></th>
        <th colspan="2"></th>
    </tr>
    <tr><th colspan="3">
        <div align="center">
            <?php
            $TotalHs = 0;
            $TotalGs = 0;
            foreach ($data as $semester => $subjects) {
            ?>
            <table class="T2" dir="rtl" >
                <tr><td colspan="3" align="right" style="border:none;">الفصل الدراسي <?= $semester ?> :</td></tr>
                <tr bgcolor="#f2f2f2">
                    <th width="70%">المقرر الدراسي</th>
                    <th width="5%">السـاعات</th>
                    <th width="15%">التقدير</th>
                </tr>
                <?php
                $totalHours = 0;
                $gradePoints = 0;
                foreach ($subjects as $subject) {
                    $totalHours += $subject['SubjectHours'];
                    $gradePoints += $subject['GradePoint'];
                ?>
                <tr>
                    <td align="right"> <?= $subject['SubjectName'] ?> </td>
                    <td> <?= $subject['SubjectHours'] ?> </td>
                    <td> <?= $subject['SubjectGradeAr'] ?> </td>
                </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>المعدل الفصلي = <?= number_format($gradePoints / $totalHours, 2) ?></td>
                    <td><?= $totalHours ?></td>
                    <td>المعدل التراكمي = <?= number_format(($TotalGs + $gradePoints) / ($TotalHs + $totalHours), 2) ?></td>
                </tr>
            </table>
            <br>
            <?php
            $TotalHs += $totalHours;
            $TotalGs += $gradePoints;
            }
            ?>
        </div>
    </th></tr>
    <tr align="right">
        <th colspan="3"><b>:يتم تحويل التقديرات إلى نقاط على النحو التالي</b><br>
            <center>أ=4.00, ب+=3.50, ب=3.00, ج+=2.50, ج=2.00, ر=0.00</center>
        </th>
    </tr>
    <tr align="center">
        <td colspan="2"><img class="signature" src="img/<?= $Signatures['ImgDeann'] ?>"></td>
        <td><img class="signature" src="img/<?= $Signatures['Imgregg'] ?>"></td>
    </tr>
    <tr align="center">
        <th colspan="2"><i><?= $Signatures['FacultyDean_NameA'] ?></i></th>
        <th><i><?= $Signatures['FacultyRegistrar_NameA'] ?></i></th>
    </tr>
    <tr align="center">
        <th colspan="2">عميد الكلية</th>
        <th>مسجل الكلية</th>
    </tr>
    <tr><th colspan="3"><br></th></tr>
    <tr align="center">
        <th colspan="3"><br><br><br><i><?= $Signatures['AcademicAffairsDean_NameA'] ?></i></th>
    </tr>
    <tr align="center">
        <th colspan="3">أمين الشؤون العلمية</th>
    </tr>
</table>
</body>
</html>
