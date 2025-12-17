<?php
// Backup: request-pickup.php
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

// Handle form submission for special pickup request
$success_message = '';
$error_message = '';

// Define special waste categories
$specialCategories = [
    'electronics' => 'Electronics (E-Waste)',
    'hazardous' => 'Hazardous Waste',
    'bulk' => 'Bulk Items',
    'construction' => 'Construction Debris',
    'medical' => 'Medical Waste'
];

// Weight ranges
$weightRanges = [
    '1-10' => '1-10 kg',
    '11-25' => '11-25 kg',
    '26-50' => '26-50 kg',
    '51-100' => '51-100 kg',
    '100+' => '100+ kg'
];

// Time preferences
$timePreferences = [
    '' => 'Any time',
    'morning' => 'Morning (8AM-12PM)',
    'afternoon' => 'Afternoon (1PM-5PM)',
    'evening' => 'Evening (5PM-8PM)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_pickup'])) {
    $category = $_POST['waste_category'];
    $weight = $_POST['estimated_weight'];
    $preferredDate = $_POST['preferred_date'];
    $timePreference = $_POST['time_preference'] ?? '';
    $itemDescription = $_POST['item_description'] ?? '';
    $specialInstructions = $_POST['special_instructions'] ?? '';
    
    // Validate inputs
    if (empty($category) || empty($weight) || empty($preferredDate) || empty($itemDescription)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if date is not in the past
        $selectedDate = new DateTime($preferredDate);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selectedDate < $today) {
            $error_message = "Preferred date cannot be in the past.";
        } else {
            // Insert into request_pickup table with special waste type
            $sql = "INSERT INTO request_pickup (
                        user_id, 
                        pickup_date, 
                        pickup_time, 
                        waste_type, 
                        special_instructions,
                        address,
                        contact_person,
                        contact_number,
                        status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            // Convert time preference to actual time range
            $timeSlot = '';
            switch ($timePreference) {
                case 'morning':
                    $timeSlot = '8:00-12:00';
                    break;
                case 'afternoon':
                    $timeSlot = '13:00-17:00';
                    break;
                case 'evening':
                    $timeSlot = '17:00-20:00';
                    break;
                default:
                    $timeSlot = '9:00-17:00'; // Default time
            }
            
            // Combine item description and weight into special instructions
            $combinedInstructions = "Special Pickup Request\n";
            $combinedInstructions .= "Category: " . $specialCategories[$category] . "\n";
            $combinedInstructions .= "Estimated Weight: " . $weightRanges[$weight] . "\n";
            $combinedInstructions .= "Items: " . $itemDescription . "\n";
            if (!empty($specialInstructions)) {
                $combinedInstructions .= "Special Instructions: " . $specialInstructions;
            }
            
            // For special pickup, use 'other' as waste_type or create specific type
            $wasteType = 'other'; // or create a new type like 'special'
            if ($category == 'electronics') {
                $wasteType = 'electronic';
            } elseif ($category == 'medical') {
                $wasteType = 'other'; // Keep as other for medical
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isssssss", 
                $userId, 
                $preferredDate, 
                $timeSlot, 
                $wasteType, 
                $combinedInstructions,
                $currentUser['address'],
                $currentUser['fullname'],
                $currentUser['contact']
            );
            
            if ($stmt->execute()) {
                $requestId = $stmt->insert_id;
                $success_message = "Special pickup request submitted successfully! Request ID: #$requestId";
                
                // Create notification for admin
                $notificationSql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
                                    VALUES (1, 'Special Pickup Request', 
                                    'Special pickup request #$requestId from " . $currentUser['fullname'] . " (" . $specialCategories[$category] . ").', 
                                    'warning', ?, 'special_pickup')";
                $notifStmt = $conn->prepare($notificationSql);
                $notifStmt->bind_param("i", $requestId);
                $notifStmt->execute();
                
            } else {
                $error_message = "Error submitting request. Please try again.";
            }
        }
    }
}

// Get user's recent special pickup requests
$requestsSql = "SELECT 
                    id,
                    pickup_date,
                    pickup_time,
                    waste_type,
                    special_instructions,
                    status,
                    created_at
                FROM request_pickup 
                WHERE user_id = ? 
                AND (waste_type = 'other' OR waste_type = 'electronic')
                AND special_instructions LIKE '%Special Pickup Request%'
                ORDER BY created_at DESC 
                LIMIT 5";
$requestsStmt = $conn->prepare($requestsSql);
$requestsStmt->bind_param("i", $userId);
$requestsStmt->execute();
$requestsResult = $requestsStmt->get_result();
$recentRequests = [];

while ($row = $requestsResult->fetch_assoc()) {
    // Parse special instructions to extract category and weight
    $instructions = $row['special_instructions'];
    $category = 'Unknown';
    $weight = 'N/A';
    $items = '';
    
    if (preg_match('/Category:\s*(.+)/', $instructions, $matches)) {
        $category = trim($matches[1]);
    }
    if (preg_match('/Estimated Weight:\s*(.+)/', $instructions, $matches)) {
        $weight = trim($matches[1]);
    }
    if (preg_match('/Items:\s*(.+?)(?:\n|$)/', $instructions, $matches)) {
        $items = trim($matches[1]);
    }
    
    $row['category'] = $category;
    $row['weight'] = $weight;
    $row['items'] = $items;
    $recentRequests[] = $row;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: ../Auth,login,signup/login.php");
    exit();
}
?>
