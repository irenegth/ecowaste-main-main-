<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once __DIR__ . '/../Config/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['demo' => true, 'centers' => [ ['id'=>1,'name'=>'Center A','address'=>'123 Main St'], ['id'=>2,'name'=>'Center B','address'=>'456 Market St'] ]]);
    exit();
}

$res = $conn->query('SELECT id, name, address FROM recycling_centers');
$centers = [];
while ($row = $res->fetch_assoc()) { $centers[] = $row; }
echo json_encode(['centers' => $centers]);
exit();
