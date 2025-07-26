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
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <style>
            body {
                font-family: "Arial", sans-serif;
                background-color: #f9f9f9;
                color: #c00;
                text-align: center;
                direction: ltr;
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
            <h1>⚠️ Data Error</h1>
            <p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>
            <br>
            <p><a href="javascript:history.back();">🔙 Go Back</a></p>
        </div>
    </body>
    </html>';
    exit();
}

if (!$id) {
    renderErrorPage("Student ID is not specified.");
}

$conn = connectToDatabase($selectedServer);
if (!$conn) {
    renderErrorPage("Failed to connect to the database.");
}

$sql = "SELECT * FROM TranscriptF(?) ORDER BY SemesterID, SubjectNameEng";
$params = [$id];
$TRRR = sqlsrv_query($conn, $sql, $params);
if (!$TRRR) {
    renderErrorPage("Failed to retrieve transcript data.");
}

$Certificate = getCertificte($selectedServer, $id);
$row = getUserById($selectedServer, $id);

$facultyId = $Certificate['FacultyID'] ?? null;
if (!$facultyId) {
    renderErrorPage("Student's associated faculty not found.");
}

$Signatures = getAllSignatures($selectedServer, $facultyId);
if (!$Signatures) renderErrorPage("Signatures data not found.");
if (!$Certificate) renderErrorPage("Certificate data not found.");
if (!$row) renderErrorPage("Student data not found.");

$GradDate = $Certificate['GraduationDate']->format('d/m/Y');
$DateNow = date("d/m/Y");

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
    <meta charset="UTF-8">
    <title>General Certificate</title>
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
            font-family: 'Arial', sans-serif;
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
            object-fit: contain;
            image-rendering: crisp-edges;
            border: none;
            display: block;
            margin: 0 auto;
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

<button id="printBtn" onclick="printCertificate()">🖨️ Print Certificate</button>

<?php if (!empty($Certificate['Photo'])): ?>
    <div style="width: 120px; height: 120px; margin-bottom: 10px;" align="left">
        <img 
            src="data:image/jpeg;base64,<?= base64_encode($Certificate['Photo']) ?>" 
            alt="Student Photo"
            style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 2px solid #ccc; display: block;"
        />
    </div>
<?php endif; ?>

<h5>Student No: <?= htmlspecialchars($Certificate['AdmissionFormNo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h5>

<div align="center">
    <b style="font-size:24px;">CERTIFICATE</b>
</div>

<div align="left">
    <b style="font-size:20px;">This is to certify that the University Senate has awarded:</b>
</div>

<div align="left" style="font-size:16px;">
    <b><?= htmlspecialchars($Certificate['StudentNameEng'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b>
    - Nationality:
    <b><?= htmlspecialchars($Certificate['StudentNationalityEng'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b>
</div>

<table align="left" style="font-size:16px;" dir="ltr">
    <tr><td><b>The Degree of:</b></td><td><u><?= htmlspecialchars($Certificate['DegreeNameEn'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></td></tr>
    <tr><td><b>Faculty of:</b></td><td><u><?= htmlspecialchars($Certificate['FacultyNameEng'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></td></tr>
    <tr><td><b>Specialization:</b></td><td><u><?= htmlspecialchars($Certificate['DepartmentNameEng'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></u></td></tr>
    <tr><td><b>Date of Award:</b></td><td><u><?= $GradDate ?></u></td></tr>
    <tr><td><b>Date of Issue:</b></td><td><u><?= $DateNow ?></u></td></tr>
</table>

<table width="100%">
    <tr align="center">
        <td><img width="100" height="100" src="img/<?= htmlspecialchars($Signatures['Imgregg'] ?? 'not-found.png') ?>" alt="Registrar Signature"></td>
        <td></td>
        <td><img width="100" height="100" src="img/<?= htmlspecialchars($Signatures['ImgDeann'] ?? 'not-found.png') ?>" alt="Dean Signature"></td>
    </tr>
    <tr align="center">
        <th><b><?= htmlspecialchars($Signatures['FacultyRegistrar_NameE'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></th>
        <th></th>
        <th><b><?= htmlspecialchars($Signatures['FacultyDean_NameE'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></th>
    </tr>
    <tr align="center">
        <th><b>Registrar</b></th>
        <th></th>
        <th><b>Dean of Faculty</b></th>
    </tr>

        <tr align="center">
        <td colspan="3"><b><br><br><br> </b></td>   
    </tr>     
    <tr align="center">
        <th colspan="3"><b><?= htmlspecialchars($Signatures['AcademicAffairsDean_NameE'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></th>
    </tr>
    <tr align="center">
        <th colspan="3"><b>Secretary of Academic Affairs</b></th>
    </tr>
</table>

</body>
</html>

<?php sqlsrv_close($conn); ?>
