<?php
function connect() {
    $serverName = "adminmanaer.database.windows.net";
    $connectionOptions = [
        "Database"     => "StudentAllForPayment",
        "Uid"          => "admini",
        "PWD"          => "P@ssw0rd",
        "CharacterSet" => "UTF-8"
    ];

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["status" => 500, "error" => "Database connection failed"]);
        exit;
    }

    return $conn;
}