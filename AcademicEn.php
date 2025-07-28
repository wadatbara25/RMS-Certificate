<?php
session_start();

include 'db_connection.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$selectedServer = $_SESSION["server"];
$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid student ID.");
}

// الاتصال بقاعدة البيانات
$conn = connectToDatabase($selectedServer);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// جلب السجل الأكاديمي للطالب
$sql = "SELECT * FROM AcademicRecord(?) ORDER BY SemesterID, SubjectNameEng";
$stmt = sqlsrv_query($conn, $sql, [$id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $semester = $row['SemesterID'];
    if (!isset($data[$semester])) {
        $data[$semester] = [];
    }
    $data[$semester][] = [
        'Subject'     => $row['SubjectNameEng'],
        'Hours'       => $row['SubjectHours'],
        'Grade'       => $row['SubjectGradeEng'],
        'GradePoints' => $row['GradePoint'],
    ];
}

// جلب بيانات الطالب والشهادات والتواقيع
$Certificate = getCertificte($selectedServer, $id);
$user = getUserById($selectedServer, $id);
$Signatures = getAllSignatures($selectedServer, $Certificate['FacultyID'] ?? null);

if (!$Certificate || !$user || !$Signatures) {
    die("No data found.");
}

// تواريخ التنسيق
$AddDate = isset($Certificate['AdmissionDate']) && $Certificate['AdmissionDate'] instanceof DateTime 
    ? $Certificate['AdmissionDate']->format('d/m/Y') : '';
$DateNow = date("d/m/Y");

// حماية دالة division من إعادة التعريف
if (!function_exists('division')) {
    function division($gpa) {
        if ($gpa >= 3.5) return 'First class';
        if ($gpa >= 2.5) return 'Second class';
        return 'Third class';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Academic Record</title>
    <style>
        table.T1 {
            border: none;
            background-color: #fff;
            text-align: center;
            width: 90%;
            margin: auto;
        }
        table.T2 {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        table.T2, th.T2, td.T2 {
            border: 1px solid black;
        }
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        hr.new1 {
            border-top: 1px dashed red;
        }
        img.signature, img.student-photo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 2px;
            border: none;
        }
    </style>
</head>
<body>
    <table class="T1" align="center">
        <tr align="left">
            <td>
                <img class="student-photo" src="data:image/jpeg;base64,<?= base64_encode($Certificate['Photo']) ?>" alt="Student Photo" />
            </td>
            <td></td>
            <td></td>
        </tr>
        <tr align="left">
            <td><b style="font-family:'TimeNews'; font-size:11px;">Student No: <?= htmlspecialchars($Certificate['AdmissionFormNo']) ?></b></td>
            <td colspan="2"></td>
        </tr>
        <tr align="center">
            <td colspan="3"><b>Faculty of <?= htmlspecialchars($Certificate['FacultyNameEng']) ?></b></td>
        </tr>
        <tr align="center">
            <td colspan="3"><b>ACADEMIC RECORD<hr class="new1"></b></td>
        </tr>
        <tr align="left">
            <td colspan="2"><b>Name: <u><?= htmlspecialchars($Certificate['StudentNameEng']) ?></u></b></td>
            <td><b>Nationality: <u><?= htmlspecialchars($Certificate['StudentNationalityEng']) ?></u></b></td>
        </tr>
        <tr align="left">
            <td colspan="2"><b>Admission Date: </b> <u><?= $AddDate ?></u></td>
            <td><b>Specialization: </b> <u><?= htmlspecialchars($Certificate['SpecializationNameE']) ?></u></td>
        </tr>
        <tr>
            <td colspan="3">
                <div align="center">
                    <?php
                    $TotalHours = 0;
                    $TotalPoints = 0;
                    foreach ($data as $semester => $subjects):
                        $semesterHours = 0;
                        $semesterPoints = 0;
                    ?>
                        <table class="T2">
                            <tr>
                                <td colspan="3" align="left" style="border:none;">Semester <?= htmlspecialchars($semester) ?>:</td>
                            </tr>
                            <tr bgcolor="#f2f2f2">
                                <th width="70%">Subject</th>
                                <th width="15%">Hours</th>
                                <th width="15%">Grade</th>
                            </tr>
                            <?php foreach ($subjects as $sub):
                                $semesterHours += $sub['Hours'];
                                $semesterPoints += $sub['GradePoints'];
                            ?>
                                <tr>
                                    <td align="left">&nbsp;&nbsp;<?= htmlspecialchars($sub['Subject']) ?></td>
                                    <td><?= htmlspecialchars($sub['Hours']) ?></td>
                                    <td><?= htmlspecialchars($sub['Grade']) ?></td>
                                </tr>
                            <?php endforeach;
                            $TotalHours += $semesterHours;
                            $TotalPoints += $semesterPoints;
                            ?>
                            <tr class="total-row">
                                <td>GPA = <?= number_format($semesterPoints / $semesterHours, 2) ?></td>
                                <td><?= $semesterHours ?></td>
                                <td>CGPA = <?= number_format($TotalPoints / $TotalHours, 2) ?></td>
                            </tr>
                        </table>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <tr align="left">
            <td colspan="3">
                <b>Grades are converted into points as follows:</b><br>
                <center>A = 4.00, B+ = 3.50, B = 3.00, C+ = 2.50, C = 2.00, F = 0.00</center>
            </td>
        </tr>
        <tr align="center">
            <td><img class="signature" src="img/<?= htmlspecialchars($Signatures['Imgregg']) ?>" alt="Registrar Signature" /></td>
            <td colspan="2"><img class="signature" src="img/<?= htmlspecialchars($Signatures['ImgDeann']) ?>" alt="Dean Signature" /></td>
        </tr>
        <tr align="center">
            <td><b><i><?= htmlspecialchars($Signatures['FacultyRegistrar_NameE']) ?></i></b></td>
            <td colspan="2"><b><i><?= htmlspecialchars($Signatures['FacultyDean_NameE']) ?></i></b></td>
        </tr>
        <tr align="center">
            <td>Registrar</td>
            <td colspan="2">Dean of Faculty</td>
        </tr>
        <tr><td colspan="3"><br></td></tr>
        <tr align="center">
            <td colspan="3"><br><br><br><b><i><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameE']) ?></i></b></td>
        </tr>
        <tr align="center">
            <td colspan="3">Secretary of Academic Affairs</td>
        </tr>
    </table>
</body>
</html>
<?php
// اغلاق الاتصال وتحرير الموارد بعد الانتهاء
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
