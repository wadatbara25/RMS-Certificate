<?php
function handleTransaction($caller) {
    $conn = connect();
    $data = json_decode(file_get_contents("php://input"), true);

    $studentID   = $data['student_id'] ?? null;
    $totalAmount = $data['amount'] ?? null;

    if (!$studentID || !$totalAmount) {
        errorResponse("Missing student_id or amount", 400);
    }

    $transactionId = uniqid("TXN_");

    $sql = "INSERT INTO Transactions 
        (TransactionID, StudentID, Amount, MinAmount, MaxAmount, RRN, StatusCode, StatusDescription, CreatedAt, CallerSource)
        VALUES (?, ?, ?, ?, ?, NULL, ?, ?, GETDATE(), ?)";

    $params = [
        $transactionId,
        $studentID,
        $totalAmount,
        $totalAmount,
        $totalAmount,
        "0",
        "Successful inquiry",
        $caller
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if (!$stmt) {
        errorResponse("Database error", 500);
    }

    successResponse([
        "transaction_id" => $transactionId,
        "student_id"     => $studentID,
        "amount"         => $totalAmount,
        "caller"         => $caller
    ]);
}