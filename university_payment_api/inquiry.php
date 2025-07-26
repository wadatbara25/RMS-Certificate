<?php
header("Content-Type: application/json");
include '../db_connection.php'; // الاتصال بقواعد البيانات المختلفة

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['student_id']) || !isset($input['transactionId'])) {
    echo json_encode([
        "status" => "101",
        "statusDescription" => "Missing parameters: student_id or transactionId.",
        "msg" => "Missing parameters: student_id or transactionId."
    ]);
    exit;
}

$studentID = strtoupper(trim($input['student_id']));
$transactionId = trim($input['transactionId']);

// استخراج البادئة لتحديد رقم السيرفر المناسب
$prefix4 = substr($studentID, 0, 4);
$prefix3 = substr($studentID, 0, 3);

$facultyPrefixes = [
    'FCS'  => '1',
    'COM'  => '1',
    'ECO'  => '2',
    'FED'  => '3',
    'EDU'  => '3',
    'FEDU' => '3',
    'LAW'  => '4',
    'LMS'  => '5',
    'MAS'  => '5',
    'NUR'  => '6',
    'NURS' => '6',
    'FVM'  => '7',
    'FMED' => '8',
    'MED'  => '8'
];

$prefix = $facultyPrefixes[$prefix4] ?? ($facultyPrefixes[$prefix3] ?? null);

if (!$prefix) {
    echo json_encode([
        "status" => "102",
        "statusDescription" => "Invalid student ID prefix."
    ]);
    exit;
}

// الاتصال بقاعدة البيانات المحلية للتحقق من الدفع السابق
$connLocal = connectToDatabase('local'); // تأكد أن هذا الاتصال المحلي يحتوي جدول الدفعيات
if ($connLocal === false) {
    echo json_encode([
        "status" => "103",
        "statusDescription" => "Failed to connect to local DB"
    ]);
    exit;
}

// تحقق من السجل السابق
$checkSql = "SELECT amount FROM ReceivedPaymentsFromBank WHERE student_id = ? AND transaction_id = ?";
$checkStmt = sqlsrv_query($connLocal, $checkSql, [$studentID, $transactionId]);

if ($checkStmt && ($row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC))) {
    $amount = number_format(floatval($row['amount']), 2, '.', '');
    echo json_encode([
        "student_id" => $studentID,
        "transaction id" => $transactionId,
        "amount" => $amount,
        "minAmount" => $amount,
        "maxAmount" => $amount,
        "status" => "0",
        "statusDescription" => "Already processed"
    ]);
    exit;
}

// استعلام جديد
$conn = connectToDatabase($prefix);
if ($conn === false) {
    echo json_encode([
        "status" => "104",
        "statusDescription" => "Failed to connect to student DB"
    ]);
    exit;
}

$sql = "SELECT StudyFees, RegistrationFees FROM dbo.StudentLatestFeesByAdmissionNo(?)";
$params = [$studentID];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    sqlsrv_close($conn);
    echo json_encode([
        "status" => "105",
        "statusDescription" => "Query execution failed"
    ]);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

if (!$row) {
    echo json_encode([
        "status" => "106",
        "statusDescription" => "Student not found or no fees"
    ]);
    exit;
}

$total = floatval($row['StudyFees'] ?? 0) + floatval($row['RegistrationFees'] ?? 0);
$amountFormatted = number_format($total, 2, '.', '');

// حفظ السجل في قاعدة البيانات المحلية
$insertSql = "INSERT INTO ReceivedPaymentsFromBank (student_id, transaction_id, amount, received_at) VALUES (?, ?, ?, GETDATE())";
sqlsrv_query($connLocal, $insertSql, [$studentID, $transactionId, $total]);
sqlsrv_close($connLocal);

// إرسال الرد النهائي
echo json_encode([
    "student_id" => $studentID,
    "transaction id" => $transactionId,
    "amount" => $amountFormatted,
    "minAmount" => $amountFormatted,
    "maxAmount" => $amountFormatted,
    "status" => "0",
    "statusDescription" => "Successful inquiry"
]);
?>
