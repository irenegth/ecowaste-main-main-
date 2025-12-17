<?php
session_start();

// Include database configuration
require_once '../../config/db.php';

// Use Database class
$database = Database::getInstance();
$conn = $database->getConnection();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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

if (!$currentUser || $currentUser['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../frontend-ecowaste/Auth,login,signup/login.php");
    exit();
}

// Get first name from fullname
$fullnameParts = explode(' ', $currentUser['fullname']);
$firstname = $fullnameParts[0] ?? 'Admin';

// Handle form actions
$success_message = '';
$error_message = '';

// Handle user approval/rejection
if (isset($_GET['approve_user'])) {
    $targetUserId = intval($_GET['approve_user']);
    $updateSql = "UPDATE users SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $userId, $targetUserId);
    
    if ($stmt->execute()) {
        $success_message = "User approved successfully!";
        
        // Create notification for the user
        $notificationSql = "INSERT INTO notifications (user_id, title, message, type) 
                            VALUES (?, 'Account Approved', 'Your account has been approved. You can now access all features.', 'success')";
        $notifStmt = $conn->prepare($notificationSql);
        $notifStmt->bind_param("i", $targetUserId);
        $notifStmt->execute();
    } else {
        $error_message = "Error approving user.";
    }
}

// Handle user rejection
if (isset($_GET['reject_user'])) {
    $targetUserId = intval($_GET['reject_user']);
    $updateSql = "UPDATE users SET status = 'rejected', approved_by = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $userId, $targetUserId);
    
    if ($stmt->execute()) {
        $success_message = "User rejected successfully!";
        
        // Create notification for the user
        $notificationSql = "INSERT INTO notifications (user_id, title, message, type) 
                            VALUES (?, 'Account Rejected', 'Your account registration has been rejected. Please contact support for more information.', 'error')";
        $notifStmt = $conn->prepare($notificationSql);
        $notifStmt->bind_param("i", $targetUserId);
        $notifStmt->execute();
    } else {
        $error_message = "Error rejecting user.";
    }
}

// Handle user suspension
if (isset($_GET['suspend_user'])) {
    $targetUserId = intval($_GET['suspend_user']);
    $updateSql = "UPDATE users SET status = 'suspended', approved_by = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $userId, $targetUserId);
    
    if ($stmt->execute()) {
        $success_message = "User suspended successfully!";
    } else {
        $error_message = "Error suspending user.";
    }
}

// Handle user activation
if (isset($_GET['activate_user'])) {
    $targetUserId = intval($_GET['activate_user']);
    $updateSql = "UPDATE users SET status = 'approved', approved_by = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $userId, $targetUserId);
    
    if ($stmt->execute()) {
        $success_message = "User activated successfully!";
    } else {
        $error_message = "Error activating user.";
    }
}

// Handle pickup request approval
if (isset($_GET['approve_pickup'])) {
    $pickupId = intval($_GET['approve_pickup']);
    $updateSql = "UPDATE request_pickup SET status = 'scheduled' WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("i", $pickupId);
    
    if ($stmt->execute()) {
        $success_message = "Pickup request approved and scheduled!";
        
        // Get user info for notification
        $userSql = "SELECT user_id FROM request_pickup WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("i", $pickupId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $pickupUser = $userResult->fetch_assoc();
        
        if ($pickupUser) {
            $notificationSql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
                                VALUES (?, 'Pickup Scheduled', 'Your pickup request #$pickupId has been scheduled.', 'success', ?, 'pickup')";
            $notifStmt = $conn->prepare($notificationSql);
            $notifStmt->bind_param("ii", $pickupUser['user_id'], $pickupId);
            $notifStmt->execute();
        }
    } else {
        $error_message = "Error approving pickup request.";
    }
}

// Handle pickup request rejection
if (isset($_GET['reject_pickup'])) {
    $pickupId = intval($_GET['reject_pickup']);
    $updateSql = "UPDATE request_pickup SET status = 'cancelled' WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("i", $pickupId);
    
    if ($stmt->execute()) {
        $success_message = "Pickup request rejected!";
        
        // Get user info for notification
        $userSql = "SELECT user_id FROM request_pickup WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("i", $pickupId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $pickupUser = $userResult->fetch_assoc();
        
        if ($pickupUser) {
            $notificationSql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
                                VALUES (?, 'Pickup Rejected', 'Your pickup request #$pickupId has been rejected.', 'error', ?, 'pickup')";
            $notifStmt = $conn->prepare($notificationSql);
            $notifStmt->bind_param("ii", $pickupUser['user_id'], $pickupId);
            $notifStmt->execute();
        }
    } else {
        $error_message = "Error rejecting pickup request.";
    }
}

// Handle pickup completion
if (isset($_GET['complete_pickup'])) {
    $pickupId = intval($_GET['complete_pickup']);
    $updateSql = "UPDATE request_pickup SET status = 'completed', completed_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("i", $pickupId);
    
    if ($stmt->execute()) {
        $success_message = "Pickup marked as completed!";
        
        // Get user info and weight for waste statistics
        $pickupSql = "SELECT user_id, waste_type, waste_weight FROM request_pickup WHERE id = ?";
        $pickupStmt = $conn->prepare($pickupSql);
        $pickupStmt->bind_param("i", $pickupId);
        $pickupStmt->execute();
        $pickupResult = $pickupStmt->get_result();
        $pickupData = $pickupResult->fetch_assoc();
        
        if ($pickupData && $pickupData['waste_weight']) {
            // Update waste statistics
            $currentYear = date('Y');
            $currentMonth = date('m');
            
            // Check if stats exist for this month
            $checkSql = "SELECT id FROM waste_statistics WHERE user_id = ? AND year = ? AND month = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("iii", $pickupData['user_id'], $currentYear, $currentMonth);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing stats
                $updateStatsSql = "UPDATE waste_statistics SET 
                                    " . $pickupData['waste_type'] . "_weight = " . $pickupData['waste_type'] . "_weight + ?,
                                    total_weight = total_weight + ?
                                  WHERE user_id = ? AND year = ? AND month = ?";
                $updateStatsStmt = $conn->prepare($updateStatsSql);
                $updateStatsStmt->bind_param("ddiii", $pickupData['waste_weight'], $pickupData['waste_weight'], 
                                             $pickupData['user_id'], $currentYear, $currentMonth);
                $updateStatsStmt->execute();
            } else {
                // Insert new stats
                $column = $pickupData['waste_type'] . "_weight";
                $insertStatsSql = "INSERT INTO waste_statistics 
                                  (user_id, year, month, $column, total_weight) 
                                  VALUES (?, ?, ?, ?, ?)";
                $insertStatsStmt = $conn->prepare($insertStatsSql);
                $insertStatsStmt->bind_param("iiiid", $pickupData['user_id'], $currentYear, $currentMonth,
                                             $pickupData['waste_weight'], $pickupData['waste_weight']);
                $insertStatsStmt->execute();
            }
            
            // Create notification for user
            $notificationSql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
                                VALUES (?, 'Pickup Completed', 'Your pickup request #$pickupId has been completed. ' || ? || ' kg of ' || ? || ' waste collected.', 'success', ?, 'pickup')";
            $notifStmt = $conn->prepare($notificationSql);
            $notifStmt->bind_param("idsi", $pickupData['user_id'], $pickupData['waste_weight'], $pickupData['waste_type'], $pickupId);
            $notifStmt->execute();
        }
    } else {
        $error_message = "Error completing pickup.";
    }
}

