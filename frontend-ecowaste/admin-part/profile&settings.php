<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings - EcoWaste Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for profile functionality */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .save-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .save-btn:hover {
            background-color: #45a049;
        }
        
        .cancel-btn {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-right: 1rem;
            transition: background-color 0.3s;
        }
        
        .cancel-btn:hover {
            background-color: #e0e0e0;
        }
        
        .edit-form {
            display: none;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Security section specific styles */
        .security-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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
        }

        .password-form {
            max-width: 500px;
        }

        .password-note {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo" onclick="window.location.href='dashboard.html'">
            <img class="logo-icon" src="https://img.icons8.com/?size=100&id=seuCyneMNgp6&format=png&color=000000" alt="EcoWaste Logo">
            <div class="logo-text">
                <div class="logo-title">EcoWaste</div>
                <div class="logo-subtitle">Management</div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-item" onclick="window.location.href='dashboard.html'">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </div>
            
            <div class="nav-item" onclick="window.location.href='recycling-centers.html'">
                <span class="nav-icon">📍</span>
                <span>Recycling Centers</span>
            </div>
            <div class="nav-item" onclick="window.location.href='admin-panel.html'">
                <span class="nav-icon">👥</span>
                <span>Admin Panel</span>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="nav-item" onclick="logout()">
                <span class="nav-icon">🚪</span>
                <span>Log out</span>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="user-section" style="margin-left: auto;">
                <div class="user-info" style="cursor: pointer;" onclick="window.location.href='profile&settings.html'">
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
                <h1 class="page-title">Profile & Settings</h1>
                <p class="page-subtitle">Manage your personal information and security</p>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('profile')">Profile Information</button>
                <button class="tab" onclick="showTab('security')">Security</button>
            </div>

            <div id="tabContent">
                <!-- Profile Tab Content -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div>
                            <div class="section-title">Profile Information</div>
                            <div class="section-subtitle">View and update your profile details</div>
                        </div>
                        <button class="edit-btn" onclick="toggleEdit()">Edit Profile</button>
                    </div>

                    <div class="profile-main">
                        <div class="profile-avatar">AU</div>
                        <div class="profile-info">
                            <div class="profile-name">Admin User</div>
                            <div class="profile-email">admin@example.com</div>
                        </div>
                    </div>

                    <div class="profile-details" id="profileDetails">
                        <div class="detail-item">
                            <div class="detail-icon">👤</div>
                            <div class="detail-content">
                                <div class="detail-label">Full Name</div>
                                <div class="detail-value" id="nameValue">Admin User</div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-icon">📧</div>
                            <div class="detail-content">
                                <div class="detail-label">Email</div>
                                <div class="detail-value" id="emailValue">admin@example.com</div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-icon">📞</div>
                            <div class="detail-content">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value" id="phoneValue">+1234567891</div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-icon">📍</div>
                            <div class="detail-content">
                                <div class="detail-label">Address</div>
                                <div class="detail-value" id="addressValue">Admin Office, Eco City</div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-icon">🏷️</div>
                            <div class="detail-content">
                                <div class="detail-label">Role</div>
                                <div class="detail-value" id="roleValue">Admin</div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form (hidden by default) -->
                    <div class="edit-form" id="editForm">
                        <div class="section-title" style="margin-top: 2rem; margin-bottom: 1rem;">Edit Profile</div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="editName" value="Admin User" placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="editEmail" value="admin@example.com" placeholder="Enter your email">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" id="editPhone" value="+1234567891" placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea id="editAddress" rows="2" placeholder="Enter your address">Admin Office, Eco City</textarea>
                        </div>
                        <div class="form-actions">
                            <button class="cancel-btn" onclick="cancelEdit()">Cancel</button>
                            <button class="save-btn" onclick="saveProfile()">Save Changes</button>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="section-title" style="margin-bottom: 1.5rem;">Account Statistics</div>
                    <div class="section-subtitle" style="margin-bottom: 2rem;">Your waste management journey</div>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Total Pickups</div>
                            <div class="stat-value">15</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Weight Recycled</div>
                            <div class="stat-value">45kg</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">CO₂ Saved</div>
                            <div class="stat-value">120kg</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let isEditing = false;
        let originalData = {};

        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');

            const content = document.getElementById('tabContent');

            if (tab === 'profile') {
                content.innerHTML = `
                    <div class="profile-card">
                        <div class="profile-header">
                            <div>
                                <div class="section-title">Profile Information</div>
                                <div class="section-subtitle">View and update your profile details</div>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit()">Edit Profile</button>
                        </div>

                        <div class="profile-main">
                            <div class="profile-avatar">AU</div>
                            <div class="profile-info">
                                <div class="profile-name">Admin User</div>
                                <div class="profile-email">admin@example.com</div>
                            </div>
                        </div>

                        <div class="profile-details" id="profileDetails">
                            <div class="detail-item">
                                <div class="detail-icon">👤</div>
                                <div class="detail-content">
                                    <div class="detail-label">Full Name</div>
                                    <div class="detail-value" id="nameValue">Admin User</div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">📧</div>
                                <div class="detail-content">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value" id="emailValue">admin@example.com</div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">📞</div>
                                <div class="detail-content">
                                    <div class="detail-label">Phone</div>
                                    <div class="detail-value" id="phoneValue">+1234567891</div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">📍</div>
                                <div class="detail-content">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value" id="addressValue">Admin Office, Eco City</div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">🏷️</div>
                                <div class="detail-content">
                                    <div class="detail-label">Role</div>
                                    <div class="detail-value" id="roleValue">Admin</div>
                                </div>
                            </div>
                        </div>

                        <div class="edit-form" id="editForm" style="display: none;">
                            <div class="section-title" style="margin-top: 2rem; margin-bottom: 1rem;">Edit Profile</div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" id="editName" value="Admin User" placeholder="Enter your full name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="editEmail" value="admin@example.com" placeholder="Enter your email">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" id="editPhone" value="+1234567891" placeholder="Enter your phone number">
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <textarea id="editAddress" rows="2" placeholder="Enter your address">Admin Office, Eco City</textarea>
                            </div>
                            <div class="form-actions">
                                <button class="cancel-btn" onclick="cancelEdit()">Cancel</button>
                                <button class="save-btn" onclick="saveProfile()">Save Changes</button>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card">
                        <div class="section-title" style="margin-bottom: 1.5rem;">Account Statistics</div>
                        <div class="section-subtitle" style="margin-bottom: 2rem;">Your waste management journey</div>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Total Pickups</div>
                                <div class="stat-value">15</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Weight Recycled</div>
                                <div class="stat-value">45kg</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">CO₂ Saved</div>
                                <div class="stat-value">120kg</div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (tab === 'security') {
                content.innerHTML = `
                    <div class="security-card">
                        <div class="security-title">Change Password</div>
                        <div class="security-subtitle">Update your password to keep your account secure</div>
                        
                        <div class="password-form">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" id="currentPassword" placeholder="Enter current password">
                            </div>
                            
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" id="newPassword" placeholder="Enter new password">
                                <div class="password-note">Must be at least 8 characters long</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" id="confirmPassword" placeholder="Confirm new password">
                            </div>
                            
                            <button class="save-btn" onclick="updatePassword()">Update Password</button>
                        </div>
                    </div>
                `;
            }
        }

        function toggleEdit() {
            const editForm = document.getElementById('editForm');
            const editBtn = document.querySelector('.edit-btn');
            
            if (!isEditing) {
                // Store original data
                originalData = {
                    name: document.getElementById('nameValue').textContent,
                    email: document.getElementById('emailValue').textContent,
                    phone: document.getElementById('phoneValue').textContent,
                    address: document.getElementById('addressValue').textContent
                };
                
                // Show edit form
                editForm.style.display = 'block';
                editBtn.textContent = 'Cancel Edit';
                editBtn.style.backgroundColor = '#f5f5f5';
                editBtn.style.color = '#333';
                isEditing = true;
            } else {
                // Cancel edit
                cancelEdit();
            }
        }

        function saveProfile() {
            const newName = document.getElementById('editName').value;
            const newEmail = document.getElementById('editEmail').value;
            const newPhone = document.getElementById('editPhone').value;
            const newAddress = document.getElementById('editAddress').value;
            
            // Validation
            if (!newName || !newEmail) {
                alert('Name and email are required');
                return;
            }
            
            if (!isValidEmail(newEmail)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Update values
            document.getElementById('nameValue').textContent = newName;
            document.getElementById('emailValue').textContent = newEmail;
            document.getElementById('phoneValue').textContent = newPhone;
            document.getElementById('addressValue').textContent = newAddress;
            
            // Also update the profile header
            document.querySelector('.profile-name').textContent = newName;
            document.querySelector('.profile-email').textContent = newEmail;
            
            // Also update the top bar user info
            document.querySelector('.user-name').textContent = newName;
            document.querySelector('.user-email').textContent = newEmail;
            
            // Hide edit form
            const editForm = document.getElementById('editForm');
            const editBtn = document.querySelector('.edit-btn');
            editForm.style.display = 'none';
            editBtn.textContent = 'Edit Profile';
            editBtn.style.backgroundColor = '';
            editBtn.style.color = '';
            
            isEditing = false;
            
            alert('Profile updated successfully!');
        }

        function cancelEdit() {
            const editForm = document.getElementById('editForm');
            const editBtn = document.querySelector('.edit-btn');
            
            // Restore original data
            document.getElementById('editName').value = originalData.name;
            document.getElementById('editEmail').value = originalData.email;
            document.getElementById('editPhone').value = originalData.phone;
            document.getElementById('editAddress').value = originalData.address;
            
            // Hide edit form
            editForm.style.display = 'none';
            editBtn.textContent = 'Edit Profile';
            editBtn.style.backgroundColor = '';
            editBtn.style.color = '';
            
            isEditing = false;
        }

        function updatePassword() {
            const currentPassword = document.getElementById('currentPassword')?.value;
            const newPassword = document.getElementById('newPassword')?.value;
            const confirmPassword = document.getElementById('confirmPassword')?.value;
            
            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields');
                return;
            }
            
            if (newPassword.length < 8) {
                alert('New password must be at least 8 characters long');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match');
                return;
            }
            
            // In a real app, you would verify current password with server first
            // For demo purposes, we'll just show success
            alert('Password updated successfully!');
            
            // Clear password fields
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        }

        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
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