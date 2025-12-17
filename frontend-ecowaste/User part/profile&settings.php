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

// Get user settings
$settingsSql = "SELECT * FROM user_settings WHERE user_id = ?";
$settingsStmt = $conn->prepare($settingsSql);
$settingsStmt->bind_param("i", $userId);
$settingsStmt->execute();
$settingsResult = $settingsStmt->get_result();
$userSettings = $settingsResult->fetch_assoc();

// Get first name from fullname for avatar
$fullnameParts = explode(' ', $currentUser['fullname']);
$firstname = $fullnameParts[0] ?? 'User';
$lastname = $fullnameParts[1] ?? '';

// Get user statistics
$statsSql = "SELECT 
                COUNT(*) as total_pickups,
                COALESCE(SUM(waste_weight), 0) as total_waste,
                COALESCE(SUM(
                    CASE waste_type 
                        WHEN 'plastic' THEN waste_weight * 3.5  -- kg CO2 saved per kg plastic
                        WHEN 'paper' THEN waste_weight * 1.5
                        WHEN 'metal' THEN waste_weight * 8.0
                        WHEN 'glass' THEN waste_weight * 0.8
                        WHEN 'electronic' THEN waste_weight * 5.0
                        WHEN 'organic' THEN waste_weight * 0.5
                        ELSE waste_weight * 2.0
                    END
                ), 0) as co2_saved
            FROM request_pickup 
            WHERE user_id = ? AND status = 'completed'";