// Handle user search
$searchQuery = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = $conn->real_escape_string($_GET['search']);
}

// Get admin statistics
$stats = [];

// Pending accounts
$sql = "SELECT COUNT(*) as count FROM users WHERE status = 'pending'";
$result = $conn->query($sql);
$stats['pending_accounts'] = $result->fetch_assoc()['count'];

// Pending pickup requests
$sql = "SELECT COUNT(*) as count FROM request_pickup WHERE status = 'pending'";
$result = $conn->query($sql);
$stats['pending_requests'] = $result->fetch_assoc()['count'];

// Scheduled pickups
$sql = "SELECT COUNT(*) as count FROM request_pickup WHERE status = 'scheduled'";
$result = $conn->query($sql);
$stats['scheduled'] = $result->fetch_assoc()['count'];

// Active users
$sql = "SELECT COUNT(*) as count FROM users WHERE status = 'approved'";
$result = $conn->query($sql);
$stats['active_users'] = $result->fetch_assoc()['count'];

// Get pending users for display
$pendingUsersSql = "SELECT id, fullname, email, contact, address, created_at 
                   FROM users WHERE status = 'pending' 
                   ORDER BY created_at DESC";
$pendingUsersResult = $conn->query($pendingUsersSql);
$pendingUsers = [];
while ($row = $pendingUsersResult->fetch_assoc()) {
    $pendingUsers[] = $row;
}

