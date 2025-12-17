<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Use your existing Database class
$database = Database::getInstance();
$conn = $database->getConnection();

// Simple session check
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_role']);

if (!$isLoggedIn) {
    // Check if coming from login (for testing)
    if (isset($_GET['test'])) {
        // For testing only - set dummy session
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'user';
        $_SESSION['user_email'] = 'test@example.com';
        $_SESSION['logged_in'] = true;
        $isLoggedIn = true;
    } else {
        // Redirect to login
        header("Location: ../Auth,login,signup/login.php");
        exit();
    }
}

// Get current user data from database using mysqli
$userId = $_SESSION['user_id'] ?? 1;
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();

if (!$currentUser) {
    // User not found in database
    session_destroy();
    header("Location: ../Auth,login,signup/login.php");
    exit();
}

// Extract firstname from fullname
$fullnameParts = explode(' ', $currentUser['fullname']);
$currentUser['firstname'] = $fullnameParts[0] ?? 'User';

// Fetch dashboard statistics from database
$dashboardData = [];

// 1. Get total users count
$sql = "SELECT COUNT(*) as total FROM users WHERE status = 'approved'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['total_users'] = $row['total'];

// 2. Get today's pickups count
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as total FROM request_pickup WHERE DATE(created_at) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$dashboardData['todays_pickups'] = $row['total'];

// 3. Get total waste recycled (from completed pickups)
$sql = "SELECT COALESCE(SUM(waste_weight), 0) as total FROM request_pickup WHERE status = 'completed'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$dashboardData['total_waste'] = $row['total'];

// 4. Get collection rate (percentage of completed vs total)
$sql = "SELECT 
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            COUNT(*) as total
        FROM request_pickup
        WHERE status IN ('completed', 'cancelled', 'pending')";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if ($row['total'] > 0) {
    $dashboardData['collection_rate'] = round(($row['completed'] / $row['total']) * 100);
} else {
    $dashboardData['collection_rate'] = 0;
}

// 5. Get user's recent transactions
$sql = "SELECT 
            id,
            pickup_date as date,
            waste_type,
            waste_weight,
            status,
            created_at
        FROM request_pickup 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$recentTransactions = [];

while ($row = $result->fetch_assoc()) {
    $row['points'] = $row['waste_weight'] ? intval($row['waste_weight'] * 10) : 0;
    $row['display_id'] = 'TRX-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
    $recentTransactions[] = $row;
}

// 6. Get waste statistics for current user
$currentYear = date('Y');
$currentMonth = date('m');
$sql = "SELECT 
            plastic_weight,
            paper_weight,
            metal_weight,
            glass_weight,
            electronic_weight,
            organic_weight,
            total_weight
        FROM waste_statistics 
        WHERE user_id = ? AND year = ? AND month = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $currentYear, $currentMonth);
$stmt->execute();
$result = $stmt->get_result();
$wasteStats = $result->fetch_assoc();

if (!$wasteStats) {
    // If no stats exist for current month, create default
    $wasteStats = [
        'plastic_weight' => 0,
        'paper_weight' => 0,
        'metal_weight' => 0,
        'glass_weight' => 0,
        'electronic_weight' => 0,
        'organic_weight' => 0,
        'total_weight' => 0
    ];
}

// 7. Get monthly trend data for the last 6 months
$trendData = [
    'biodegradable' => [],
    'non_biodegradable' => []
];
$monthNames = [];

for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    list($year, $month) = explode('-', $date);
    
    $sql = "SELECT 
                COALESCE(SUM(plastic_weight + paper_weight + metal_weight + glass_weight + electronic_weight), 0) as non_biodegradable,
                COALESCE(SUM(organic_weight), 0) as biodegradable
            FROM waste_statistics 
            WHERE year = ? AND month = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $trendData['biodegradable'][] = floatval($row['biodegradable'] ?? 0);
    $trendData['non_biodegradable'][] = floatval($row['non_biodegradable'] ?? 0);
    $monthNames[] = date('M', mktime(0, 0, 0, $month, 1));
}

// 8. Get processing status distribution
$sql = "SELECT 
            status,
            COUNT(*) as count
        FROM request_pickup 
        WHERE status IN ('completed', 'in_progress', 'pending', 'scheduled')
        GROUP BY status";
$result = $conn->query($sql);

// Process status data for chart
$processingData = [
    'Completed' => 0,
    'In Progress' => 0,
    'Pending' => 0,
    'Scheduled' => 0
];

while ($row = $result->fetch_assoc()) {
    switch ($row['status']) {
        case 'completed':
            $processingData['Completed'] = $row['count'];
            break;
        case 'in_progress':
            $processingData['In Progress'] = $row['count'];
            break;
        case 'pending':
            $processingData['Pending'] = $row['count'];
            break;
        case 'scheduled':
            $processingData['Scheduled'] = $row['count'];
            break;
    }
}

