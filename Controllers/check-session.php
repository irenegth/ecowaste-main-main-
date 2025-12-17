<?php
session_start();
require_once __DIR__ . '/../Config/Auth.php';

$auth = new Auth();
$isLoggedIn = $auth->isLoggedIn();

header('Content-Type: application/json');
echo json_encode([
    'valid' => $isLoggedIn !== false,
    'user' => $isLoggedIn ?: null
]);
exit;
?>