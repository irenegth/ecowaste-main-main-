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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Pickup - EcoWaste Management</title>
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
        
        .pickup-badge {
            padding: 4px 8px;
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
        
        .cancel-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .cancel-btn:hover {
            background: #c82333;
        }
        
        .pickup-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .bio {
            background: #d4f4dd;
            color: #00A651;
        }
        
        .nonbio {
            background: #dbe9ff;
            color: #4285F4;
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
            
            <div class="nav-item active">
                <span class="nav-icon">📅</span>
                <span>Schedule Pickup</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='request-pickup.php'">
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
                <h1 class="page-title">Schedule Waste Collection</h1>
                <p class="page-subtitle">Plan your waste pickups</p>
            </div>

            <div class="info-box">
                <div class="info-icon">ℹ️</div>
                <p>Schedule your waste collection. Pickups are typically processed within 24-48 hours.</p>
            </div>

            <div class="form-card">
                <h2 class="form-title">New Pickup Schedule</h2>
                <p class="form-subtitle">Choose your waste type and preferred time</p>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="sp-label">Waste Type <span class="required">*</span></label>
                            <select id="wasteType" name="waste_type" required>
                                <option value="">Select waste type</option>
                                <?php foreach ($wasteTypes as $type): ?>
                                    <option value="<?php echo $type; ?>">
                                        <?php echo ucfirst($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="sp-label">Pickup Date <span class="required">*</span></label>
                            <input type="date" id="pickupDate" name="pickup_date" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="sp-label">Time Slot <span class="required">*</span></label>
                            <select id="pickupTime" name="pickup_time" required>
                                <option value="">Select time slot</option>
                                <option value="8:00-10:00">8:00 AM - 10:00 AM</option>
                                <option value="10:00-12:00">10:00 AM - 12:00 PM</option>
                                <option value="13:00-15:00">1:00 PM - 3:00 PM</option>
                                <option value="15:00-17:00">3:00 PM - 5:00 PM</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="sp-label">Estimated Weight (kg)</label>
                            <input type="number" id="weight" name="weight" min="0" step="0.1" placeholder="e.g., 5.5">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="sp-label">Special Instructions (Optional)</label>
                        <textarea id="special_instructions" name="special_instructions" 
                                  placeholder="e.g., Please call before arrival, Location is at back gate..."></textarea>
                    </div>

                    <input type="hidden" name="schedule_pickup" value="1">
                    <button type="submit" class="schedule-btn">Schedule Pickup</button>
                </form>
            </div>

            <div class="scheduled-section">
                <h2 class="section-title">Your Scheduled Pickups</h2>
                <p class="section-subtitle">Manage your upcoming waste collections</p>

                <?php if (empty($scheduledPickups)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <p style="font-size: 18px;">No scheduled pickups found.</p>
                        <p>Schedule your first pickup using the form above!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scheduledPickups as $pickup): ?>
                        <div class="pickup-item">
                            <div class="pickup-icon <?php echo $pickup['waste_type'] == 'organic' ? 'bio' : 'nonbio'; ?>">
                                <?php 
                                $icons = [
                                    'plastic' => '🥤',
                                    'paper' => '📄',
                                    'metal' => '🔩',
                                    'glass' => '🥛',
                                    'electronic' => '💻',
                                    'organic' => '🍃',
                                    'other' => '🗑️'
                                ];
                                echo $icons[$pickup['waste_type']] ?? '🗑️';
                                ?>
                            </div>
                            <div class="pickup-details">
                                <div class="pickup-type">
                                    <?php echo ucfirst($pickup['waste_type']); ?>
                                    <span class="pickup-badge status-<?php echo str_replace('_', '', $pickup['status']); ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $pickup['status'])); ?>
                                    </span>
                                </div>
                                <div class="pickup-time">
                                    <?php 
                                    echo date('M d, Y', strtotime($pickup['pickup_date'])) . ' • ' . 
                                         str_replace('-', ' - ', $pickup['pickup_time']);
                                    ?>
                                </div>
                                <?php if ($pickup['waste_weight']): ?>
                                    <div class="pickup-weight">
                                        Weight: <?php echo number_format($pickup['waste_weight'], 2); ?> kg
                                    </div>
                                <?php endif; ?>
                                <?php if ($pickup['special_instructions']): ?>
                                    <div class="pickup-notes"><?php echo htmlspecialchars($pickup['special_instructions']); ?></div>
                                <?php endif; ?>
                                <div class="pickup-date">
                                    Requested: <?php echo date('M d, Y h:i A', strtotime($pickup['created_at'])); ?>
                                </div>
                            </div>
                            <?php if ($pickup['status'] !== 'completed' && $pickup['status'] !== 'cancelled'): ?>
                                <button class="cancel-btn" 
                                        onclick="cancelPickup(<?php echo $pickup['id']; ?>)">
                                    Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="guidelines-card">
                <h3 class="guidelines-title">Scheduling Guidelines</h3>
                <ul>
                    <li>Select the appropriate waste type for proper handling</li>
                    <li>Biodegradable (organic) waste includes food scraps, garden waste</li>
                    <li>Non-biodegradable waste includes plastics, metals, glass, and paper</li>
                    <li>Electronic waste must be scheduled separately</li>
                    <li>Cancellations can be made up to 12 hours before scheduled pickup</li>
                    <li>Please separate your waste before the scheduled pickup time</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('pickupDate').setAttribute('min', today);
        
        // Set default date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        document.getElementById('pickupDate').value = tomorrowStr;

        function cancelPickup(pickupId) {
            if (confirm('Are you sure you want to cancel this pickup?\n\nNote: Cancellations should be made at least 12 hours in advance.')) {
                window.location.href = '?cancel=' + pickupId;
            }
        }

        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '?action=logout';
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const wasteType = document.getElementById('wasteType').value;
            const pickupDate = document.getElementById('pickupDate').value;
            const pickupTime = document.getElementById('pickupTime').value;
            
            if (!wasteType || !pickupDate || !pickupTime) {
                alert('Please fill in all required fields.');
                e.preventDefault();
                return false;
            }
            
            // Check if date is in the past
            const selectedDate = new Date(pickupDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Pickup date cannot be in the past.');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>