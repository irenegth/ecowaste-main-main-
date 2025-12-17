<?php
// check-session.php
session_start();

header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user_id' => null,
    'role' => null
];

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['user_id'] = $_SESSION['user_id'];
    $response['role'] = $_SESSION['user_role'] ?? 'user';
}

echo json_encode($response);
exit();
?>