// Get pending pickup requests
$pendingPickupsSql = "SELECT 
                        rp.id,
                        rp.pickup_date,
                        rp.pickup_time,
                        rp.waste_type,
                        rp.waste_weight,
                        rp.special_instructions,
                        u.fullname as user_name,
                        u.email as user_email,
                        rp.created_at
                      FROM request_pickup rp
                      JOIN users u ON rp.user_id = u.id
                      WHERE rp.status = 'pending'
                      ORDER BY rp.created_at DESC";
$pendingPickupsResult = $conn->query($pendingPickupsSql);
$pendingPickups = [];
while ($row = $pendingPickupsResult->fetch_assoc()) {
    $pendingPickups[] = $row;
}

// Get scheduled pickups
$scheduledPickupsSql = "SELECT 
                          rp.id,
                          rp.pickup_date,
                          rp.pickup_time,
                          rp.waste_type,
                          rp.waste_weight,
                          rp.special_instructions,
                          u.fullname as user_name,
                          u.email as user_email,
                          u.contact as user_contact,
                          rp.address,
                          rp.created_at
                        FROM request_pickup rp
                        JOIN users u ON rp.user_id = u.id
                        WHERE rp.status = 'scheduled'
                        ORDER BY rp.pickup_date ASC, rp.pickup_time ASC";
$scheduledPickupsResult = $conn->query($scheduledPickupsSql);
$scheduledPickups = [];
while ($row = $scheduledPickupsResult->fetch_assoc()) {
    $scheduledPickups[] = $row;
}

// Get all users for management
$usersSql = "SELECT 
                id,
                fullname,
                email,
                contact,
                address,
                status,
                role,
                created_at,
                last_login
             FROM users 
             WHERE role != 'admin' 
             ORDER BY created_at DESC";
