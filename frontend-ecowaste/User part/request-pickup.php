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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Pickup - EcoWaste Management</title>
    <link rel="stylesheet" href="all.css">
    <style>
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-scheduled {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-in_progress {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #00A651;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .request-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #00A651;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .request-title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            font-weight: 500;
            color: #666;
            min-width: 100px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .items-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        
        .items-column {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .items-column.accepted {
            border-top: 4px solid #00A651;
        }
        
        .items-column.not-accepted {
            border-top: 4px solid #dc3545;
        }
        
        .items-column h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .items-column ul {
            padding-left: 20px;
            margin: 0;
        }
        
        .items-column li {
            margin-bottom: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo" onclick="window.location.href='dashboard.php'">
            <img class="logo-icon" src="https://img.icons8.com/?size=100&id=seuCyneMNgp6&format=png&color=000000" alt="EcoWaste Logo">
            <div class="logo-text">
                <div class="logo-title">EcoWaste</div>
                <div class="logo-subtitle">Management</div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-item" onclick="window.location.href='dashboard.php'">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='schedule-pickup.php'">
                <span class="nav-icon">📅</span>
                <span>Schedule Pickup</span>
            </div>
            
            <div class="nav-item active">
                <span class="nav-icon">📋</span>
                <span>Request Pickup</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='recycling-centers.php'">
                <span class="nav-icon">📍</span>
                <span>Recycling Centers</span>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="nav-item" onclick="simpleLogout()">
                <span class="nav-icon">🚪</span>
                <span>Log out</span>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="user-section" style="margin-left: auto;">
                <div class="user-info" style="cursor: pointer;" onclick="window.location.href='profile&settings.php'">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($firstname, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['fullname']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <!-- Display messages -->
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1 class="page-title">Request Special Pickup</h1>
                <p class="page-subtitle">Request pickup for special waste items</p>
            </div>

            <div class="rp-form-card">
                <h2 class="rp-form-title">New Pickup Request</h2>
                <p class="rp-form-subtitle">Fill in details for your special waste pickup</p>

                <form method="POST" action="">
                    <div class="rp-form-row">
                        <div class="rp-form-group">
                            <label class="rp-label">Waste Category <span class="required">*</span></label>
                            <select id="wasteCategory" name="waste_category" required>
                                <option value="">Select category</option>
                                <?php foreach ($specialCategories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>">
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="rp-form-group">
                            <label class="rp-label">Estimated Weight <span class="required">*</span></label>
                            <select id="estimatedWeight" name="estimated_weight" required>
                                <option value="">Select weight range</option>
                                <?php foreach ($weightRanges as $value => $label): ?>
                                    <option value="<?php echo $value; ?>">
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="rp-form-row">
                        <div class="rp-form-group">
                            <label class="rp-label">Preferred Date <span class="required">*</span></label>
                            <input type="date" id="preferredDate" name="preferred_date" required>
                        </div>

                        <div class="rp-form-group">
                            <label class="rp-label">Time Preference</label>
                            <select id="timePreference" name="time_preference">
                                <?php foreach ($timePreferences as $value => $label): ?>
                                    <option value="<?php echo $value; ?>">
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="rp-form-group full-width">
                        <label class="rp-label">Item Description <span class="required">*</span></label>
                        <textarea id="itemDescription" name="item_description" 
                                  placeholder="Describe the items to be picked up (e.g., 2 old computers, 1 TV, 3 monitors)..." 
                                  rows="3" required></textarea>
                    </div>

                    <div class="rp-form-group full-width">
                        <label class="rp-label">Special Instructions</label>
                        <textarea id="specialInstructions" name="special_instructions" 
                                  placeholder="Any special handling requirements, access instructions, or safety concerns..." 
                                  rows="2"></textarea>
                    </div>

                    <input type="hidden" name="request_pickup" value="1">
                    <button type="submit" class="submit-btn">Submit Request</button>
                </form>
            </div>

            <div class="requests-section">
                <h2 class="sp-section-title">Your Recent Requests</h2>
                <p class="sp-section-subtitle">Track the status of your pickup requests</p>

                <?php if (empty($recentRequests)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <p style="font-size: 18px;">No special pickup requests found.</p>
                        <p>Submit your first special pickup request using the form above!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentRequests as $request): ?>
                        <div class="request-item">
                            <div class="request-header">
                                <div class="request-title">
                                    <?php 
                                    // Extract short title from category
                                    $title = explode('(', $request['category'])[0];
                                    echo trim($title) . ' Collection';
                                    ?>
                                </div>
                                <span class="status-badge status-<?php echo str_replace('_', '', $request['status']); ?>">
                                    <?php 
                                    $statusText = ucwords(str_replace('_', ' ', $request['status']));
                                    // Map status to more user-friendly text
                                    $statusMap = [
                                        'pending' => 'Pending Review',
                                        'scheduled' => 'Scheduled',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled'
                                    ];
                                    echo $statusMap[$request['status']] ?? $statusText;
                                    ?>
                                </span>
                            </div>
                            <div class="request-details">
                                <div class="detail-row">
                                    <span class="detail-label">Category:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['category']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Weight:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['weight']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Date:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($request['pickup_date'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Time:</span>
                                    <span class="detail-value"><?php echo str_replace('-', ' - ', $request['pickup_time']); ?></span>
                                </div>
                                <?php if (!empty($request['items'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Items:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars(substr($request['items'], 0, 100)); ?>...</span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row">
                                    <span class="detail-label">Request ID:</span>
                                    <span class="detail-value">#<?php echo $request['id']; ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Requested:</span>
                                    <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="acceptable-items">
                <h2 class="sp-section-title">Acceptable Items for Special Pickup</h2>
                <div class="items-grid">
                    <div class="items-column accepted">
                        <h3>✅ Accepted Items</h3>
                        <ul>
                            <li>Old computers & laptops</li>
                            <li>Mobile phones & tablets</li>
                            <li>Televisions & monitors</li>
                            <li>Batteries (all types)</li>
                            <li>Light bulbs & tubes</li>
                            <li>Paint & chemicals (sealed)</li>
                            <li>Old furniture & appliances</li>
                            <li>Mattresses & bulky items</li>
                        </ul>
                    </div>
                    <div class="items-column not-accepted">
                        <h3>❌ Not Accepted</h3>
                        <ul>
                            <li>Explosive materials</li>
                            <li>Radioactive waste</li>
                            <li>Medical sharps (needles)</li>
                            <li>Biological waste</li>
                            <li>Asbestos materials</li>
                            <li>Large vehicle parts</li>
                            <li>Untreated sewage</li>
                            <li>Illegal substances</li>
                        </ul>
                    </div>
                </div>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    <strong>Note:</strong> For hazardous materials, please ensure they are properly sealed and labeled. 
                    Additional fees may apply for certain items. Contact us for specific questions about your items.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('preferredDate').setAttribute('min', today);
        
        // Set default date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        document.getElementById('preferredDate').value = tomorrowStr;

        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '?action=logout';
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const category = document.getElementById('wasteCategory').value;
            const weight = document.getElementById('estimatedWeight').value;
            const date = document.getElementById('preferredDate').value;
            const description = document.getElementById('itemDescription').value;
            
            if (!category || !weight || !date || !description) {
                alert('Please fill in all required fields.');
                e.preventDefault();
                return false;
            }
            
            // Check if date is in the past
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Preferred date cannot be in the past.');
                e.preventDefault();
                return false;
            }
            
            // Check description length
            if (description.length < 10) {
                alert('Please provide a more detailed item description (at least 10 characters).');
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Show confirmation for hazardous waste
        document.getElementById('wasteCategory').addEventListener('change', function() {
            if (this.value === 'hazardous' || this.value === 'medical') {
                alert('Important: Please ensure hazardous/medical waste is properly sealed and labeled. Special handling requirements apply.');
            }
        });
    </script>
</body>
</html>