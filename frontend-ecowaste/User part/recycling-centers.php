
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Centers - EcoWaste Management</title>
    <link rel="stylesheet" href="all.css">
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
            
            <div class="nav-item active" onclick="window.location.href='recycling-centers.php'">
                <span class="nav-icon">📍</span>
                <span>Recycling Centers</span>
            </div>
            
        </div>

        <div class="sidebar-footer">
            
            <div class="nav-item" onclick="if(confirm('Log out?')) window.location.href='index.html'">
    <span class="nav-icon">🚪</span>
    <span>Log out</span>
</div>
        </div>
    </div>

    <div class="main-content">
                <div class="top-bar">
            <div class="user-section" style="margin-left: auto;">
                </button>
                <div class="user-info" style="cursor: pointer;" onclick="window.location.href='profile&settings.php'">
                    <div class="user-avatar">AU</div>
                    <div class="user-details">
                        <div class="user-name">Admin User</div>
                        <div class="user-email">admin@example.com</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="page-header">
                <h1 class="page-title">Recycling Centers & Junk Shops</h1>
                <p class="page-subtitle">Find nearby locations to recycle your waste</p>
            </div>

            <div class="search-section">
                <input type="text" class="search-input" placeholder="Search by name, type, or accepted items...">
                <button class="near-me-btn">
                    📍 Near Me
                </button>
            </div>

            <div class="map-container">
                <div class="map-placeholder">
                    <div class="map-pin" style="top: 25%; left: 30%; color: #00A651;">📍</div>
                    <div class="map-pin" style="top: 40%; left: 50%; color: #6c757d;">📍</div>
                    <div class="map-pin" style="top: 15%; left: 65%; color: #FF9800;">📍</div>
                    <div class="map-pin" style="top: 55%; left: 45%; color: #9C27B0;">📍</div>
                    <div class="map-center-text">Interactive Map - Showing 5 locations nearby</div>
                </div>
            </div>

            <div class="centers-grid">
                <div class="center-card">
                    <div class="center-header">
                        <div>
                            <div class="center-name">Green Earth Recycling Center</div>
                            <div class="center-type">Recycling Center</div>
                        </div>
                        <div class="distance">0.8 km</div>
                    </div>
                    <div class="center-details">
                        <div class="detail-item">
                            <span class="detail-icon">📍</span>
                            <span>123 Eco Street, Green District</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📞</span>
                            <span>+1 (555) 0101</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🕐</span>
                            <span>Mon-Sat: 8AM-6PM</span>
                        </div>
                    </div>
                    <div class="accepted-items">
                        <div class="items-label">Accepted Items:</div>
                        <div class="items-tags">
                            <span class="item-tag">Plastic</span>
                            <span class="item-tag">Paper</span>
                            <span class="item-tag">Glass</span>
                            <span class="item-tag">Metal</span>
                        </div>
                    </div>
                    <div class="center-actions">
                        <button class="rc-action-btn">
                            🧭 Directions
                        </button>
                        <button class="rc-action-btn primary">
                            📞 Call
                        </button>
                    </div>
                </div>

                <div class="center-card">
                    <div class="center-header">
                        <div>
                            <div class="center-name">City Junk Shop</div>
                            <div class="center-type">Junk Shop</div>
                        </div>
                        <div class="distance">1.2 km</div>
                    </div>
                    <div class="center-details">
                        <div class="detail-item">
                            <span class="detail-icon">📍</span>
                            <span>456 Recycle Ave, Downtown</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📞</span>
                            <span>+1 (555) 0102</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🕐</span>
                            <span>Mon-Sun: 9AM-7PM</span>
                        </div>
                    </div>
                    <div class="accepted-items">
                        <div class="items-label">Accepted Items:</div>
                        <div class="items-tags">
                            <span class="item-tag">Metal</span>
                            <span class="item-tag">Plastic</span>
                            <span class="item-tag">Cardboard</span>
                            <span class="item-tag">Bottles</span>
                        </div>
                    </div>
                    <div class="center-actions">
                        <button class="rc-action-btn">
                            🧭 Directions
                        </button>
                        <button class="rc-action-btn primary">
                            📞 Call
                        </button>
                    </div>
                </div>

                <div class="center-card">
                    <div class="center-header">
                        <div>
                            <div class="center-name">Tech Recycle Hub</div>
                            <div class="center-type">E-Waste Center</div>
                        </div>
                        <div class="distance">2.1 km</div>
                    </div>
                    <div class="center-details">
                        <div class="detail-item">
                            <span class="detail-icon">📍</span>
                            <span>789 Digital Road, Tech Park</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📞</span>
                            <span>+1 (555) 0103</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🕐</span>
                            <span>Mon-Fri: 9AM-5PM</span>
                        </div>
                    </div>
                    <div class="accepted-items">
                        <div class="items-label">Accepted Items:</div>
                        <div class="items-tags">
                            <span class="item-tag">Electronics</span>
                            <span class="item-tag">Batteries</span>
                            <span class="item-tag">Cables</span>
                            <span class="item-tag">Computers</span>
                        </div>
                    </div>
                    <div class="center-actions">
                        <button class="rc-action-btn">
                            🧭 Directions
                        </button>
                        <button class="rc-action-btn primary">
                            📞 Call
                        </button>
                    </div>
                </div>

                <div class="center-card">
                    <div class="center-header">
                        <div>
                            <div class="center-name">Eco Solutions Recycling</div>
                            <div class="center-type">Recycling Center</div>
                        </div>
                        <div class="distance">3.5 km</div>
                    </div>
                    <div class="center-details">
                        <div class="detail-item">
                            <span class="detail-icon">📍</span>
                            <span>321 Green Lane, Eco Village</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📞</span>
                            <span>+1 (555) 0104</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🕐</span>
                            <span>Tue-Sat: 8AM-6PM</span>
                        </div>
                    </div>
                    <div class="accepted-items">
                        <div class="items-label">Accepted Items:</div>
                        <div class="items-tags">
                            <span class="item-tag">Plastics</span>
                            <span class="item-tag">Glass</span>
                            <span class="item-tag">Paper</span>
                            <span class="item-tag">Organic Waste</span>
                        </div>
                    </div>
                    <div class="center-actions">
                        <button class="rc-action-btn">
                            🧭 Directions
                        </button>
                        <button class="rc-action-btn primary">
                            📞 Call
                        </button>
                    </div>
                </div>
            </div>

            <div class="tips-section">
                <h3 class="tips-title">Tips for Recycling</h3>
                <ul>
                    <li>Clean and dry your recyclables before dropping them off</li>
                    <li>Remove labels and caps from bottles when possible</li>
                    <li>Check if the center accepts your specific waste type before visiting</li>
                    <li>Some centers offer pickup services for bulk items</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Call button functionality
        document.querySelectorAll('.rc-action-btn.primary').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.center-card');
                const centerName = card.querySelector('.center-name').textContent;
                alert(`Calling ${centerName}...`);
            });
        });

        // Directions button functionality
        document.querySelectorAll('.rc-action-btn:not(.primary)').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.center-card');
                const centerName = card.querySelector('.center-name').textContent;
                alert(`Opening directions to ${centerName}...`);
            });
        });

        // Near Me button
        document.querySelector('.near-me-btn').addEventListener('click', function() {
            alert('Finding centers near your location...');
        });
        function showUserMenu() {
    const choice = confirm("Profile & Settings: OK\nLogout: Cancel");
    if (choice) {
        window.location.href = 'profile&settings.html';
    } else {
        logout();
    }
}

function logout() {
    if (confirm('Are you sure you want to log out?')) {
        alert('Logging out...');
        window.location.href = 'login.html';
    }
}
    </script>
</body>
</html>