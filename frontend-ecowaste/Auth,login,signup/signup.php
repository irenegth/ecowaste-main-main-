<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EcoWaste Management</title>
    <link rel="stylesheet" href="auth-style.css">
</head>
<body class="auth-body">
    <button class="back-button" onclick="window.location.href='index.php'">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Back
    </button>

    <div class="logo-section">
    <div class="logo">
        <img src="https://img.icons8.com/?size=100&id=seuCyneMNgp6&format=png&color=000000" class="new-logo-icon" alt="EcoWaste Logo">
        EcoWaste Management
    </div>

    <div class="tab-container">
            <button class="tab" onclick="window.location.href='login.html'">Login</button>
        <button class="tab active">Sign Up</button>
    </div>
</div>

    <div class="auth-container">
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Sign up to start managing your waste. Admin approval required.</p>

        <form id="signupForm">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="fullname" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="your@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+1234567890" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" placeholder="123 Green Street" required>
            </div>

            <button type="submit" class="auth-button">Create Account</button>
        </form>

        <div class="warning-box">
            <div class="warning-icon">⚠️</div>
            <p>New accounts require admin approval before you can log in.</p>
        </div>
    </div>

<script>
document.getElementById('signupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get form values
    const formData = {
        fullname: document.getElementById('fullname').value.trim(),
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value.trim(),
        contact: document.getElementById('phone').value.trim(),
        address: document.getElementById('address').value.trim()
    };
    
    // Validation
    if (!formData.fullname || !formData.email || !formData.password || !formData.contact || !formData.address) {
        showAlert('Please fill in all fields.', 'error');
        return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
        showAlert('Please enter a valid email address.', 'error');
        return;
    }
    
    // Password validation
    if (formData.password.length < 6) {
        showAlert('Password must be at least 6 characters.', 'error');
        return;
    }
    
    // Phone validation (optional)
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]+$/;
    if (!phoneRegex.test(formData.contact)) {
        showAlert('Please enter a valid phone number.', 'error');
        return;
    }
    
    // Show loading
    const submitBtn = document.querySelector('.auth-button');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Creating Account...';
    submitBtn.disabled = true;
    
    try {
        // Use FormData
        const formDataObj = new FormData();
        formDataObj.append('fullname', formData.fullname);
        formDataObj.append('email', formData.email);
        formDataObj.append('password', formData.password);
        formDataObj.append('contact', formData.contact);
        formDataObj.append('address', formData.address);
        
        console.log('Sending registration data...');
        
        // Try multiple paths for registration
        let response;
        const possiblePaths = [
            '../../Controllers/userRegisterController.php',
            'http://localhost/ECOWASTE-MAIN/Controllers/userRegisterController.php',
            'http://localhost:8000/Controllers/userRegisterController.php',
            '../Controllers/userRegisterController.php'
        ];
        
        // Try each path
        for (let path of possiblePaths) {
            try {
                console.log('Trying path:', path);
                response = await fetch(path, {
                    method: 'POST',
                    body: formDataObj
                });
                console.log('Success with path:', path);
                break;
            } catch (error) {
                console.log('Failed with path:', path, error);
                continue;
            }
        }
        
        if (!response) {
            throw new Error('Cannot connect to registration server.');
        }
        
        // Debug: Check response
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Response is not JSON:', responseText);
            
            // Try to clean the response
            const cleanedText = responseText.trim();
            if (cleanedText.startsWith('{') && cleanedText.endsWith('}')) {
                try {
                    data = JSON.parse(cleanedText);
                } catch (e2) {
                    throw new Error('Server returned invalid JSON format');
                }
            } else {
                throw new Error('Server error: ' + responseText.substring(0, 100));
            }
        }
        
        // Handle response
        if (data.success === true) {
            showAlert(data.message || 'Registration successful! Please wait for admin approval.', 'success');
            
            // Reset form
            document.getElementById('signupForm').reset();
            
            // Redirect to login page after 3 seconds
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 3000);
            
        } else {
            showAlert(data.message || 'Registration failed. Please try again.', 'error');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
        
    } catch (error) {
        console.error('Error:', error);
        
        let errorMsg = 'Registration error: ' + error.message + '\n\n';
        
        if (error.message.includes('Failed to fetch') || error.message.includes('Cannot connect')) {
            errorMsg += 'TROUBLESHOOTING:\n';
            errorMsg += '1. Make sure PHP server is running\n';
            errorMsg += '2. Check the registration controller exists\n';
            errorMsg += '3. Try accessing directly:\n';
            errorMsg += '   http://localhost/ECOWASTE-MAIN/Controllers/userRegisterController.php\n';
            errorMsg += '\nTEST DIRECTLY:\n';
            errorMsg += 'Open the controller URL in browser to check for PHP errors';
        }
        
        showAlert(errorMsg, 'error');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// Use the same showAlert function from login.php
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.custom-alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `custom-alert ${type}`;
    
    // Add icon based on type
    let icon = '';
    if (type === 'success') {
        icon = '✓';
        alertDiv.style.backgroundColor = '#d4edda';
        alertDiv.style.color = '#155724';
        alertDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        icon = '✗';
        alertDiv.style.backgroundColor = '#f8d7da';
        alertDiv.style.color = '#721c24';
        alertDiv.style.border = '1px solid #f5c6cb';
    } else {
        icon = 'ℹ';
        alertDiv.style.backgroundColor = '#d1ecf1';
        alertDiv.style.color = '#0c5460';
        alertDiv.style.border = '1px solid #bee5eb';
    }
    
    alertDiv.innerHTML = `
        <span class="alert-icon">${icon}</span>
        <span class="alert-message">${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    // Add styles
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.padding = '15px 20px';
    alertDiv.style.borderRadius = '5px';
    alertDiv.style.zIndex = '1000';
    alertDiv.style.display = 'flex';
    alertDiv.style.alignItems = 'center';
    alertDiv.style.gap = '10px';
    alertDiv.style.maxWidth = '400px';
    alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
    alertDiv.style.animation = 'slideIn 0.3s ease-out';
    
    // Add close button styles
    const closeBtn = alertDiv.querySelector('.alert-close');
    closeBtn.style.background = 'none';
    closeBtn.style.border = 'none';
    closeBtn.style.fontSize = '20px';
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.marginLeft = 'auto';
    closeBtn.style.padding = '0';
    closeBtn.style.width = '24px';
    closeBtn.style.height = '24px';
    closeBtn.style.display = 'flex';
    closeBtn.style.alignItems = 'center';
    closeBtn.style.justifyContent = 'center';
    
    // Add animation style if not exists
    if (!document.querySelector('#alert-animation')) {
        const style = document.createElement('style');
        style.id = 'alert-animation';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Check if already logged in (if user somehow reaches signup while logged in)
window.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (isLoggedIn === 'true' && user.id) {
        showAlert('You are already logged in. Redirecting to dashboard...', 'info');
        setTimeout(() => {
            if (user.role === 'admin') {
                window.location.href = '../admin-part/dashboard.php';
            } else {
                window.location.href = '../User part/dashboard.php';
            }
        }, 2000);
    }
});
</script>
</body>
</html>