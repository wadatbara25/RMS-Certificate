<?php
function successResponse($data) {
    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => $code, "error" => $message]);
    exit;
}