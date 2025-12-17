<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

session_start();

require_once __DIR__ . '/../Config/db.php';

$data = $_POST;
if (empty($data)) {
    // try raw JSON
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
}

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    // Demo fallback
    $role = (strpos($email, 'admin') !== false) ? 'admin' : 'user';
    $_SESSION['logged_in'] = true;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    echo json_encode(['success' => true, 'demo' => true, 'role' => $role, 'name' => explode('@', $email)[0]]);
    exit();
}

$stmt = $conn->prepare('SELECT id, email, password, fullname, user_role FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$valid = false;
// detect if password is hashed
if (isset($user['password']) && strlen($user['password']) > 0) {
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        // not a hash we can verify, fallback to plain compare
        $valid = ($password === $user['password']);
    } else {
        // try password_verify
        $valid = password_verify($password, $user['password']);
    }
}

if ($valid) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['user_role'] ?? 'user';
    echo json_encode(['success' => true, 'role' => $_SESSION['user_role'], 'name' => $_SESSION['user_name']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
}

exit();
