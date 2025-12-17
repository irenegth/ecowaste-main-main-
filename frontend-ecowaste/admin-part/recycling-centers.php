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
$recyclingCenters = [
    [
        'id' => 1,
        'name' => 'Mother Earth Foundation Philippines',
        'type' => 'Recycling Center',
        'address' => '88 Don Alejandro Roces Ave, Quezon City, Metro Manila',
        'contact' => '(02) 8-426-6114',
        'hours' => 'Mon-Sat: 8:00 AM - 6:00 PM',
        'accepted' => ['Plastic', 'Paper', 'Glass', 'Metal', 'Electronics'],
        'distance' => '2.5 km',
        'latitude' => 14.6254,
        'longitude' => 121.0178
    ],
    [
        'id' => 2,
        'name' => 'Philippine Recyclers Inc.',
        'type' => 'Junk Shop',
        'address' => 'Tandang Sora Ave, Quezon City, Metro Manila',
        'contact' => '(02) 8-925-1234',
        'hours' => 'Mon-Sun: 7:00 AM - 7:00 PM',
        'accepted' => ['Plastic Bottles', 'Metal Scraps', 'Cardboard', 'Newspapers'],
        'distance' => '3.2 km',
        'latitude' => 14.6581,
        'longitude' => 121.0366
    ],
    [
        'id' => 3,
        'name' => 'Green Antz Builders Inc.',
        'type' => 'Eco-Bricks Facility',
        'address' => 'Marikina City, Metro Manila',
        'contact' => '(02) 8-646-9876',
        'hours' => 'Mon-Fri: 9:00 AM - 5:00 PM',
        'accepted' => ['Plastic Sachets', 'Plastic Laminates', 'Ecobricks'],
        'distance' => '5.8 km',
        'latitude' => 14.6507,
        'longitude' => 121.1029
    ],
    [
        'id' => 4,
        'name' => 'Manila Bay Clean-Up Center',
        'type' => 'Coastal Recycling',
        'address' => 'Roxas Blvd, Manila, Metro Manila',
        'contact' => '(02) 8-567-4321',
        'hours' => 'Tue-Sun: 6:00 AM - 4:00 PM',
        'accepted' => ['Plastic Waste', 'Glass', 'Fishing Nets', 'Marine Debris'],
        'distance' => '8.1 km',
        'latitude' => 14.5832,
        'longitude' => 120.9774
    ],
    [
        'id' => 5,
        'name' => 'Basic Environmental Systems & Technologies (BEST)',
        'type' => 'E-Waste Facility',
        'address' => 'Muntinlupa City, Metro Manila',
        'contact' => '(02) 8-862-5555',
        'hours' => 'Mon-Fri: 8:00 AM - 5:00 PM',
        'accepted' => ['Computers', 'Mobile Phones', 'Batteries', 'Appliances'],
        'distance' => '12.3 km',
        'latitude' => 14.4080,
        'longitude' => 121.0415
    ],
    [
        'id' => 6,
        'name' => 'Plastic Credit Exchange (PCX)',
        'type' => 'Plastic Recycling',
        'address' => 'Makati City, Metro Manila',
        'contact' => '(02) 8-884-1111',
        'hours' => 'Mon-Fri: 9:00 AM - 6:00 PM',
        'accepted' => ['Flexible Plastics', 'Sachets', 'Plastic Films'],
        'distance' => '4.5 km',
        'latitude' => 14.5547,
        'longitude' => 121.0244
    ],
    [
        'id' => 7,
        'name' => 'Metro Clark Waste Management Corp',
        'type' => 'Waste Management',
        'address' => 'Clark Freeport Zone, Pampanga',
        'contact' => '(045) 499-0123',
        'hours' => 'Mon-Sat: 7:00 AM - 5:00 PM',
        'accepted' => ['General Waste', 'Recyclables', 'Hazardous Waste'],
        'distance' => '85.2 km',
        'latitude' => 15.1850,
        'longitude' => 120.5365
    ],
    [
        'id' => 8,
        'name' => 'Envirocycle Philippines',
        'type' => 'Composting Facility',
        'address' => 'Biñan, Laguna',
        'contact' => '(049) 511-2233',
        'hours' => 'Mon-Sat: 8:00 AM - 6:00 PM',
        'accepted' => ['Food Waste', 'Garden Waste', 'Organic Materials'],
        'distance' => '45.7 km',
        'latitude' => 14.3425,
        'longitude' => 121.0790
    ],
    [
        'id' => 9,
        'name' => 'Cebu City Materials Recovery Facility',
        'type' => 'MRF',
        'address' => 'Cebu City, Cebu',
        'contact' => '(032) 254-7890',
        'hours' => 'Mon-Sat: 7:00 AM - 5:00 PM',
        'accepted' => ['Segregated Waste', 'Recyclables', 'Biodegradables'],
        'distance' => '570 km',
        'latitude' => 10.3157,
        'longitude' => 123.8854
    ],
    [
        'id' => 10,
        'name' => 'Davao City Recycling Center',
        'type' => 'Recycling Center',
        'address' => 'Davao City, Davao del Sur',
        'contact' => '(082) 221-3344',
        'hours' => 'Mon-Fri: 8:00 AM - 5:00 PM',
        'accepted' => ['Plastic', 'Paper', 'Metal', 'Glass'],
        'distance' => '1,020 km',
        'latitude' => 7.1907,
        'longitude' => 125.4553
    ]
];

