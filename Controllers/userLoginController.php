<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// IMPORTANT: Set headers BEFORE any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log for debugging
error_log("=== LOGIN ATTEMPT ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']
        ]);
        exit;
    }

    // Get POST data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    error_log("Email: $email");
    error_log("Password received: " . (!empty($password) ? 'YES' : 'NO'));

    // Validate
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Email and password are required'
        ]);
        exit;
    }

    // Include database config
    require_once __DIR__ . '/../Config/db.php';
    
    // Get database connection
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    error_log("Database connected: " . ($conn ? 'YES' : 'NO'));
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("User found: " . $result->num_rows);
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'User not found. Please check your email or register.'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    error_log("User status: " . $user['status']);
    error_log("User role: " . $user['role']);
    
    // Check account status
    if ($user['status'] !== 'approved') {
        $statusMessage = ucfirst($user['status']);
        echo json_encode([
            'success' => false, 
            'message' => "Account is {$statusMessage}. Please contact administrator."
        ]);
        exit;
    }
    
    // Verify password
    error_log("Stored hash: " . substr($user['password'], 0, 20) . "...");
    error_log("Password verify result: " . (password_verify($password, $user['password']) ? 'TRUE' : 'FALSE'));
    
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Incorrect password. Please try again.'
        ]);
        exit;
    }
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Store session in database
    $sessionStmt = $conn->prepare("
        INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $sessionStmt->bind_param("isss", $user['id'], $token, $ip, $agent);
    $sessionStmt->execute();
    $sessionStmt->close();
    
    // Remove sensitive data
    unset($user['password']);
    unset($user['reset_token']);
    unset($user['reset_expiry']);
    
    // Determine redirect URL based on role
    $redirect_url = '';
    if ($user['role'] === 'admin') {
        $redirect_url = '../frontend-ecowaste/admin-part/dashboard.php';
    } else {
        $redirect_url = '../frontend-ecowaste/User part/dashboard.php';
    }
    
    // Successful login response
    $response = [
        'success' => true,
        'message' => 'Login successful!',
        'user' => $user,
        'token' => $token,
        'redirect_url' => $redirect_url
    ];
    
    error_log("Login successful for: " . $user['email']);
    error_log("Redirecting to: " . $redirect_url);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error_details' => 'Check PHP error log for details'
    ]);
}

// Make sure no extra output
exit;
?>