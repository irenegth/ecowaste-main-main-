<?php
session_start();

// Include database configuration
require_once '../../config/db.php';

// Use Database class
$database = Database::getInstance();
$conn = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend-ecowaste/Auth,login,signup/login.php");
    exit();
}

// Get current user data
$userId = $_SESSION['user_id'];
$userSql = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$currentUser = $userResult->fetch_assoc();

if (!$currentUser) {
    session_destroy();
    header("Location: ../../frontend-ecowaste/Auth,login,signup/login.php");
    exit();
}

// Get first name from fullname
$fullnameParts = explode(' ', $currentUser['fullname']);
$firstname = $fullnameParts[0] ?? 'User';

// Check if user is admin for different menu
$isAdmin = ($currentUser['role'] === 'admin');

// Sample recycling centers data (in real app, get from database)
$recyclingCenters = [ /* ... original content truncated in backup for brevity ... */ ];

?>
<?php
// Backup of admin recycling-centers.php
?>
<!doctype html><html><body><p>Backup: admin recycling-centers.php</p></body></html>