// Get user's recycling centers from database if any
$dbCentersSql = "SELECT * FROM recycling_centers WHERE status = 'active' ORDER BY name ASC";
$dbCentersResult = $conn->query($dbCentersSql);

// If database has centers, use them instead of sample data
if ($dbCentersResult->num_rows > 0) {
    $recyclingCenters = [];
    while ($row = $dbCentersResult->fetch_assoc()) {
        $recyclingCenters[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'type' => $row['materials_accepted'] ? 'Recycling Center' : 'Facility',
            'address' => $row['address'],
            'contact' => $row['contact'],
            'hours' => $row['operating_hours'] ?? 'Mon-Sat: 8AM-5PM',
            'accepted' => explode(',', $row['materials_accepted'] ?? ''),
            'distance' => 'N/A',
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude']
        ];
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
    header("Location: ../../frontend-ecowaste/Auth,login,signup/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Centers - EcoWaste Management</title>
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
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        .rc-search-section {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .rc-search-input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .near-me-btn {
            background: #00A651;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }
        
        .near-me-btn:hover {
            background: #008f45;
        }
        
        .map-container {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #e0f7e9, #c8e6c9);
            border-radius: 10px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .map-placeholder {
            width: 100%;
            height: 100%;
            position: relative;
            background: linear-gradient(135deg, #f1f8e9, #dcedc8);
        }
        
        .map-pin {
            position: absolute;
            font-size: 24px;
            animation: pulse 2s infinite;
            cursor: pointer;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .map-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: 500;
            color: #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .centers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .center-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            border: 1px solid #e9ecef;
            cursor: pointer;
        }
        
        .center-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .center-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .center-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .center-type {
            font-size: 14px;
            color: #00A651;
            font-weight: 500;
            background: #e8f5e9;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .distance {
            background: #f8f9fa;
            color: #495057;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .center-details {
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .detail-icon {
            margin-right: 10px;
            color: #00A651;
            min-width: 20px;
        }
        
        .accepted-items {
            margin-bottom: 20px;
        }
        
        .items-label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 10px;
        }
        
        .items-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .item-tag {
            background: #e8f5e9;
            color: #00A651;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .center-actions {
            display: flex;
            gap: 10px;
        }
        
        .center-action-btn {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #00A651;
            border-radius: 5px;
            background: white;
            color: #00A651;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .center-action-btn:hover {
            background: #f1f8e9;
        }
        
        .center-action-btn.primary {
            background: #00A651;
            color: white;
        }
        
        .center-action-btn.primary:hover {
            background: #008f45;
        }
        
        .tips-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tips-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .tips-section ul {
            padding-left: 20px;
            margin: 0;
        }
        
        .tips-section li {
            margin-bottom: 10px;
            color: #666;
            line-height: 1.6;
        }
        
        /* Philippines-themed styles */
        .ph-flag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }
        
        .ph-flag-red {
            color: #CE1126;
        }
        
        .ph-flag-blue {
            color: #0038A8;
        }
        
        .ph-flag-yellow {
            color: #FCD116;
        }
        
        .philippines-badge {
            display: inline-block;
            background: linear-gradient(to right, #0038A8 33%, #CE1126 33% 66%, transparent 66%);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            margin-left: 5px;
            position: relative;
        }
        
        .philippines-badge:after {
            content: "★";
            color: #FCD116;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Filter buttons */
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            border-color: #00A651;
            color: #00A651;
        }
        
        .filter-btn.active {
            background: #00A651;
            color: white;
            border-color: #00A651;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .centers-grid {
                grid-template-columns: 1fr;
            }
            
            .rc-search-section {
                flex-direction: column;
            }
            
            .map-container {
                height: 200px;
            }
            
            .center-actions {
                flex-direction: column;
            }
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
                <div class="ph-flag">
                    <span class="ph-flag-red">●</span>
                    <span class="ph-flag-blue">●</span>
                    <span class="ph-flag-yellow">●</span>
                    <span style="font-size: 12px; color: #666;">PH</span>
                </div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-item" onclick="window.location.href='dashboard.php'">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </div>
            
            <div class="nav-item active">
                <span class="nav-icon">📍</span>
                <span>Recycling Centers</span>
            </div>
            
            <?php if ($isAdmin): ?>
                <div class="nav-item" onclick="window.location.href='admin-panel.php'">
                    <span class="nav-icon">👥</span>
                    <span>Admin Panel</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin-dashboard.php'">
                    <span class="nav-icon">📊</span>
                    <span>Admin Dashboard</span>
                </div>
            <?php else: ?>
                <div class="nav-item" onclick="window.location.href='schedule-pickup.php'">
                    <span class="nav-icon">📅</span>
                    <span>Schedule Pickup</span>
                </div>
                <div class="nav-item" onclick="window.location.href='request-pickup.php'">
                    <span class="nav-icon">📋</span>
                    <span>Request Pickup</span>
                </div>
            <?php endif; ?>
            
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
                        <?php if ($isAdmin): ?>
                            <div class="user-role" style="font-size: 12px; color: #00A651;">
                                <?php echo ucfirst($currentUser['role']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="page-header">
                <h1 class="page-title">Recycling Centers in the Philippines <span class="philippines-badge">PH</span></h1>
                <p class="page-subtitle">Find nearby locations to recycle your waste and support environmental sustainability</p>
            </div>

            <div class="rc-search-section">
                <input type="text" class="rc-search-input" placeholder="Search by name, location, or waste type...">
                <button class="near-me-btn" onclick="findNearMe()">
                    📍 Near Me
                </button>
            </div>

            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterCenters('all')">All Centers</button>
                <button class="filter-btn" onclick="filterCenters('manila')">Metro Manila</button>
                <button class="filter-btn" onclick="filterCenters('luzon')">Luzon</button>
                <button class="filter-btn" onclick="filterCenters('visayas')">Visayas</button>
                <button class="filter-btn" onclick="filterCenters('mindanao')">Mindanao</button>
                <button class="filter-btn" onclick="filterCenters('ewaste')">E-Waste</button>
                <button class="filter-btn" onclick="filterCenters('plastic')">Plastic</button>
            </div>

            <div class="map-container">
                <div class="map-placeholder">
                    <!-- Map pins for each center -->
                    <?php foreach ($recyclingCenters as $index => $center): ?>
                        <?php if (isset($center['latitude']) && isset($center['longitude'])): ?>
                            <div class="map-pin" style="top: <?php echo rand(10, 90); ?>%; left: <?php echo rand(10, 90); ?>%; color: #00A651;" 
                                 onclick="showCenterDetails(<?php echo $center['id']; ?>)">
                                📍
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="map-center-text">
                        Recycling Centers Across the Philippines<br>
                        <small><?php echo count($recyclingCenters); ?> locations available</small>
                    </div>
                </div>
            </div>

            <div class="centers-grid">
                <?php foreach ($recyclingCenters as $center): ?>
                    <div class="center-card" data-center-id="<?php echo $center['id']; ?>" 
                         data-center-type="<?php echo strtolower($center['type']); ?>"
                         data-center-location="<?php echo strtolower($center['address']); ?>">
                        <div class="center-header">
                            <div>
                                <div class="center-name"><?php echo htmlspecialchars($center['name']); ?></div>
                                <div class="center-type"><?php echo htmlspecialchars($center['type']); ?></div>
                            </div>
                            <div class="distance"><?php echo $center['distance']; ?></div>
                        </div>
                        <div class="center-details">
                            <div class="detail-item">
                                <span class="detail-icon">📍</span>
                                <span><?php echo htmlspecialchars($center['address']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-icon">📞</span>
                                <span><?php echo htmlspecialchars($center['contact']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-icon">🕐</span>
                                <span><?php echo htmlspecialchars($center['hours']); ?></span>
                            </div>
                        </div>
                        <div class="accepted-items">
                            <div class="items-label">Accepted Materials:</div>
                            <div class="items-tags">
                                <?php foreach ($center['accepted'] as $item): ?>
                                    <span class="item-tag"><?php echo htmlspecialchars(trim($item)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="center-actions">
                            <button class="center-action-btn" onclick="event.stopPropagation(); getDirections('<?php echo htmlspecialchars($center['address']); ?>')">
                                🧭 Directions
                            </button>
                            <button class="center-action-btn primary" onclick="event.stopPropagation(); callCenter('<?php echo htmlspecialchars($center['contact']); ?>')">
                                📞 Call Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tips-section">
                <h3 class="tips-title">Recycling Tips for the Philippines 🌏</h3>
                <ul>
                    <li><strong>Clean and Dry:</strong> Wash plastic bottles and cans before recycling</li>
                    <li><strong>Separate Properly:</strong> Sort plastic, paper, glass, and metal separately</li>
                    <li><strong>Flatten Cardboard:</strong> Flatten cardboard boxes to save space</li>
                    <li><strong>Check Hours:</strong> Verify operating hours before visiting</li>
                    <li><strong>Bring Reusable Bags:</strong> Use eco-bags to carry your recyclables</li>
                    <li><strong>Special Handling:</strong> Batteries, fluorescent lamps, and medical waste require special disposal</li>
                    <li><strong>Eco-Bricks:</strong> Consider making eco-bricks from plastic sachets</li>
                    <li><strong>Ask Questions:</strong> If unsure about recyclability, ask the center staff</li>
                    <li><strong>Remove Caps:</strong> Remove bottle caps as they're often made of different materials</li>
                    <li><strong>Local Compliance:</strong> Follow your barangay's waste segregation rules</li>
                </ul>
                <p style="margin-top: 15px; color: #666; font-size: 14px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Important Note:</strong> Proper recycling helps reduce waste in landfills and oceans. 
                    Support the #CleanPhilippines initiative and help protect our environment for future generations!
                </p>
            </div>
        </div>
    </div>

    <script>
        // Function to find centers near user
        function findNearMe() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        alert('Finding recycling centers near your location...\n\nLatitude: ' + 
                              position.coords.latitude + '\nLongitude: ' + position.coords.longitude);
                        
                        // You could implement actual distance calculation here
                        highlightNearbyCenters(position.coords.latitude, position.coords.longitude);
                    },
                    () => {
                        alert('Unable to get your location. Please allow location access in your browser settings.');
                    }
                );
            } else {
                alert('Your browser does not support geolocation.');
            }
        }
        
        // Function to highlight nearby centers
        function highlightNearbyCenters(userLat, userLng) {
            document.querySelectorAll('.center-card').forEach(card => {
                const distanceElement = card.querySelector('.distance');
                const distanceText = distanceElement.textContent;
                
                if (distanceText.includes('km')) {
                    const distance = parseFloat(distanceText);
                    if (distance <= 5) {
                        card.style.border = '2px solid #00A651';
                        card.style.boxShadow = '0 0 15px rgba(0, 166, 81, 0.3)';
                    }
                }
            });
        }

        // Function to get directions
        function getDirections(address) {
            const encodedAddress = encodeURIComponent(address + ', Philippines');
            const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodedAddress}&travelmode=driving`;
            
            if (confirm('Open Google Maps for directions to ' + address + '?')) {
                window.open(mapsUrl, '_blank');
            }
        }

        // Function to call center
        function callCenter(phoneNumber) {
            // Clean phone number (remove parentheses and spaces)
            const cleanNumber = phoneNumber.replace(/[()\s-]/g, '');
            
            if (confirm('Call ' + phoneNumber + '?')) {
                window.location.href = `tel:${cleanNumber}`;
            }
        }

        // Function to show center details
        function showCenterDetails(centerId) {
            const centerCard = document.querySelector(`[data-center-id="${centerId}"]`);
            if (centerCard) {
                const centerName = centerCard.querySelector('.center-name').textContent;
                const centerAddress = centerCard.querySelector('.detail-item:nth-child(1) span:nth-child(2)').textContent;
                const centerContact = centerCard.querySelector('.detail-item:nth-child(2) span:nth-child(2)').textContent;
                const centerHours = centerCard.querySelector('.detail-item:nth-child(3) span:nth-child(2)').textContent;
                
                const details = `
                    <strong>${centerName}</strong>
                    <br><br>
                    <strong>📍 Address:</strong> ${centerAddress}
                    <br>
                    <strong>📞 Contact:</strong> ${centerContact}
                    <br>
                    <strong>🕐 Hours:</strong> ${centerHours}
                    <br><br>
                    What would you like to do?
                `;
                
                if (confirm(details + '\n\nGet Directions: OK\nCall Center: Cancel')) {
                    getDirections(centerAddress);
                } else {
                    callCenter(centerContact);
                }
            }
        }

        // Filter centers by type/location
        function filterCenters(filterType) {
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            const centerCards = document.querySelectorAll('.center-card');
            const searchTerm = document.querySelector('.rc-search-input').value.toLowerCase();
            
            centerCards.forEach(card => {
                const centerName = card.querySelector('.center-name').textContent.toLowerCase();
                const centerType = card.querySelector('.center-type').textContent.toLowerCase();
                const centerAddress = card.querySelector('.detail-item:nth-child(1) span:nth-child(2)').textContent.toLowerCase();
                const acceptedItems = Array.from(card.querySelectorAll('.item-tag'))
                    .map(tag => tag.textContent.toLowerCase())
                    .join(' ');
                
                let shouldShow = true;
                
                // Apply search filter
                if (searchTerm && !(
                    centerName.includes(searchTerm) || 
                    centerType.includes(searchTerm) || 
                    centerAddress.includes(searchTerm) ||
                    acceptedItems.includes(searchTerm)
                )) {
                    shouldShow = false;
                }
                
                // Apply type filter
                if (filterType !== 'all') {
                    switch (filterType) {
                        case 'manila':
                            shouldShow = shouldShow && centerAddress.includes('manila') || centerAddress.includes('quezon') || centerAddress.includes('makati');
                            break;
                        case 'luzon':
                            shouldShow = shouldShow && (centerAddress.includes('pampanga') || centerAddress.includes('laguna') || centerAddress.includes('luzon'));
                            break;
                        case 'visayas':
                            shouldShow = shouldShow && centerAddress.includes('cebu');
                            break;
                        case 'mindanao':
                            shouldShow = shouldShow && centerAddress.includes('davao');
                            break;
                        case 'ewaste':
                            shouldShow = shouldShow && (acceptedItems.includes('electronic') || acceptedItems.includes('computer') || acceptedItems.includes('battery'));
                            break;
                        case 'plastic':
                            shouldShow = shouldShow && (acceptedItems.includes('plastic') || acceptedItems.includes('bottle') || acceptedItems.includes('sachet'));
                            break;
                    }
                }
                
                card.style.display = shouldShow ? 'block' : 'none';
                
                // Count visible centers
                const visibleCenters = document.querySelectorAll('.center-card[style*="block"]').length;
                document.querySelector('.map-center-text small').textContent = `${visibleCenters} locations available`;
            });
        }

        // Simple logout function
        function simpleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '?action=logout';
            }
        }

        // Search functionality
        const searchInput = document.querySelector('.rc-search-input');
        searchInput.addEventListener('input', function() {
            filterCenters(document.querySelector('.filter-btn.active').textContent.toLowerCase().replace(' centers', ''));
        });

        // Add click event to map pins
        document.querySelectorAll('.map-pin').forEach(pin => {
            pin.addEventListener('click', function() {
                const centerId = this.getAttribute('onclick')?.match(/showCenterDetails\((\d+)\)/)?.[1];
                if (centerId) {
                    showCenterDetails(centerId);
                }
            });
        });

        // Add hover effects to center cards
        document.querySelectorAll('.center-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 5px 20px rgba(0,0,0,0.1)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
            });
            
            // Click to show details
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on buttons
                if (e.target.tagName === 'BUTTON') return;
                
                const centerId = this.dataset.centerId;
                showCenterDetails(centerId);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }
            // Escape to clear search
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
        });

        // Initialize with random map pin positions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Recycling Centers page loaded. Found <?php echo count($recyclingCenters); ?> centers in the Philippines.');
            
            // Add click to PH badge in title
            document.querySelector('.philippines-badge').addEventListener('click', function() {
                alert('🇵🇭 Thank you for supporting recycling initiatives in the Philippines!\n\nTogether, we can make the Philippines cleaner and greener!');
            });
        });
    </script>
</body>
</html>