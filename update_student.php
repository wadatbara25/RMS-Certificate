<?php
header('Content-Type: application/json');
include 'db_connection.php';
session_start();
include 'db_connection.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$studentId = $input['id'];
$studentName = $input['sename'];
$studentEmail = $input['username'];

// Assuming you have a database connection already established
// $conn = new mysqli('localhost', 'username', 'password', 'database');

// Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

$sql = "UPDATE Students SET StudentNameEng= ?, AdmissionFormNo = ?, StudentPhoto = ? WHERE StudentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $studentName, $studentEmail, $studentId);

$response = array();

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
}

$stmt->close();
// $conn->close();

echo json_encode($response);
?>
