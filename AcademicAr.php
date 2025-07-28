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
    die("رقم الطالب غير صحيح.");
}

$conn = connectToDatabase($selectedServer);

$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);
$Signatures = getAllSignatures($selectedServer, $Certificate['FacultyID']);

if (!$Certificate || !$row || !$Signatures) {
    die("لم يتم العثور على بيانات.");
}

$AddDate = $Certificate['AdmissionDate']->format('d/m/Y');
$DateNow = date("d/m/Y");

function divition($gpa) {
    return $gpa >= 3.5 ? 'First class' : ($gpa >= 2.5 ? 'Two' : 'Three');
}

$sql = "SELECT * FROM AcademicRecord(?) ORDER BY SemesterID, SubjectNameEng";
$stmt = sqlsrv_query($conn, $sql, [$id]);

if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $semester = $row['SemesterID'];
    $data[$semester][] = [
        'Subject' => $row['SubjectName'],
        'Hours' => $row['SubjectHours'],
        'Grade' => $row['SubjectGrade'],
        'GradePoints' => $row['GradePoint']
    ];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>السجل الأكاديمي</title>
    <style>
        table.T1 { border: 0; background-color: #fff; text-align: center; }
        table.T2 {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.T2, th.T2, td.T2 {
            border: 1px solid black;
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        hr.new1 {
            border-top: 1px dashed red;
        }
        img.signature {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border: 0px solid #666;
            border-radius: 2px;"
        }
        img.student-photo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border: 0px solid #000;
            border-radius: 2px;
        }
    </style>
</head>
<body>
<table class="T1" align="center" width="90%">
    <tr align="left">
        <td>
            <img class="student-photo" src="data:image/jpeg;base64,<?= base64_encode($Certificate['Photo']) ?>" alt="صورة الطالب" />
        </td>
        <th></th><th></th>
    </tr>
    <tr align="left">
        <td><b style="font-family:'TimeNews'; font-size:11px;"><?= $Certificate['AdmissionFormNo'] ?> :الرقم الجامعي</b></td>
        <td colspan="2"></td>
    </tr>
    <tr align="center">
        <td colspan="3"><b>كلية <?= $Certificate['FacultyName'] ?></b></td>
    </tr>
    <tr align="center">
        <td colspan="3"><b>سجل أكاديمي<hr class="new1"></b></td>
    </tr>
    <tr align="right">
        <td><b>الجنسية: <u><?= $Certificate['StudentNationality'] ?></u></b></td>
        <td colspan="2"><b>الاسم: <u><?= $Certificate['StudentName'] ?></u></b></td>
    </tr>
    <tr align="right">
        <th><b>التخصص:</b> <u><?= $Certificate['SpecializationName'] ?></u></th>
        <th colspan="2"><b>تاريخ القبول:</b> <u><?= $AddDate ?></u></th>
    </tr>
    <tr><th colspan="3">
        <div align="center">
        <?php
            $TotalHs = 0;
            $TotalGs = 0;
            foreach ($data as $semester => $entries):
                $semesterHours = 0;
                $semesterPoints = 0;
        ?>
            <table class="T2" dir="rtl">
                <tr>
                    <td colspan="3" align="right" style="border:none;">الفصل الدراسي <?= $semester ?>:</td>
                </tr>
                <tr bgcolor="f2f2f2">
                    <th width="70%">المقرر الدراسي</th>
                    <th width="15%">الساعات</th>
                    <th width="15%">التقدير</th>
                </tr>
                <?php foreach ($entries as $entry): 
                    $semesterHours += $entry['Hours'];
                    $semesterPoints += $entry['GradePoints'];
                ?>
                    <tr>
                        <td align="right">&nbsp;&nbsp;<?= htmlspecialchars($entry['Subject']) ?></td>
                        <td><?= $entry['Hours'] ?></td>
                        <td><?= $entry['Grade'] ?></td>
                    </tr>
                <?php endforeach; 
                    $TotalHs += $semesterHours;
                    $TotalGs += $semesterPoints;
                ?>
                <tr class="total-row">
                    <td>المعدل الفصلي = <?= number_format($semesterPoints / $semesterHours, 2) ?></td>
                    <td><?= $semesterHours ?></td>
                    <td>المعدل التراكمي = <?= number_format($TotalGs / $TotalHs, 2) ?></td>
                </tr>
            </table>
        <?php endforeach; ?>
        </div>
    </th></tr>
    <tr align="right">
        <th colspan="3">
            <b>:يتم تحويل التقديرات إلى نقاط على النحو التالي</b><br>
            <center>أ = 4.00, ب+ = 3.50, ب = 3.00, ج+ = 2.50, ج = 2.00, د+ = 1.50, د = 1.00, ر = 0.00</center>
        </th>
    </tr>
    <tr align="center">
        <td>
                    <img class="signature" src="img/<?= htmlspecialchars($Signatures['ImgDeann']) ?>" alt="توقيع العميد" />

        </td>
        <td colspan="2">
            <img class="signature" src="img/<?= htmlspecialchars($Signatures['Imgregg']) ?>" alt="توقيع المسجل" />
        </td>
    </tr>
    <tr align="center">
       
        <th colspan="2"><b><i><?= $Signatures['FacultyDean_NameA'] ?></i></b></th>
         <th><b><i><?= $Signatures['FacultyRegistrar_NameA'] ?></i></b></th>
    </tr>
    <tr align="center">
      
        <th colspan="2">عميد الكلية</th>
          <th>المسجل</th>
    </tr>
    <tr><th colspan="3"><br></th></tr>
    <tr align="center">
        <th colspan="3"><br><br><br><b><i><?= $Signatures['AcademicAffairsDean_NameA'] ?></i></b></th>
    </tr>
    <tr align="center">
        <th colspan="3">أمين الشؤون العلمية</th>
    </tr>
</table>
</body>
</html>
