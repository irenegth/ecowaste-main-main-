<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

session_start();

$response = ['logged_in' => false];
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['user_id'] = $_SESSION['user_id'] ?? null;
    $response['role'] = $_SESSION['user_role'] ?? 'user';
}

echo json_encode($response);
exit();
