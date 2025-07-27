<?php
header("Content-Type: application/json");
include '../db_connection.php'; // الاتصال بقواعد البيانات المختلفة

// قراءة بيانات JSON من الطلب
$input = json_decode(file_get_contents("php://input"), true);

// التحقق من وجود المعاملات المطلوبة
if (empty($input['student_id']) || empty($input['transactionId'])) {
    respondWithError("101", "Missing parameters: student_id or transactionId.");
}

// تنظيف وتنسيق القيم المدخلة
$studentID = strtoupper(trim($input['student_id']));
$transactionId = trim($input['transactionId']);

// تحديد السيرفر بناءً على بادئة الرقم الجامعي
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
    respondWithError("102", "Invalid student ID prefix.");
}

// الاتصال بقاعدة البيانات المحلية
$connLocal = connectToDatabase('local');
if (!$connLocal) {
    respondWithError("103", "Failed to connect to local DB.");
}

// التحقق من الدفع المسبق لنفس المعاملة
$checkSql = "SELECT amount FROM ReceivedPaymentsFromBank WHERE student_id = ? AND transaction_id = ?";
$checkStmt = sqlsrv_query($connLocal, $checkSql, [$studentID, $transactionId]);

if ($checkStmt && ($row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC))) {
    $amount = number_format(floatval($row['amount']), 2, '.', '');
    sqlsrv_close($connLocal);
    respondWithSuccess($studentID, $transactionId, $amount, "Already processed");
}

// الاتصال بقاعدة بيانات الطالب حسب البادئة
$connStudent = connectToDatabase($prefix);
if (!$connStudent) {
    respondWithError("104", "Failed to connect to student DB.");
}

// استعلام عن الرسوم
$sql = "SELECT StudyFees, RegistrationFees FROM dbo.StudentLatestFeesByAdmissionNo(?)";
$stmt = sqlsrv_query($connStudent, $sql, [$studentID]);

if (!$stmt) {
    sqlsrv_close($connStudent);
    respondWithError("105", "Query execution failed.");
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);
sqlsrv_close($connStudent);

if (!$row) {
    respondWithError("106", "Student not found or no fees.");
}

// حساب المبلغ الإجمالي
$totalAmount = floatval($row['StudyFees'] ?? 0) + floatval($row['RegistrationFees'] ?? 0);
$amountFormatted = number_format($totalAmount, 2, '.', '');

// حفظ السجل في قاعدة البيانات المحلية
$insertSql = "INSERT INTO ReceivedPaymentsFromBank (student_id, transaction_id, amount, received_at) VALUES (?, ?, ?, GETDATE())";
sqlsrv_query($connLocal, $insertSql, [$studentID, $transactionId, $totalAmount]);
sqlsrv_close($connLocal);

// إرسال الرد النهائي
respondWithSuccess($studentID, $transactionId, $amountFormatted, "Successful inquiry");

// دوال مساعدة

function respondWithError($code, $message) {
    echo json_encode([
        "status" => $code,
        "statusDescription" => $message,
        "msg" => $message
    ]);
    exit;
}

function respondWithSuccess($studentID, $transactionId, $amount, $description) {
    echo json_encode([
        "student_id" => $studentID,
        "transaction id" => $transactionId,
        "amount" => $amount,
        "minAmount" => $amount,
        "maxAmount" => $amount,
        "status" => "0",
        "statusDescription" => $description
    ]);
    exit;
}
?>
