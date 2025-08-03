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
$DateNow = date("Y/m/d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript</title>
    <style>
        body {
            font-family: "TimeNews", sans-serif;
            font-size: 12px;
            margin: 0;
            direction: ltr;
        }
       table.T1 {
    width: 90%;
    margin: auto;
    background-color: #fff;
    border: 0;
    table-layout: auto; /* يسمح للأعمدة بالتوسع تلقائياً */
}
table.T1 td,
table.T1 th {
    white-space: nowrap; /* يمنع التفاف النص */
}
        table.T2 {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: center;
        }
        .T2 th, .T2 td {
            border: 1px solid black;
            padding: 0px;
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
<table class="T1" border="0" cellspacing="0" cellpadding="0">
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
        
    </tr>
    <tr align="left">
        <td colspan="2"><b style="font-family:'TimeNews'; font-size:11px;">Student No: <?= $Certificate['AdmissionFormNo'] ?></b></td>
       
        <td></td>
    </tr>
    <tr align="center"><td colspan="3" ><b>Faculty of <?= $Certificate['FacultyNameEng'] ?></b></td></tr>
    <tr align="center"><td colspan="3" white-space="nowrap"><b><?= $Certificate['DegreeNameEn'] ?></b></td></tr>
    <tr align="center"><td colspan="3"><b>Academic Transcript<hr class="new1"></b></td></tr>
    <tr align="left">
       
        <td colspan="2" style="width: 90%;"><b style="font-family:'TimeNews'; font-size:14px;">Name: <u><?= $Certificate['StudentNameEng'] ?></u></b></td>
         <td><b style="font-family:'TimeNews'; font-size:14px;">Nationality: <u><?= $Certificate['StudentNationalityEng'] ?></u></b></td>
    </tr>
    <tr align="left">
        <th colspan="2"><b style="font-family:'TimeNews'; font-size:14px;">Admission Date: <u><?= $AddDate ?></u></b></th>
        <th><b style="font-family:'TimeNews'; font-size:14px;">Date of Award: <u><?= $GradDate ?></u></b></th>
    </tr>
    <tr align="left">
        <th colspan="2"></th>
        <th><b style="font-family:'TimeNews'; font-size:14px;">Total Credit Hours: <u><?= $Certificate['C_Hours'] ?></u></b></th>
        
    </tr>
    <tr><th colspan="3">
        <div align="center">
            <?php
            $TotalHs = 0;
            $TotalGs = 0;
            foreach ($data as $semester => $subjects) {
            ?>
            <table class="T2">
                <tr><td colspan="3" align="left" style="border:none;">Semester <?= $semester ?>:</td></tr>
                <tr bgcolor="#f2f2f2">
                    <th width="70%">Subject</th>
                    <th width="5%">Hours</th>
                    <th width="15%">Grade</th>
                </tr>
                <?php
                $totalHours = 0;
                $gradePoints = 0;
                foreach ($subjects as $subject) {
                    $totalHours += $subject['SubjectHours'];
                    $gradePoints += $subject['GradePoint'];
                ?>
                <tr>
                    <td align="left"><?= $subject['SubjectNameEng'] ?></td>
                    <td><?= $subject['SubjectHours'] ?></td>
                    <td><?= $subject['SubjectGradeEng'] ?></td>
                </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>GPA = <?= number_format($gradePoints / $totalHours, 2) ?></td>
                    <td><?= $totalHours ?></td>
                    <td>CGPA = <?= number_format(($TotalGs + $gradePoints) / ($TotalHs + $totalHours), 2) ?></td>
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
    <tr align="center">
        <th colspan="3"><b style="font-family:'TimeNews'; font-size:12px;">Grades are converted into points as follows:<br>
            <center>A = 4.00, B+ = 3.50, B = 3.00, C+ = 2.50, C = 2.00, F = 0.00</center>
        </b></th>
    </tr>
    <tr align="center">
            <td><img class="signature" src="img/<?= $Signatures['Imgregg'] ?>"></td>
    
    <td colspan="2"><img class="signature" src="img/<?= $Signatures['ImgDeann'] ?>"></td>
    </tr>
    <tr align="center">
        
        <th><b style="font-family:'TimeNews'; font-size:14px;"><?= $Signatures['FacultyRegistrar_NameE'] ?></b></th>
        <th colspan="2"><b style="font-family:'TimeNews'; font-size:14px;"><?= $Signatures['FacultyDean_NameE'] ?></b></th>
    </tr>
    <tr align="center">
        <th colspan="2">Dean of Faculty</th>
        <th>Faculty Registrar</th>
    </tr>
    <tr><th colspan="3"><br></th></tr>
    <tr align="center">
        <th colspan="3"><br><br><br><b style="font-family:'TimeNews'; font-size:14px;"><?= $Signatures['AcademicAffairsDean_NameE'] ?></b></th>
    </tr>
    <tr align="center">
        <th colspan="3">Secretary of Academic Affairs</th>
    </tr>
</table>
</body>
</html>
