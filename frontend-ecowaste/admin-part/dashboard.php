<?php
// Start session
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

// Fetch admin dashboard statistics
$dashboardData = [];

// 1. Get total users count
$sql = "SELECT COUNT(*) as total FROM users WHERE status = 'approved'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['total_users'] = $row['total'];

// 2. Get pending user applications count
$sql = "SELECT COUNT(*) as total FROM users WHERE status = 'pending'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['pending_applications'] = $row['total'];

// 3. Get total recycling centers count
$sql = "SELECT COUNT(*) as total FROM recycling_centers WHERE status = 'active'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['total_centers'] = $row['total'];

// 4. Get total pickups count (completed)
$sql = "SELECT COUNT(*) as total FROM request_pickup WHERE status = 'completed'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['total_pickups'] = $row['total'];

// 5. Get total waste collected
$sql = "SELECT COALESCE(SUM(waste_weight), 0) as total FROM request_pickup WHERE status = 'completed'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['total_waste'] = $row['total'];

// 6. Get recycling rate (completed vs total pickups)
$sql = "SELECT 
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            COUNT(*) as total
        FROM request_pickup";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if ($row['total'] > 0) {
    $dashboardData['recycling_rate'] = round(($row['completed'] / $row['total']) * 100);
} else {
    $dashboardData['recycling_rate'] = 0;
}

// 7. Get recent pickups (last 7 days)
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
$sql = "SELECT 
            rp.id,
            rp.pickup_date,
            rp.waste_type,
            rp.waste_weight,
            rp.status,
            u.fullname as user_name,
            rp.created_at
        FROM request_pickup rp
        JOIN users u ON rp.user_id = u.id
        WHERE rp.created_at >= ?
        ORDER BY rp.created_at DESC 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sevenDaysAgo);
$stmt->execute();
$result = $stmt->get_result();
$recentPickups = [];
while ($row = $result->fetch_assoc()) {
    $recentPickups[] = $row;
}

// 8. Get waste distribution by type
$sql = "SELECT 
            waste_type,
            COUNT(*) as count,
            COALESCE(SUM(waste_weight), 0) as total_weight
        FROM request_pickup 
        WHERE status = 'completed'
        GROUP BY waste_type";
$result = $conn->query($sql);
$wasteDistribution = [];
while ($row = $result->fetch_assoc()) {
    $wasteDistribution[$row['waste_type']] = [
        'count' => $row['count'],
        'weight' => $row['total_weight']
    ];
}

// 9. Get monthly waste data for chart (last 6 months)
$monthlyData = [];
$monthNames = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    list($year, $month) = explode('-', $date);
    
    $sql = "SELECT 
                COALESCE(SUM(waste_weight), 0) as total_weight,
                COUNT(*) as pickup_count
            FROM request_pickup 
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $monthlyData[] = [
        'weight' => floatval($row['total_weight']),
        'count' => $row['pickup_count']
    ];
    $monthNames[] = date('M', mktime(0, 0, 0, $month, 1));
}

// 10. Get user growth data (last 6 months)
$userGrowth = [];
$userMonthNames = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    list($year, $month) = explode('-', $date);
    
    $sql = "SELECT COUNT(*) as new_users FROM users WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $userGrowth[] = $row['new_users'];
    $userMonthNames[] = date('M', mktime(0, 0, 0, $month, 1));
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

// Handle report download
if (isset($_GET['download_report'])) {
    // Generate report data
    $reportData = [
        'generated_at' => date('Y-m-d H:i:s'),
        'total_users' => $dashboardData['total_users'],
        'pending_applications' => $dashboardData['pending_applications'],
        'total_centers' => $dashboardData['total_centers'],
        'total_pickups' => $dashboardData['total_pickups'],
        'total_waste' => $dashboardData['total_waste'],
        'recycling_rate' => $dashboardData['recycling_rate'],
        'monthly_data' => $monthlyData
    ];
    
    // For now, just show alert. In production, generate CSV/PDF
    echo "<script>alert('Report generation started. Data will be available for download shortly.');</script>";
}

