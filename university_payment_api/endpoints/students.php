<?php
function getStudents() {
    $conn = connect();
    $sql = "SELECT StudentID, FullName, Balance FROM Students";
    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        errorResponse("Database error", 500);
    }

    $students = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $students[] = $row;
    }

    successResponse($students);
}