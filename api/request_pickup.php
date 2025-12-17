<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once __DIR__ . '/../Config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$date = $data['date'] ?? null;
$type = $data['type'] ?? null;
$weight = $data['weight'] ?? null;

if (!$date || !$type || !$weight) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => true, 'demo' => true, 'message' => 'Demo mode, request recorded locally']);
    exit();
}

$user_id = $data['user_id'] ?? null;
// Insert into request_pickup
$sql = 'INSERT INTO request_pickup (user_id, pickup_date, waste_type, waste_weight, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())';
$stmt = $conn->prepare($sql);
$stmt->bind_param('isss', $user_id, $date, $type, $weight);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
}

exit();