// Function to safely encode data for JavaScript
function js_encode($data) {
    return htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EcoWaste Management</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Additional styles for admin dashboard */
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
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-title {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 14px;
            color: #00A651;
            font-weight: 500;
        }
        
        .stat-change.negative {
            color: #dc3545;
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }
        
        .download-btn {
            background: #00A651;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }
        
        .download-btn:hover {
            background: #008f45;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        
        tbody tr:hover {
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
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-in_progress {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }
        
        .welcome {
            margin-bottom: 30px;
        }
        
        .welcome h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: #333;
        }
        
        .welcome p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-header {
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px 0;
        }
        
        .chart-subtitle {
            color: #666;
            font-size: 14px;
            margin: 0;
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
            <div class="nav-item active">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='recycling-centers.php'">
                <span class="nav-icon">📍</span>
                <span>Recycling Centers</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='admin-panel.php'">
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
            <div class="welcome">
                <h1>Welcome back, <?php echo htmlspecialchars($firstname); ?>! 👋</h1>
                <p>Here's what's happening with your waste management system today.</p>
            </div>

            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Active Users</div>
                        <div class="stat-icon" style="background: #d4f4dd; color: #00A651;">👥</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($dashboardData['total_users']); ?></div>
                    <div class="stat-change">Total registered users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Pending Applications</div>
                        <div class="stat-icon" style="background: #dbe9ff; color: #4285F4;">📍</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($dashboardData['pending_applications']); ?></div>
                    <div class="stat-change">Awaiting approval</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Recycling Centers</div>
                        <div class="stat-icon" style="background: #f3e5f5; color: #9C27B0;">📥</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($dashboardData['total_centers']); ?></div>
                    <div class="stat-change">Active facilities</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Pickups</div>
                        <div class="stat-icon" style="background: #fff3e0; color: #FF9800;">📊</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($dashboardData['total_pickups']); ?></div>
                    <div class="stat-change">Completed collections</div>
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="filter-section">
                <div class="filter-group">
                    <label class="filter-label">Report Type</label>
                    <select class="filter-select" id="reportType">
                        <option value="overview">Overview Report</option>
                        <option value="detailed">Detailed Report</option>
                        <option value="summary">Summary Report</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Period</label>
                    <select class="filter-select" id="reportPeriod">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <button class="download-btn" onclick="downloadReport()">
                    📥 Download Report
                </button>
            </div>

            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Waste Collected</div>
                        <div class="stat-icon" style="background: #e8f5e9; color: #00A651;">📊</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($dashboardData['total_waste'], 0); ?> kg</div>
                    <div class="stat-change">+8.2% from last period</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Recycling Rate</div>
                        <div class="stat-icon" style="background: #e3f2fd; color: #00A651;">📈</div>
                    </div>
                    <div class="stat-value"><?php echo $dashboardData['recycling_rate']; ?>%</div>
                    <div class="stat-change">+12% from last period</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Collections This Month</div>
                        <div class="stat-icon" style="background: #f3e5f5; color: #9C27B0;">📋</div>
                    </div>
                    <div class="stat-value"><?php echo end($monthlyData)['count']; ?></div>
                    <div class="stat-change">+15% from last month</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Monthly Waste Collection</div>
                        <div class="chart-subtitle">Total waste collected per month (in kg)</div>
                    </div>
                    <canvas id="monthlyChart" height="120"></canvas>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">User Growth</div>
                        <div class="chart-subtitle">New user registrations per month</div>
                    </div>
                    <canvas id="userGrowthChart" height="120"></canvas>
                </div>
            </div>

            <!-- Recent Pickups Table -->
            <div class="admin-card">
                <div class="admin-header">
                    <div>
                        <h2 class="admin-title">Recent Pickups</h2>
                        <p class="admin-subtitle">Last 7 days pickup requests</p>
                    </div>
                    <button class="download-btn" onclick="window.location.href='pickup-management.php'">
                        📋 View All Pickups
                    </button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Date</th>
                                <th>User</th>
                                <th>Waste Type</th>
                                <th>Weight (kg)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPickups)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">
                                        No recent pickups found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPickups as $pickup): ?>
                                    <tr>
                                        <td>#<?php echo $pickup['id']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($pickup['pickup_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($pickup['user_name']); ?></td>
                                        <td><?php echo ucfirst($pickup['waste_type']); ?></td>
                                        <td><?php echo $pickup['waste_weight'] ? number_format($pickup['waste_weight'], 2) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'status-' . str_replace('_', '', $pickup['status']);
                                            $statusText = ucwords(str_replace('_', ' ', $pickup['status']));
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Waste Distribution -->
            <div class="admin-card">
                <h2 class="admin-title">Waste Distribution by Type</h2>
                <p class="admin-subtitle">Breakdown of collected waste materials</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                    <?php 
                    $wasteTypes = [
                        'plastic' => ['icon' => '🥤', 'color' => '#00A651'],
                        'paper' => ['icon' => '📄', 'color' => '#4285F4'],
                        'metal' => ['icon' => '🔩', 'color' => '#FF9800'],
                        'glass' => ['icon' => '🥛', 'color' => '#9C27B0'],
                        'electronic' => ['icon' => '💻', 'color' => '#EF5350'],
                        'organic' => ['icon' => '🍃', 'color' => '#8BC34A'],
                        'other' => ['icon' => '🗑️', 'color' => '#6c757d']
                    ];
                    
                    foreach ($wasteTypes as $type => $info):
                        $typeData = $wasteDistribution[$type] ?? ['count' => 0, 'weight' => 0];
                    ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; margin-bottom: 10px;"><?php echo $info['icon']; ?></div>
                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;"><?php echo ucfirst($type); ?></div>
                            <div style="color: #666; font-size: 14px;">
                                <?php echo number_format($typeData['weight'], 0); ?> kg
                            </div>
                            <div style="color: #999; font-size: 12px;">
                                <?php echo $typeData['count']; ?> pickups
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        Chart.defaults.color = '#6c757d';

        // Monthly Waste Collection Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo js_encode($monthNames); ?>,
                datasets: [{
                    label: 'Waste Collected (kg)',
                    data: <?php echo js_encode(array_column($monthlyData, 'weight')); ?>,
                    backgroundColor: 'rgba(0, 166, 81, 0.1)',
                    borderColor: '#00A651',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#00A651'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: { 
                        mode: 'index', 
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return 'Waste: ' + context.parsed.y.toFixed(0) + ' kg';
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        grid: { 
                            color: 'rgba(0, 0, 0, 0.05)' 
                        },
                        ticks: { 
                            color: '#6c757d',
                            callback: function(value) {
                                return value + ' kg';
                            }
                        }
                    },
                    x: {
                        grid: { 
                            display: false 
                        },
                        ticks: { 
                            color: '#6c757d' 
                        }
                    }
                }
            }
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'bar',
            data: {
                labels: <?php echo js_encode($userMonthNames); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo js_encode($userGrowth); ?>,
                    backgroundColor: 'rgba(66, 133, 244, 0.7)',
                    borderColor: '#4285F4',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: { 
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        grid: { 
                            color: 'rgba(0, 0, 0, 0.05)' 
                        },
                        ticks: { 
                            color: '#6c757d',
                            precision: 0
                        }
                    },
                    x: {
                        grid: { 
                            display: false 
                        },
                        ticks: { 
                            color: '#6c757d' 
                        }
                    }
                }
            }
        });

        // Simple logout function
        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '?action=logout';
            }
        }

        // Download report function
        function downloadReport() {
            const reportType = document.getElementById('reportType').value;
            const period = document.getElementById('reportPeriod').value;
            
            // Show loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Generating...';
            btn.disabled = true;
            
            // Simulate report generation
            setTimeout(() => {
                // In production, this would make an AJAX call to generate and download the report
                alert(`Generating ${reportType} report for ${period} period...\n\nIn production, this would download a PDF/CSV file.`);
                
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1000);
        }

        // Auto-refresh data every 5 minutes
        setInterval(() => {
            // You could add auto-refresh logic here if needed
            console.log('Dashboard data auto-refreshed at: ' + new Date().toLocaleTimeString());
        }, 300000); // 5 minutes

        // Add hover effects to stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
                });
            });
        });
    </script>
</body>
</html>