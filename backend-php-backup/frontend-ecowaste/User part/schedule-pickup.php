<?php
// Backup: schedule-pickup.php
?>
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Auth,login,signup/login.php");
    exit();
}

// Include database configuration
require_once __DIR__ . '/../../config/db.php';

// Use your Database class
$database = Database::getInstance();
$conn = $database->getConnection();

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
    header("Location: ../Auth,login,signup/login.php");
    exit();
}

// Get first name from fullname
$fullnameParts = explode(' ', $currentUser['fullname']);
$firstname = $fullnameParts[0] ?? 'User';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_pickup'])) {
    $wasteType = $_POST['waste_type'];
    $pickupDate = $_POST['pickup_date'];
    $pickupTime = $_POST['pickup_time'];
    $weight = $_POST['weight'] ?? null;
    $specialInstructions = $_POST['special_instructions'] ?? '';
    
    // Validate inputs
    if (empty($wasteType) || empty($pickupDate) || empty($pickupTime)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert into request_pickup table
        $sql = "INSERT INTO request_pickup (
                    user_id, 
                    pickup_date, 
                    pickup_time, 
                    waste_type, 
                    waste_weight,
                    address,
                    contact_person,
                    contact_number,
                    special_instructions,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssdssss", 
            $userId, 
            $pickupDate, 
            $pickupTime, 
            $wasteType, 
            $weight,
            $currentUser['address'],
            $currentUser['fullname'],
            $currentUser['contact'],
            $specialInstructions
        );
        
        if ($stmt->execute()) {
            $success_message = "Pickup scheduled successfully! Your request has been submitted for approval.";
            
            // Create notification for admin
            $pickupId = $stmt->insert_id;
            $notificationSql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
                                VALUES (1, 'New Pickup Request', 
                                'New pickup request #$pickupId from " . $currentUser['fullname'] . ".', 
                                'info', ?, 'pickup')";
            $notifStmt = $conn->prepare($notificationSql);
            $notifStmt->bind_param("i", $pickupId);
            $notifStmt->execute();
            
        } else {
            $error_message = "Error scheduling pickup. Please try again.";
        }
    }
}

// Handle cancellation
if (isset($_GET['cancel'])) {
    $pickupId = intval($_GET['cancel']);
    
    // Check if pickup belongs to user
    $checkSql = "SELECT status FROM request_pickup WHERE id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $pickupId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $pickupData = $checkResult->fetch_assoc();
    
    if ($pickupData) {
        // Only allow cancellation if not completed
        if ($pickupData['status'] !== 'completed') {
            $updateSql = "UPDATE request_pickup SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $pickupId);
            
            if ($updateStmt->execute()) {
                $success_message = "Pickup cancelled successfully.";
            } else {
                $error_message = "Error cancelling pickup.";
            }
        } else {
            $error_message = "Cannot cancel a completed pickup.";
        }
    }
}

// Get user's scheduled pickups
$pickupsSql = "SELECT 
                    id,
                    pickup_date,
                    pickup_time,
                    waste_type,
                    waste_weight,
                    special_instructions,
                    status,
                    created_at
                FROM request_pickup 
                WHERE user_id = ? 
                AND status IN ('pending', 'scheduled', 'in_progress')
                ORDER BY pickup_date ASC, pickup_time ASC";
$pickupsStmt = $conn->prepare($pickupsSql);
$pickupsStmt->bind_param("i", $userId);
$pickupsStmt->execute();
$pickupsResult = $pickupsStmt->get_result();
$scheduledPickups = [];

while ($row = $pickupsResult->fetch_assoc()) {
    $scheduledPickups[] = $row;
}

// Get waste types from database enum values
$wasteTypes = ['plastic', 'paper', 'metal', 'glass', 'electronic', 'organic', 'other'];

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: ../Auth,login,signup/login.php");
    exit();
}
?>
