<?php
function getStudentFees($caller) {
    $conn = connect();
    $studentID = strtoupper(trim($_GET['id'] ?? ''));

    if (!$studentID) {
        errorResponse("Missing student ID", 400);
    }

    $sql = "SELECT StudyFees, RegistrationFees FROM tbl_StudentAllForPayment WHERE StudentID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$studentID]);

    if (!$stmt) {
        errorResponse("Database error", 500);
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$row) {
        errorResponse("Student not found", 404);
    }

    $total = floatval($row['StudyFees'] ?? 0) + floatval($row['RegistrationFees'] ?? 0);

    successResponse([
        "student_id"        => $studentID,
        "study_fees"        => number_format($row['StudyFees'], 2, '.', ''),
        "registration_fees" => number_format($row['RegistrationFees'], 2, '.', ''),
        "total"             => number_format($total, 2, '.', ''),
        "caller"            => $caller
    ]);
}