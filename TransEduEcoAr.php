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
        'Subject' => $row['SubjectName'],
        'Hours' => $row['SubjectHours'],
        'HoursTxt' => $row['SubjectHoursTxt'],
        'GradeAr' => $row['SubjectGradeAr'],
        'GradeEng' => $row['SubjectGradeEng'],
        'GradePoints' => $row['GradePointN'] ?? 0,
    ];
}

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
    <title> شهادة التفاصيل  </title>
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
<table class="T1" align="center" width="90%">
    <tr align="left">
        <?php
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $Certificate['StudentID'] ?? $id);
        $imagePath = "saved_images/$safeId.jpg";
        ?>
        <td colspan="2">
            <?php if (file_exists($imagePath)): ?>
                <img class="student-photo" src="<?= htmlspecialchars($imagePath) ?>" alt="صورة الطالب" />
            <?php else: ?>
                <span style="color: gray; font-size: 14px;">📷 لا توجد صورة</span>
            <?php endif; ?>
        </td>
        <th></th><th></th>
    </tr>
    <tr align="left">
        <td><b style="font-family:'TimeNews'; font-size:11px;"><?= htmlspecialchars($Certificate['AdmissionFormNo']) ?> :الرقم الجامعي</b></td>
        <td colspan="2"></td>
    </tr>
    <tr align="center">
        <td colspan="3"><b>كلية <?= htmlspecialchars($Certificate['FacultyName']) ?></b></td>
    </tr>
    <tr align="center">
        <td colspan="3"><b><?php echo $Certificate['DegreeNameAr'];?></b></td>
    </tr>
    <tr align="center">
        <td colspan="3"><b>شهـادة تفـاصيـل<hr class="new1"></b></td>
    </tr>
    <tr align="right">
        <td><b>الجنسية: <u><?php echo $Certificate['StudentNationality'];?></u> </b> </td>
        <td colspan="2"> <b>الاسم:<u> <?php echo $Certificate['StudentName'];?> </u> </b> </td>
    </tr>
    <tr align="right">
        <th colspan="2"><b><?php echo $Class;?> :</b>&nbsp;<u><?php echo $message;?> </u></th>
        <th colspan="2"><b>التخصص :</b><u><?php echo $Certificate['SpecializationName'];?></u></th>   
    </tr>
    <tr align="right">
        <th><b></b>&nbsp;<u><?php echo $GradDate;?> :تاريخ  التخرج</u></th>
        <th colspan="2"><b></b>&nbsp;<u><?php echo $AddDate;?> :تاريخ الالتحاق</u></th>
    </tr>
    <tr align="right">
        <th> <b></b> &nbsp;<u><?php  echo $Certificate['C_Hours']; ?>: الساعات المعتمدة الكلية</u></th>
        <th colspan="2"></u></th>
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
                    <td colspan="4" align="right" style="border:none;">الفصل الدراسي <?= htmlspecialchars($semester) ?>:</td>
                </tr>
                <tr bgcolor="#f2f2f2">
                    <th width="40%">المقرر الدراسي</th>
                    <th width="20%">الساعات</th>
                    <th width="20%">التقدير</th>
                </tr>
                <?php foreach ($entries as $entry): 
                    $semesterHours += (float)$entry['Hours'];
                    $semesterPoints += (float)$entry['GradePoints'];
                ?>
                    <tr>
                        <td align="right"><?= htmlspecialchars($entry['Subject']) ?></td>
                        <td><?= htmlspecialchars($entry['HoursTxt']) ?></td>
                        <td><?= htmlspecialchars($entry['GradeAr']) ?></td>
                    </tr>
                <?php endforeach; 
                    $TotalHs += $semesterHours;
                    $TotalGs += $semesterPoints;
                ?>
                <tr class="total-row">
                    <td colspan="2">المعدل الفصلي = <?= $semesterHours > 0 ? number_format($semesterPoints / $semesterHours, 2) : 'N/A' ?></td>
                    <td colspan="2">المعدل التراكمي = <?= $TotalHs > 0 ? number_format($TotalGs / $TotalHs, 2) : 'N/A' ?></td>
                </tr>
            </table><br>
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
        <th colspan="2"><b><i><?= htmlspecialchars($Signatures['FacultyDean_NameA']) ?></i></b></th>
        <th><b><i><?= htmlspecialchars($Signatures['FacultyRegistrar_NameA']) ?></i></b></th>
    </tr>
    <tr align="center">
        <th colspan="2">عميد الكلية</th>
        <th>المسجل</th>
    </tr>
    <tr><th colspan="3"><br></th></tr>
    <tr align="center">
        <th colspan="3"><br><br><br><b><i><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameA']) ?></i></b></th>
    </tr>
    <tr align="center">
        <th colspan="3">أمين الشؤون العلمية</th>
    </tr>
</table>
</body>
</html>
