<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database config
require_once __DIR__ . '/../Config/db.php';

// Get database connection
$database = Database::getInstance();
$conn = $database->getConnection();

$response = ['success' => false, 'message' => ''];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method';
        echo json_encode($response);
        exit;
    }

    // Get POST data
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    // Validate required fields
    if (empty($fullname) || empty($email) || empty($password) || empty($contact) || empty($address)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
        echo json_encode($response);
        exit;
    }

    // Validate password length
    if (strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters';
        echo json_encode($response);
        exit;
    }

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        $response['message'] = 'Email already registered. Please use a different email or login.';
        echo json_encode($response);
        exit;
    }
    $checkEmail->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Default values
    $status = 'pending';
    $role = 'user';

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, contact, address, status, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $fullname, $email, $hashedPassword, $contact, $address, $status, $role);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Registration successful! Please wait for admin approval before you can login.';
        
        // Also create user settings entry
        $userId = $conn->insert_id;
        $settingsStmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
        $settingsStmt->bind_param("i", $userId);
        $settingsStmt->execute();
        $settingsStmt->close();
        
        // Create notification for admin
        $adminNotification = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (1, 'New User Registration', 'New user registered: $fullname ($email). Please review for approval.', 'info')
        ");
        $adminNotification->execute();
        $adminNotification->close();
        
    } else {
        $response['message'] = 'Registration failed. Please try again. Error: ' . $stmt->error;
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("Registration Error: " . $e->getMessage());
}

echo json_encode($response);
?>