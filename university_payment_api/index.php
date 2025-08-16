<?php
require 'db.php';
require 'response.php';
require 'endpoints/students.php';
require 'endpoints/transactions.php';
require 'endpoints/fees.php';

$validApiKeys = [
    "sk_live_admin"   => "Admin System",
    "sk_live_mobile"  => "Mobile App",
    "sk_live_partner" => "Partner Integration"
];

$headers = getallheaders();
$apiKey = $headers['X-API-KEY'] ?? '';

if (!array_key_exists($apiKey, $validApiKeys)) {
    errorResponse("Invalid or missing API Key", 403);
}

$caller = $validApiKeys[$apiKey];
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

switch ("$method /$path") {
    case 'GET /students':
        getStudents();
        break;

    case 'POST /transactions':
        handleTransaction($caller);
        break;

    case 'GET /fees':
        getStudentFees($caller);
        break;

    default:
        errorResponse("Invalid endpoint", 404);
}