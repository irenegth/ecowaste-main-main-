<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once __DIR__ . '/../Config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit();
}

// check existing
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'user';
$sql = 'INSERT INTO users (fullname, email, password, user_role, status) VALUES (?, ?, ?, ?, "approved")';
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $name, $email, $hash, $role);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
}

exit();
