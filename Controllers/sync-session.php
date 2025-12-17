<?php
session_start();
header('Content-Type: application/json');

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['id']) && isset($input['role'])) {
    $_SESSION['user_id'] = $input['id'];
    $_SESSION['user_role'] = $input['role'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Session synced'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data'
    ]);
}
exit;
?>