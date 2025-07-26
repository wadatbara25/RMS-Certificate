<?php
header("Content-Type: application/json");

include '../db_connection.php';

// ===== Helper: Send Consistent Error Responses =====
function sendErrorResponse(int $httpCode, string $appStatus, string $description, string $logMessage = '', string $wwwAuthenticate = ''): void {
    http_response_code($httpCode);
    if ($wwwAuthenticate) header("WWW-Authenticate: $wwwAuthenticate");
    if ($logMessage) error_log("[Payment API Error][$appStatus] $logMessage");
    echo json_encode([
        "status" => $appStatus,
        "statusDescription" => $description,
        "msg" => $description
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== Determine DB Server from Student ID Prefix =====
function getServerFromStudentID(string $studentID): string {
    $prefixMap = [
        'FCS' => '1', 'COM' => '1',
        'ECO' => '2',
        'FED' => '3', 'EDU' => '3', 'FEDU' => '3',
        'LAW' => '4',
        'LMS' => '5', 'MAS' => '5',
        'NUR' => '6', 'NURS' => '6',
        'FVM' => '7',
        'FMED'=> '8', 'MED' => '8'
    ];
    $id = strtoupper(preg_replace('/\s+/', '', $studentID));
    return $prefixMap[substr($id, 0, 4)] ?? $prefixMap[substr($id, 0, 3)] ?? '1';
}

// ===== API Security: Validate Bearer Token =====
$expectedToken = "mySuperSecretKey123"; // Replace securely
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
    sendErrorResponse(401, "401", "Authentication Required", 
        "Authorization header missing/malformed.", 'Bearer realm="Payment API"');
}

if ($matches[1] !== $expectedToken) {
    sendErrorResponse(401, "401_INVALID_TOKEN", "Invalid Token", 
        "Token mismatch.", 'Bearer realm="Payment API", error="invalid_token"');
}

// ===== Read and Validate JSON Request =====
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);
error_log("Payment API Input: " . ($rawInput ?: 'Empty JSON'));

if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    sendErrorResponse(400, "400_JSON_ERROR", "Malformed JSON", json_last_error_msg());
}

$rrn           = trim($input['RRN'] ?? '');
$transactionId = trim($input['transactionId'] ?? '');
$amount        = floatval($input['Amount'] ?? 0);
$studentID     = strtoupper(trim($input['student_id'] ?? ''));

if (!$rrn || !$transactionId || $amount <= 0 || !$studentID) {
    sendErrorResponse(400, "400_INVALID_PARAMS", "Missing or invalid parameters.", 
        "RRN: '$rrn', TxID: '$transactionId', Amt: '$amount', SID: '$studentID'");
}

// ===== Connect to DB Server Based on student_id =====
$serverKey = getServerFromStudentID($studentID);

if (!isset($GLOBALS['servers'][$serverKey])) {
    sendErrorResponse(500, "500_SERVER_MAP_ERROR", "Invalid server key derived.", 
        "No config for serverKey '$serverKey' from '$studentID'");
}

$conn = connectToDatabase($serverKey);
if (!$conn) {
    sendErrorResponse(500, "500_DB_CONNECT", "Database connection failed.", 
        "Server: $serverKey. Errors: " . print_r(sqlsrv_errors(), true));
}

// ===== Check Duplicate Payment =====
$checkSQL  = "SELECT 1 FROM ReceivedPaymentsFromBank WHERE RRN = ? AND TransactionID = ?";
$checkStmt = sqlsrv_query($conn, $checkSQL, [$rrn, $transactionId]);

if (!$checkStmt) {
    $error = print_r(sqlsrv_errors(), true);
    sqlsrv_close($conn);
    sendErrorResponse(500, "500_DB_QUERY", "Duplicate check failed.", $error);
}

if (sqlsrv_fetch($checkStmt)) {
    sqlsrv_free_stmt($checkStmt);
    sqlsrv_close($conn);
    sendErrorResponse(200, "200_DUPLICATE", "Duplicate payment already recorded.");
}
sqlsrv_free_stmt($checkStmt);

// ===== Insert Payment =====
$insertSQL = "INSERT INTO ReceivedPaymentsFromBank (RRN, TransactionID, Amount, StatusCode, StatusDescription, RawRequest)
              VALUES (?, ?, ?, ?, ?, ?)";
$params = [$rrn, $transactionId, $amount, "00", "Posted Successfully", $rawInput];

$stmt = sqlsrv_query($conn, $insertSQL, $params);

if (!$stmt) {
    $error = print_r(sqlsrv_errors(), true);
    sqlsrv_close($conn);
    sendErrorResponse(500, "500_DB_INSERT", "Insertion failed.", $error);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// ===== Success Response =====
http_response_code(200);
echo json_encode([
    "transaction_id"     => $transactionId,
    "amount"             => number_format($amount, 2, '.', ''),
    "status"             => "000",
    "statusDescription"  => "Payment successfully recorded.",
    "msg"                => "Payment successfully recorded."
], JSON_UNESCAPED_UNICODE);
?>