// Simple logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Also clear user session from database
    $sql = "DELETE FROM user_sessions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    session_destroy();
    header("Location: ../Auth,login,signup/login.php");
    exit();
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
    <title>Dashboard - EcoWaste Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="all.css">
    <style>
        /* Your existing CSS styles remain the same */
        .logout-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .logout-btn:hover {
            background: #cc0000;
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
        .debug-banner {
            background: #ff9900;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            text-align: center;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-completed {
            background: #d4f4dd;
            color: #00A651;
        }
        .status-pending {
            background: #fff3e0;
            color: #FF9800;
        }
        .status-inprogress {
            background: #dbe9ff;
            color: #4285F4;
        }
        .status-cancelled {
            background: #ffebee;
            color: #EF5350;
        }
        .status-scheduled {
            background: #f3e5f5;
            color: #9C27B0;
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['test'])): ?>
    <div class="debug-banner">
        TEST MODE - Logged in as <?php echo htmlspecialchars($currentUser['fullname']); ?>
    </div>
    <?php endif; ?>
    
    <div class="sidebar">
        <div class="logo" onclick="window.location.href='dashboard.php'">
            <img class="logo-icon" src="https://img.icons8.com/?size=100&id=seuCyneMNgp6&format=png&color=000000" alt="EcoWaste Logo">
            <div class="logo-text">
                <div class="logo-title">EcoWaste</div>
                <div class="logo-subtitle">Management</div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-item active" onclick="window.location.href='dashboard.php'">
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
                <div class="user-info" style="cursor: pointer;" onclick="window.location.href='profile&settings.php'">
                    <div class="user-avatar">
                        <?php 
                        $firstName = $currentUser['firstname'] ?? 'User';
                        echo strtoupper(substr($firstName, 0, 1)); 
                        ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name">
                            <?php 
                            echo htmlspecialchars($currentUser['fullname']); 
                            ?>
                        </div>
                        <div class="user-role">
                            <?php 
                            echo ucfirst($currentUser['role']); 
                            if ($currentUser['status'] == 'pending'): ?>
                                <span style="color: #FF9800; font-size: 0.8em;"> (Pending Approval)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="welcome">
                <h1>Welcome back, <?php echo htmlspecialchars($currentUser['firstname'] ?? 'User'); ?>! 👋</h1>
                <p>Here's what's happening with your waste management today.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #d4f4dd; color: #00A651;">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($dashboardData['total_users']); ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dbe9ff; color: #4285F4;">📋</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($dashboardData['todays_pickups']); ?></div>
                        <div class="stat-label">Today's Pickups</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f3e5f5; color: #9C27B0;">♻️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($dashboardData['total_waste'], 0); ?> kg</div>
                        <div class="stat-label">Waste Recycled</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0; color: #FF9800;">⭐</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $dashboardData['collection_rate']; ?>%</div>
                        <div class="stat-label">Collection Rate</div>
                    </div>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Waste Collection Trends</div>
                            <div class="chart-subtitle">Last 6 months data</div>
                        </div>
                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #00A651;"></div>
                                Biodegradable
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #FFC107;"></div>
                                Non-Biodegradable
                            </div>
                        </div>
                    </div>
                    <canvas id="trendChart" height="70"></canvas>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Your Waste Breakdown</div>
                            <div class="chart-subtitle">This month (kg)</div>
                        </div>
                    </div>
                    <div>
                        <?php 
                        $wasteTypes = [
                            'Plastics' => $wasteStats['plastic_weight'],
                            'Paper' => $wasteStats['paper_weight'],
                            'Glass' => $wasteStats['glass_weight'],
                            'Metal' => $wasteStats['metal_weight'],
                            'Electronics' => $wasteStats['electronic_weight'],
                            'Organic' => $wasteStats['organic_weight']
                        ];
                        
                        $totalWaste = $wasteStats['total_weight'];
                        foreach ($wasteTypes as $type => $weight):
                            if ($totalWaste > 0) {
                                $percentage = round(($weight / $totalWaste) * 100);
                            } else {
                                $percentage = 0;
                            }
                        ?>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label"><?php echo $type; ?></span>
                                <span class="progress-value"><?php echo $percentage; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: #00A651;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="note">
                            Total: <?php echo number_format($totalWaste, 2); ?> kg collected this month
                        </div>
                    </div>
                </div>
            </div>

            <div class="bottom-row">
                <div class="chart-card">
                    <div class="chart-title" style="margin-bottom: 0.2rem;">Waste by Type</div>
                    <div class="chart-subtitle" style="margin-bottom: 1.2rem;">This month</div>
                    <canvas id="wasteTypeChart" height="180"></canvas>
                </div>

                <div class="chart-card">
                    <div class="chart-title" style="margin-bottom: 0.2rem;">Processing Status</div>
                    <div class="chart-subtitle" style="margin-bottom: 1.2rem;">All pickups</div>
                    <canvas id="processingChart" height="180"></canvas>
                </div>

                <div class="chart-card">
                    <div class="chart-title" style="margin-bottom: 0.2rem;">Quick Actions</div>
                    <div class="chart-subtitle" style="margin-bottom: 1.2rem;">Manage your waste</div>
                    <button class="action-btn primary" onclick="window.location.href='schedule-pickup.php'">📅 Schedule Pickup</button>
                    <button class="action-btn" onclick="window.location.href='request-pickup.php'">📋 Request Special Pickup</button>
                    <button class="action-btn" onclick="window.location.href='recycling-centers.php'">📍 Find Recycling Centers</button>
                    <button class="action-btn" onclick="window.location.href='profile.php'">👤 View Profile</button>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div>
                        <div class="chart-title">Recent Transactions</div>
                        <div class="chart-subtitle">Your waste collection records</div>
                    </div>
                    <div class="search-filter">
                        <input type="text" placeholder="Search transactions..." id="searchTransactions">
                        <button class="filter-btn" onclick="filterTable()">⚙️ Filter</button>
                    </div>
                </div>
                <table id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Waste Type</th>
                            <th>Quantity (kg)</th>
                            <th>Points Earned</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #6c757d;">
                                No transactions found. Schedule your first pickup!
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['display_id']); ?></td>
                                <td><?php echo date('d-M-Y', strtotime($transaction['date'])); ?></td>
                                <td><?php echo ucfirst($transaction['waste_type']); ?></td>
                                <td><?php echo $transaction['waste_weight'] ? number_format($transaction['waste_weight'], 2) . ' kg' : 'N/A'; ?></td>
                                <td><?php echo number_format($transaction['points']); ?> pts</td>
                                <td>
                                    <?php 
                                    $statusClass = 'status-' . str_replace('_', '', $transaction['status']);
                                    $statusText = ucwords(str_replace('_', ' ', $transaction['status']));
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
    </div>

    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        Chart.defaults.color = '#6c757d';

        // Trend Chart with real data
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo js_encode($monthNames); ?>,
                datasets: [{
                    label: 'Biodegradable',
                    data: <?php echo js_encode($trendData['biodegradable']); ?>,
                    backgroundColor: 'rgba(139, 195, 74, 0.2)',
                    borderColor: '#00A651',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#00A651'
                }, {
                    label: 'Non-Biodegradable',
                    data: <?php echo js_encode($trendData['non_biodegradable']); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: '#FFC107',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#FFC107'
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
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + ' kg';
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
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Waste Type Bar Chart with real data
        const wasteTypeCtx = document.getElementById('wasteTypeChart').getContext('2d');
        new Chart(wasteTypeCtx, {
            type: 'bar',
            data: {
                labels: ['Plastics', 'Paper', 'Glass', 'Metal', 'Electronics', 'Organic'],
                datasets: [{
                    label: 'Waste Collected (kg)',
                    data: <?php echo js_encode([
                        $wasteStats['plastic_weight'],
                        $wasteStats['paper_weight'],
                        $wasteStats['glass_weight'],
                        $wasteStats['metal_weight'],
                        $wasteStats['electronic_weight'],
                        $wasteStats['organic_weight']
                    ]); ?>,
                    backgroundColor: [
                        '#00A651',
                        '#4285F4',
                        '#9C27B0',
                        '#FF9800',
                        '#EF5350',
                        '#8BC34A'
                    ],
                    borderRadius: 6,
                    borderWidth: 0,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        display: false 
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' kg';
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
                                return value.toFixed(0) + ' kg';
                            }
                        }
                    },
                    x: {
                        grid: { 
                            display: false 
                        },
                        ticks: { 
                            color: '#6c757d',
                            maxRotation: 0
                        }
                    }
                }
            }
        });

        // Processing Donut Chart with real data
        const processingCtx = document.getElementById('processingChart').getContext('2d');
        new Chart(processingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending', 'Scheduled'],
                datasets: [{
                    data: <?php echo js_encode([
                        $processingData['Completed'],
                        $processingData['In Progress'],
                        $processingData['Pending'],
                        $processingData['Scheduled']
                    ]); ?>,
                    backgroundColor: [
                        '#00A651',
                        '#4285F4',
                        '#FF9800',
                        '#9C27B0'
                    ],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: { 
                                size: 11 
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map(function(label, i) {
                                        const value = data.datasets[0].data[i];
                                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return {
                                            text: label + ' (' + percentage + '%)',
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} pickups (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Simple logout function
        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'dashboard.php?action=logout';
            }
        }

        // Simple table filtering
        function filterTable() {
            const input = document.getElementById('searchTransactions');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('transactionsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (found) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }

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
            
            // Enable Enter key for search
            document.getElementById('searchTransactions').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    filterTable();
                }
            });
        });
    </script>
</body>
</html>