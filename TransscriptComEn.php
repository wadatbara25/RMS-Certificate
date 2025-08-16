<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
function safe($value) {
    return htmlspecialchars($value ?? '');
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
$GradDate = $Certificate['GraduationDate'] instanceof DateTime ? $Certificate['GraduationDate']->format('Y/m/d') : '';
$AddDate = $Certificate['AdmissionDate']->format('Y/m/d');
$DateNow = date("Y/m/d");



$sql = "SELECT * FROM TranscriptF(?) ORDER BY SemesterID, SubjectNameEng";
$stmt = sqlsrv_query($conn, $sql, [$id]);

if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $semester = $row['SemesterID'];
    $data[$semester][] = [
        'Subject' => $row['SubjectNameEng'],
        'Hours' => $row['SubjectHours'],
        'HoursTxt' => $row['SubjectHoursTxt'],
        'GradeAr' => $row['SubjectGradeAr'],
        'GradeEng' => $row['SubjectGradeEng'],
        'GradePoints' => $row['GradePointN'] ?? 0,
    ];
}
// تاريخ التخرج وتاريخ اليوم
$GradDate = $Certificate['GraduationDate'] instanceof DateTime ? $Certificate['GraduationDate']->format('d/m/Y') : '';
$DateNow = date("d/m/Y");
$AddDate = $Certificate['AdmissionDate']->format('d/m/Y');


// Division calculation
function divisionWithHonors($gpa) {
    return match (true) {
        $gpa >= 3.50 => 'First Class',
        $gpa >= 3.00 => 'Second Class - Division One',
        $gpa >= 2.50 => 'Second Class - Division Two',
        default => 'Third Class'
    };
}

function divisionGeneral($gpa) {
    return match (true) {
        $gpa >= 3.50 => 'First Class',
        $gpa >= 2.50 => 'Second Class',
        default => 'Third Class'
    };
}

$isHonors = str_contains($Certificate['DegreeNameEn'], 'Honours');
$message = $isHonors ? divisionWithHonors($Certificate['CGPA']) : divisionGeneral($Certificate['CGPA']);
$Class = $isHonors ? 'Class' : 'Degree';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Academic Transcript</title>
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
            border: 0;
            border-radius: 2px;
        }
        img.student-photo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border: 0;
            border-radius: 2px;
        }
    </style>
</head>
<body>
<table class="T1" align="center" width="90%" border="0" dir="rtl">
    <tr align="left">
        <?php
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $Certificate['StudentID'] ?? $id);
        $imagePath = "saved_images/$safeId.jpg";
        ?>
        <th></th>
        <td colspan="2">
            <?php if (file_exists($imagePath)): ?>
                <img class="student-photo" src="<?= htmlspecialchars($imagePath) ?>" alt="Student photo" />
            <?php else: ?>
                <span style="color: gray; font-size: 14px;">📷 No photo available</span>
            <?php endif; ?>
        </td>
        
    </tr>
    <tr align="left">
        <td colspan="2"></td>
        <td><b style="font-family:'TimeNews'; font-size:11px;"><?= htmlspecialchars($Certificate['AdmissionFormNo']) ?> :Student Number</b></td>
    </tr>
    <tr align="center">
        <td colspan="3"><b>Faculty of <?= htmlspecialchars($Certificate['FacultyNameEng']) ?></b></td>
    </tr>
     <tr align="center">
        <td colspan="3"><b><?php echo $Certificate['DegreeNameEn'];?></b></td>
   
     </tr>
    <tr align="center">
        <td colspan="3"><b>Academic Transcript<hr class="new1"></b></td>
    </tr>
    <tr align="left" >
        <td><b>Nationality: <u><?= htmlspecialchars($Certificate['StudentNationalityEng']) ?></u></b></td>
        <td colspan="2"><b>Name: <u><?= htmlspecialchars($Certificate['StudentNameEng']) ?></u></b></td>
    </tr>
     <tr align="left">
        
         <th ><b><?php echo $Class;?> </b>&nbsp;<u><?php echo $message;?> </u></th>
        <th colspan="2"></th>   
    </tr>
         <tr align="left">
        
        <th><b></b>&nbsp;<u><?php echo $GradDate;?> :Graduation Date</u></th>
        <th colspan="2"><b></b>&nbsp;<u><?php echo $AddDate;?>: Admission Date</u></th>
    </tr>
    
     <tr align="left">
       
         <th> <b></b> &nbsp;<u><?php  echo $Certificate['C_Hours']; ?>:Total Credit Hours</u></th>
         <th colspan="2"></th>
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
            <table class="T2" dir="ltr">
                <tr>
                    <td colspan="3" align="left" style="border:none;">Semester <?= htmlspecialchars($semester) ?>:</td>
                </tr>
                <tr bgcolor="#f2f2f2">
                    <th width="70%">Subject</th>
                    <th width="15%">Hours</th>
                    <th width="15%">Grade</th>
                </tr>
                <?php foreach ($entries as $entry): 
                    $semesterHours += (float)$entry['Hours'];
                    $semesterPoints += (float)$entry['GradePoints'];
                ?>
                    <tr>
                        <td align="left"><?= htmlspecialchars($entry['Subject']) ?></td>
                        <td><?= safe($entry['HoursTxt']) ?></td>
                        <td><?= htmlspecialchars($entry['GradeEng']) ?></td>
                    </tr>
                <?php endforeach; 
                    $TotalHs += $semesterHours;
                    $TotalGs += $semesterPoints;
                ?>
                <tr class="total-row">
                    <td>GPA = <?= $semesterHours > 0 ? number_format($semesterPoints / $semesterHours, 2) : 'N/A' ?></td>
                    <td><?= number_format($semesterHours, 0) ?></td>
                    <td>CGPA = <?= $TotalHs > 0 ? number_format($TotalGs / $TotalHs, 2) : 'N/A' ?></td>
                </tr>
            </table><br>
        <?php endforeach; ?>
        </div>
    </th></tr>
    <tr align="left">
        <th colspan="3">
            <b>:Grades are converted into points as follows</b><br>
            <center>A = 4.00, B+ = 3.50, B = 3.00, C+ = 2.50, C = 2.00, D+ = 1.50, D = 1.00, F = 0.00</center>
        </th>
    </tr>
    <tr align="center">
        <td>
            <img class="signature" src="img/<?= htmlspecialchars($Signatures['ImgDeann']) ?>" alt="Dean's signature" />
        </td>
        <td colspan="2">
            <img class="signature" src="img/<?= htmlspecialchars($Signatures['Imgregg']) ?>" alt="Registrar's signature" />
        </td>
    </tr>
    <tr align="center">
        <th colspan="2"><b><i><?= htmlspecialchars($Signatures['FacultyDean_NameE']) ?></i></b></th>
        <th><b><i><?= htmlspecialchars($Signatures['FacultyRegistrar_NameE']) ?></i></b></th>
    </tr>
    <tr align="center">
        <th colspan="2">Dean of Faculty</th>
        <th>Faculty Registrar</th>
    </tr>
    <tr><th colspan="3"><br></th></tr>
    <tr align="center">
        <th colspan="3"><br><br><br><b><i><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameE']) ?></i></b></th>
    </tr>
    <tr align="center">
        <th colspan="3">Secretary of Academic Affairs</th>
    </tr>
</table>
</body>
</html>
