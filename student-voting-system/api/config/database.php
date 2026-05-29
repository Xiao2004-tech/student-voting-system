<?php
// api/config/database.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_voting_db');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]);
        exit();
    }
    $conn->set_charset("utf8");
    return $conn;
}

function sendResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    $res = ["success" => $success, "message" => $message];
    if ($data !== null) $res["data"] = $data;
    echo json_encode($res);
    exit();
}

function sanitize($val) {
    return htmlspecialchars(strip_tags(trim($val)));
}