$statsStmt = $conn->prepare($statsSql);
$statsStmt->bind_param("i", $userId);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$userStats = $statsResult->fetch_assoc();

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $fullname = $_POST['fullname'];
        $contact = $_POST['contact'];
        $address = $_POST['address'];
        
        if (empty($fullname) || empty($contact)) {
            $error_message = "Full name and contact number are required.";
        } else {
            $updateSql = "UPDATE users SET fullname = ?, contact = ?, address = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssi", $fullname, $contact, $address, $userId);
            
            if ($updateStmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $currentUser['fullname'] = $fullname;
                $currentUser['contact'] = $contact;
                $currentUser['address'] = $address;
                
                // Update firstname for avatar
                $fullnameParts = explode(' ', $fullname);
                $firstname = $fullnameParts[0] ?? 'User';
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
        }
    } elseif (isset($_POST['update_password'])) {
        // Update password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error_message = "Please fill in all password fields.";
        } elseif ($newPassword !== $confirmPassword) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            // Verify current password
            if (password_verify($currentPassword, $currentUser['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $passwordSql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $passwordStmt = $conn->prepare($passwordSql);
                $passwordStmt->bind_param("si", $hashedPassword, $userId);
                
                if ($passwordStmt->execute()) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Error updating password. Please try again.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    } elseif (isset($_POST['update_settings'])) {
        // Update user settings
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $pushNotifications = isset($_POST['push_notifications']) ? 1 : 0;
        $language = $_POST['language'];
        $theme = $_POST['theme'];
        
        if ($userSettings) {
            // Update existing settings
            $updateSettingsSql = "UPDATE user_settings SET 
                                  email_notifications = ?, 
                                  sms_notifications = ?, 
                                  push_notifications = ?, 
                                  language = ?, 
                                  theme = ?, 
                                  updated_at = NOW() 
                                  WHERE user_id = ?";
            $updateSettingsStmt = $conn->prepare($updateSettingsSql);
            $updateSettingsStmt->bind_param("iiissi", $emailNotifications, $smsNotifications, $pushNotifications, $language, $theme, $userId);
        } else {
            // Insert new settings
            $updateSettingsSql = "INSERT INTO user_settings (user_id, email_notifications, sms_notifications, push_notifications, language, theme) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
            $updateSettingsStmt = $conn->prepare($updateSettingsSql);
            $updateSettingsStmt->bind_param("iiiiss", $userId, $emailNotifications, $smsNotifications, $pushNotifications, $language, $theme);
        }
        
        if ($updateSettingsStmt->execute()) {
            $success_message = "Settings updated successfully!";
            // Refresh settings
            $userSettings = [
                'email_notifications' => $emailNotifications,
                'sms_notifications' => $smsNotifications,
                'push_notifications' => $pushNotifications,
                'language' => $language,
                'theme' => $theme
            ];
        } else {
            $error_message = "Error updating settings. Please try again.";
        }
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Clear user session from database
    $sessionSql = "DELETE FROM user_sessions WHERE user_id = ?";
    $sessionStmt = $conn->prepare($sessionSql);
    $sessionStmt->bind_param("i", $userId);
    $sessionStmt->execute();
    
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
    <title>Profile & Settings - EcoWaste Management</title>
    <link rel="stylesheet" href="all.css">
    <style>
        /* Security section specific styles */
        .security-section {
            max-width: 500px;
        }
        
        .security-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .security-subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00A651;
        }
        
        .password-btn {
            background-color: #00A651;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }
        
        .password-btn:hover {
            background-color: #008f45;
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
        
        /* Settings Checkbox Styles */
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        
        /* Profile Avatar in Main Section */
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #00A651;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 32px;
            margin-right: 20px;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: #666;
            font-size: 16px;
        }
        
        /* Settings Section */
        .settings-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .settings-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 10px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #00A651;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .switch-label {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        /* Member Since Format */
        .member-since {
            color: #666;
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
                <div class="user-info" style="cursor: pointer;" onclick="window.location.href='profile.php'">
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
                <h1 class="page-title">Profile & Settings</h1>
                <p class="page-subtitle">Manage your account and preferences</p>
            </div>

            <div class="tabs">
                <button class="profile-tab active" onclick="switchTab('profile')">Profile</button>
                <button class="profile-tab" onclick="switchTab('security')">Security</button>
                <button class="profile-tab" onclick="switchTab('settings')">Settings</button>
            </div>

            <!-- Profile Tab -->
            <div class="tab-content" id="profileTab">
                <div class="profile-card">
                    <div class="profile-header">
                        <div>
                            <h2 class="ps-section-title">Personal Information</h2>
                            <p class="ps-section-subtitle">Update your personal details</p>
                        </div>
                        <button class="edit-btn" onclick="enableEdit()">Edit Profile</button>
                    </div>

                    <div class="profile-main" style="display: flex; align-items: center; margin-bottom: 30px;">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?php echo htmlspecialchars($currentUser['fullname']); ?></div>
                            <div class="profile-email"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                            <div class="member-since">
                                Member since: <?php echo date('F Y', strtotime($currentUser['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="profile-details">
                            <div class="profile-detail-item">
                                <div class="profile-detail-icon">👤</div>
                                <div class="profile-detail-content">
                                    <div class="profile-detail-label">Full Name</div>
                                    <input type="text" name="fullname" class="profile-input" 
                                           value="<?php echo htmlspecialchars($currentUser['fullname']); ?>" readonly>
                                </div>
                            </div>

                            <div class="profile-detail-item">
                                <div class="profile-detail-icon">📧</div>
                                <div class="profile-detail-content">
                                    <div class="profile-detail-label">Email Address</div>
                                    <input type="email" class="profile-input" 
                                           value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                                    <small style="color: #666; font-size: 12px;">Email cannot be changed</small>
                                </div>
                            </div>

                            <div class="profile-detail-item">
                                <div class="profile-detail-icon">📞</div>
                                <div class="profile-detail-content">
                                    <div class="profile-detail-label">Phone Number</div>
                                    <input type="tel" name="contact" class="profile-input" 
                                           value="<?php echo htmlspecialchars($currentUser['contact']); ?>" readonly>
                                </div>
                            </div>

                            <div class="profile-detail-item">
                                <div class="profile-detail-icon">📍</div>
                                <div class="profile-detail-content">
                                    <div class="profile-detail-label">Address</div>
                                    <textarea name="address" class="profile-input" rows="3" readonly 
                                              style="width: 100%; border: none; background: transparent; resize: none;"><?php echo htmlspecialchars($currentUser['address']); ?></textarea>
                                </div>
                            </div>

                            <div class="profile-detail-item">
                                <div class="profile-detail-icon">👥</div>
                                <div class="profile-detail-content">
                                    <div class="profile-detail-label">Account Type</div>
                                    <input type="text" class="profile-input" 
                                           value="<?php echo ucfirst($currentUser['role']); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="update_profile" value="1">
                        <button type="submit" id="saveProfileBtn" class="password-btn" style="display: none;">Save Changes</button>
                    </form>
                </div>

                <div class="stats-card">
                    <h2 class="ps-section-title">Account Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="ps-stat-label">Total Pickups</div>
                            <div class="ps-stat-value"><?php echo $userStats['total_pickups']; ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="ps-stat-label">Recycled Waste</div>
                            <div class="ps-stat-value"><?php echo number_format($userStats['total_waste'], 0); ?> kg</div>
                        </div>
                        <div class="stat-item">
                            <div class="ps-stat-label">CO₂ Saved</div>
                            <div class="ps-stat-value"><?php echo number_format($userStats['co2_saved'], 0); ?> kg</div>
                        </div>
                    </div>
                    <p style="margin-top: 15px; color: #666; font-size: 14px;">
                        <small>Based on completed pickups. CO₂ savings are estimated.</small>
                    </p>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-content" id="securityTab" style="display: none;">
                <div class="settings-section">
                    <h2 class="security-title">Change Password</h2>
                    <p class="security-subtitle">Update your password to keep your account secure</p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="Enter current password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Enter new password (min. 6 characters)" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
                        </div>
                        
                        <input type="hidden" name="update_password" value="1">
                        <button type="submit" class="password-btn">Update Password</button>
                    </form>
                </div>

                <div class="settings-section">
                    <h2 class="security-title">Account Security</h2>
                    <p class="security-subtitle">Manage your account security settings</p>
                    
                    <div style="margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <div>
                                <div style="font-weight: 500; color: #333;">Last Login</div>
                                <div style="color: #666; font-size: 14px;">
                                    <?php 
                                    if ($currentUser['last_login']) {
                                        echo date('F j, Y g:i A', strtotime($currentUser['last_login']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div>
                                <div style="font-weight: 500; color: #333;">Account Status</div>
                                <div style="color: #666; font-size: 14px;">
                                    <?php 
                                    $statusColor = '';
                                    switch ($currentUser['status']) {
                                        case 'approved':
                                            $statusColor = '#00A651';
                                            break;
                                        case 'pending':
                                            $statusColor = '#FF9800';
                                            break;
                                        case 'suspended':
                                            $statusColor = '#dc3545';
                                            break;
                                        default:
                                            $statusColor = '#6c757d';
                                    }
                                    ?>
                                    <span style="color: <?php echo $statusColor; ?>;">
                                        <?php echo ucfirst($currentUser['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-content" id="settingsTab" style="display: none;">
                <div class="settings-section">
                    <h2 class="security-title">Notification Settings</h2>
                    <p class="security-subtitle">Choose how you want to receive notifications</p>
                    
                    <form method="POST" action="">
                        <div class="switch-label">
                            <label class="switch">
                                <input type="checkbox" name="email_notifications" 
                                       <?php echo ($userSettings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span>Email Notifications</span>
                        </div>
                        
                        <div class="switch-label">
                            <label class="switch">
                                <input type="checkbox" name="sms_notifications" 
                                       <?php echo ($userSettings['sms_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span>SMS Notifications</span>
                        </div>
                        
                        <div class="switch-label">
                            <label class="switch">
                                <input type="checkbox" name="push_notifications" 
                                       <?php echo ($userSettings['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span>Push Notifications</span>
                        </div>
                        
                        <div class="form-group" style="margin-top: 30px;">
                            <label>Language</label>
                            <select name="language" class="form-group">
                                <option value="en" <?php echo ($userSettings['language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="es" <?php echo ($userSettings['language'] ?? 'en') == 'es' ? 'selected' : ''; ?>>Spanish</option>
                                <option value="fr" <?php echo ($userSettings['language'] ?? 'en') == 'fr' ? 'selected' : ''; ?>>French</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Theme</label>
                            <select name="theme" class="form-group">
                                <option value="light" <?php echo ($userSettings['theme'] ?? 'light') == 'light' ? 'selected' : ''; ?>>Light</option>
                                <option value="dark" <?php echo ($userSettings['theme'] ?? 'light') == 'dark' ? 'selected' : ''; ?>>Dark</option>
                                <option value="auto" <?php echo ($userSettings['theme'] ?? 'light') == 'auto' ? 'selected' : ''; ?>>Auto</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="update_settings" value="1">
                        <button type="submit" class="password-btn">Save Settings</button>
                    </form>
                </div>
                
                <div class="settings-section">
                    <h2 class="security-title">Account Actions</h2>
                    <p class="security-subtitle">Manage your account preferences</p>
                    
                    <div style="margin-top: 20px;">
                        <button class="password-btn" style="background: #6c757d; margin-right: 10px;" 
                                onclick="exportData()">
                            📥 Export My Data
                        </button>
                        
                        <button class="password-btn" style="background: #dc3545;" 
                                onclick="deleteAccount()">
                            🗑️ Delete Account
                        </button>
                    </div>
                    
                    <p style="margin-top: 15px; color: #666; font-size: 14px;">
                        <small>Note: Deleting your account will permanently remove all your data and cannot be undone.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.profile-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').style.display = 'block';
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Reset profile edit mode when switching tabs
            if (tabName !== 'profile') {
                disableEditMode();
            }
        }

        // Enable edit mode for profile
        function enableEdit() {
            const inputs = document.querySelectorAll('#profileTab input, #profileTab textarea');
            const editBtn = document.querySelector('.edit-btn');
            const saveBtn = document.getElementById('saveProfileBtn');
            
            if (editBtn.textContent === 'Edit Profile') {
                inputs.forEach(input => {
                    if (!input.name.includes('email') && !input.name.includes('role')) {
                        input.removeAttribute('readonly');
                        input.style.backgroundColor = '#f8f9fa';
                        input.style.border = '1px solid #00A651';
                    }
                });
                editBtn.style.display = 'none';
                saveBtn.style.display = 'block';
            }
        }

        // Disable edit mode
        function disableEditMode() {
            const inputs = document.querySelectorAll('#profileTab input, #profileTab textarea');
            const editBtn = document.querySelector('.edit-btn');
            const saveBtn = document.getElementById('saveProfileBtn');
            
            inputs.forEach(input => {
                input.setAttribute('readonly', true);
                input.style.backgroundColor = 'transparent';
                input.style.border = 'none';
            });
            editBtn.textContent = 'Edit Profile';
            editBtn.style.display = 'block';
            saveBtn.style.display = 'none';
        }

        // Simple logout function
        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '?action=logout';
            }
        }

        // Export data function
        function exportData() {
            if (confirm('Export your data? This may take a moment.')) {
                alert('Data export requested. You will receive an email with your data shortly.');
                // In a real app, you would make an AJAX call to export data
            }
        }

        // Delete account function
        function deleteAccount() {
            const confirmation = confirm('⚠️ WARNING: This will permanently delete your account and all associated data.\n\nThis action cannot be undone.\n\nType "DELETE" to confirm:');
            
            if (confirmation) {
                const userInput = prompt('Please type "DELETE" to confirm account deletion:');
                if (userInput === 'DELETE') {
                    alert('Account deletion requested. An administrator will process your request within 24 hours.');
                    // In a real app, you would make an AJAX call or redirect to deletion page
                } else {
                    alert('Account deletion cancelled.');
                }
            }
        }

        // Password strength checker
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                
                // You could add visual feedback here
                if (password.length > 0 && password.length < 6) {
                    this.style.borderColor = '#dc3545';
                } else if (strength === 'strong') {
                    this.style.borderColor = '#00A651';
                } else if (strength === 'medium') {
                    this.style.borderColor = '#FF9800';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
        });

        function checkPasswordStrength(password) {
            if (password.length < 6) return 'weak';
            if (password.length < 10) return 'medium';
            
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            const conditions = [hasUpperCase, hasLowerCase, hasNumbers, hasSpecial];
            const metConditions = conditions.filter(Boolean).length;
            
            if (metConditions >= 3) return 'strong';
            if (metConditions >= 2) return 'medium';
            return 'weak';
        }
    </script>
</body>
</html>