$usersResult = $conn->query($usersSql);
$allUsers = [];
while ($row = $usersResult->fetch_assoc()) {
    $allUsers[] = $row;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Clear user session from database
    $sessionSql = "DELETE FROM user_sessions WHERE user_id = ?";
    $sessionStmt = $conn->prepare($sessionSql);
    $sessionStmt->bind_param("i", $userId);
    $sessionStmt->execute();
    
    session_destroy();
    header("Location: ../../frontend-ecowaste/Auth,login,signup/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - EcoWaste Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
        
        .admin-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .admin-subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        
        .search-section {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background: #f8f9fa;
        }
        
        .tab.active {
            background: #00A651;
            color: white;
            border-color: #00A651;
        }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            min-height: 400px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-text {
            font-size: 16px;
            color: #666;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
            background: #f8f9fa;
        }
        
        .user-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        
        .user-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-btn.approve {
            background: #00A651;
            color: white;
        }
        
        .action-btn.approve:hover {
            background: #008f45;
        }
        
        .action-btn.reject {
            background: #dc3545;
            color: white;
        }
        
        .action-btn.reject:hover {
            background: #c82333;
        }
        
        .action-btn.view {
            background: #6c757d;
            color: white;
        }
        
        .action-btn.view:hover {
            background: #5a6268;
        }
        
        .action-btn.suspend {
            background: #ffc107;
            color: #212529;
        }
        
        .action-btn.suspend:hover {
            background: #e0a800;
        }
        
        .action-btn.activate {
            background: #00A651;
            color: white;
        }
        
        .action-btn.activate:hover {
            background: #008f45;
        }
        
        .action-btn.complete {
            background: #17a2b8;
            color: white;
        }
        
        .action-btn.complete:hover {
            background: #138496;
        }
        
        .user-info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .user-avatar-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #00A651;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .waste-icon {
            display: inline-block;
            margin-right: 5px;
        }
        
        .waste-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo" onclick="window.location.href='admin-dashboard.php'">
            <img class="logo-icon" src="https://img.icons8.com/?size=100&id=seuCyneMNgp6&format=png&color=000000" alt="EcoWaste Logo">
            <div class="logo-text">
                <div class="logo-title">EcoWaste</div>
                <div class="logo-subtitle">Management</div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-item" onclick="window.location.href='admin-dashboard.php'">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='recycling-centers.php'">
                <span class="nav-icon">📍</span>
                <span>Recycling Centers</span>
            </div>
            
            <div class="nav-item active">
                <span class="nav-icon">👥</span>
                <span>Admin Panel</span>
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
                <div class="user-info" style="cursor: pointer;" onclick="window.location.href='profile.php'">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($firstname, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['fullname']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        <div class="user-role" style="font-size: 12px; color: #00A651;">
                            <?php echo ucfirst($currentUser['role']); ?>
                        </div>
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
                <h1 class="page-title">Admin Panel</h1>
                <p class="page-subtitle">Manage user accounts, pickup requests, and schedules</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0; color: #FF9800;">⏳</div>
                    <div class="stat-content">
                        <div class="stat-label">Pending Accounts</div>
                        <div class="stat-value"><?php echo $stats['pending_accounts']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #ffebee; color: #dc3545;">📋</div>
                    <div class="stat-content">
                        <div class="stat-label">Pending Requests</div>
                        <div class="stat-value"><?php echo $stats['pending_requests']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd; color: #2196F3;">📅</div>
                    <div class="stat-content">
                        <div class="stat-label">Scheduled</div>
                        <div class="stat-value"><?php echo $stats['scheduled']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #f3e5f5; color: #9C27B0;">👥</div>
                    <div class="stat-content">
                        <div class="stat-label">Active Users</div>
                        <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                    </div>
                </div>
            </div>

            <div class="search-section">
                <form method="GET" action="" id="searchForm">
                    <input type="text" class="search-input" name="search" placeholder="Search by user name or item type..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </form>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('pending')">Pending Accounts</button>
                <button class="tab" onclick="showTab('pickup')">Pickup Requests</button>
                <button class="tab" onclick="showTab('scheduled')">Scheduled Pickups</button>
                <button class="tab" onclick="showTab('users')">User Management</button>
            </div>

            <div class="content-card" id="content">
                <!-- Pending Accounts Tab (default) -->
                <div id="pendingTab">
                    <?php if (empty($pendingUsers)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">👤</div>
                            <div class="empty-title">No pending accounts</div>
                            <div class="empty-text">Review and approve new user registrations</div>
                        </div>
                    <?php else: ?>
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info-row">
                                                <div class="user-avatar-small">
                                                    <?php 
                                                    $nameParts = explode(' ', $user['fullname']);
                                                    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                                    echo $initials ?: 'U';
                                                    ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                                    <div style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['contact']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($user['address'], 0, 30)) . (strlen($user['address']) > 30 ? '...' : ''); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn approve" 
                                                        onclick="approveUser(<?php echo $user['id']; ?>)">
                                                    Approve
                                                </button>
                                                <button class="action-btn reject" 
                                                        onclick="rejectUser(<?php echo $user['id']; ?>)">
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Hidden tabs content will be shown via JavaScript -->
                <div id="pickupTab" style="display: none;"></div>
                <div id="scheduledTab" style="display: none;"></div>
                <div id="usersTab" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        // Function to show tab content
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('[id$="Tab"]').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + 'Tab').style.display = 'block';
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Load tab content if needed
            loadTabContent(tabName);
        }
        
        // Load tab content dynamically
        function loadTabContent(tabName) {
            const tabContent = document.getElementById(tabName + 'Tab');
            
            if (tabName === 'pickup') {
                tabContent.innerHTML = `
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Item Type</th>
                                <th>Quantity</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingPickups)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                        No pending pickup requests
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingPickups as $pickup): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info-row">
                                                <div class="user-avatar-small">
                                                    <?php 
                                                    $nameParts = explode(' ', $pickup['user_name']);
                                                    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                                    echo $initials ?: 'U';
                                                    ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($pickup['user_name']); ?></div>
                                                    <div style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($pickup['user_email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="waste-type">
                                               <?php 
$wasteIcons = array(
    'plastic' => '🥤',
    'paper' => '📄',
    'metal' => '🔩',
    'glass' => '🥛',
    'electronic' => '💻',
    'organic' => '🍃',
    'other' => '🗑️'
);
echo $wasteIcons[$pickup['waste_type']] ?? '🗑️';
?>
                                                <?php echo ucfirst($pickup['waste_type']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo $pickup['waste_weight'] ? number_format($pickup['waste_weight'], 1) . ' kg' : 'N/A'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($pickup['pickup_date'])); ?></td>
                                        <td><span class="status-badge status-pending">Pending</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn approve" 
                                                        onclick="approvePickup(<?php echo $pickup['id']; ?>)">
                                                    Approve
                                                </button>
                                                <button class="action-btn reject" 
                                                        onclick="rejectPickup(<?php echo $pickup['id']; ?>)">
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                `;
            } else if (tabName === 'scheduled') {
                tabContent.innerHTML = `
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Waste Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($scheduledPickups)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                        No scheduled pickups
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($scheduledPickups as $pickup): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info-row">
                                                <div class="user-avatar-small">
                                                    <?php 
                                                    $nameParts = explode(' ', $pickup['user_name']);
                                                    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                                    echo $initials ?: 'U';
                                                    ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($pickup['user_name']); ?></div>
                                                    <div style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($pickup['user_email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="waste-type">
                                                <?php 
$wasteIcons = array(
    'plastic' => '🥤',
    'paper' => '📄',
    'metal' => '🔩',
    'glass' => '🥛',
    'electronic' => '💻',
    'organic' => '🍃',
    'other' => '🗑️'
);
echo $wasteIcons[$pickup['waste_type']] ?? '🗑️';
?>
                                                <?php echo ucfirst($pickup['waste_type']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($pickup['pickup_date'])); ?></td>
                                        <td><?php echo str_replace('-', ' - ', $pickup['pickup_time']); ?></td>
                                        <td><span class="status-badge status-scheduled">Scheduled</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn complete" 
                                                        onclick="completePickup(<?php echo $pickup['id']; ?>)">
                                                    Complete
                                                </button>
                                                <button class="action-btn view" 
                                                        onclick="viewPickupDetails(<?php echo $pickup['id']; ?>)">
                                                    Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                `;
            } else if (tabName === 'users') {
                tabContent.innerHTML = `
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allUsers)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                        No users found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info-row">
                                                <div class="user-avatar-small">
                                                    <?php 
                                                    $nameParts = explode(' ', $user['fullname']);
                                                    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                                    echo $initials ?: 'U';
                                                    ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                                    <div style="color: #666; font-size: 12px;">
                                                        Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['contact']); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'status-' . str_replace('_', '', $user['status']);
                                            $statusText = ucfirst($user['status']);
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td><?php echo ucfirst($user['role']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($user['status'] === 'approved'): ?>
                                                    <button class="action-btn suspend" 
                                                            onclick="suspendUser(<?php echo $user['id']; ?>)">
                                                        Suspend
                                                    </button>
                                                <?php elseif ($user['status'] === 'suspended'): ?>
                                                    <button class="action-btn activate" 
                                                            onclick="activateUser(<?php echo $user['id']; ?>)">
                                                        Activate
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn view" 
                                                        onclick="viewUser(<?php echo $user['id']; ?>)">
                                                    View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                `;
            }
        }
        
        // User action functions
        function approveUser(userId) {
            if (confirm('Approve this user account?')) {
                window.location.href = '?approve_user=' + userId;
            }
        }
        
        function rejectUser(userId) {
            if (confirm('Reject this user account?')) {
                window.location.href = '?reject_user=' + userId;
            }
        }
        
        function suspendUser(userId) {
            if (confirm('Suspend this user account?')) {
                window.location.href = '?suspend_user=' + userId;
            }
        }
        
        function activateUser(userId) {
            if (confirm('Activate this user account?')) {
                window.location.href = '?activate_user=' + userId;
            }
        }
        
        function approvePickup(pickupId) {
            if (confirm('Approve this pickup request?')) {
                window.location.href = '?approve_pickup=' + pickupId;
            }
        }
        
        function rejectPickup(pickupId) {
            if (confirm('Reject this pickup request?')) {
                window.location.href = '?reject_pickup=' + pickupId;
            }
        }
        
        function completePickup(pickupId) {
            if (confirm('Mark this pickup as completed?')) {
                window.location.href = '?complete_pickup=' + pickupId;
            }
        }
        
        function viewPickupDetails(pickupId) {
            alert('View pickup details for ID: ' + pickupId + '\n\nIn a full implementation, this would open a detailed view modal.');
        }
        
        function viewUser(userId) {
            alert('View user details for ID: ' + userId + '\n\nIn a full implementation, this would open a user profile modal.');
        }
        
        // Simple logout function
        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '?action=logout';
            }
        }
        
        // Search form submission
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchForm').submit();
            }
        });
        
        // Auto-refresh data every 2 minutes
        setInterval(() => {
            // In production, you could add auto-refresh logic here
            console.log('Admin panel auto-refreshed at: ' + new Date().toLocaleTimeString());
        }, 120000);
    </script>
</body>
</html>