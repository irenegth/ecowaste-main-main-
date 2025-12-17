<?php
session_start();

// SIMPLIFIED SESSION CHECK - Mas stable
// Kung naka-login na, redirect agad
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Check kung admin o user
    if (isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'admin') {
            header("Location: ../admin-part/dashboard.php");
            exit();
        } else {
            header("Location: ../User part/dashboard.php");
            exit();
        }
    }
}

// Kunin ang mga messages
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
$success = isset($_SESSION['signup_success']) ? $_SESSION['signup_success'] : '';

// Clear messages pagkatapos ma-display
unset($_SESSION['login_error']);
unset($_SESSION['signup_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EcoWaste Management</title>
    <link rel="stylesheet" href="auth-style.css">
    <style>
        /* FIX: Tanggalin ang JavaScript auto-redirect na nagcacause ng refresh */
        .server-message {
            background: <?php echo $error ? '#f8d7da' : '#d4edda'; ?>;
            color: <?php echo $error ? '#721c24' : '#155724'; ?>;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid <?php echo $error ? '#f5c6cb' : '#c3e6cb'; ?>;
            display: <?php echo ($error || $success) ? 'block' : 'none'; ?>;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Prevent any refresh loops */
        body {
            overflow-x: hidden;
        }
        
        /* Loading state para sa button */
        .auth-button.loading {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="auth-body">
    <?php if ($error || $success): ?>
    <div class="server-message" id="serverMessage">
        <?php 
        if ($error) echo "✗ " . htmlspecialchars($error);
        if ($success) echo "✓ " . htmlspecialchars($success);
        ?>
        <button onclick="document.getElementById('serverMessage').style.display='none'" 
                style="float:right; background:none; border:none; cursor:pointer;">×</button>
    </div>
    <?php endif; ?>

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
            <button class="tab active">Login</button>
            <button class="tab" onclick="window.location.href='signup.php'">Sign Up</button>
        </div>
    </div>

    <div class="auth-container">
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Login to your account to continue</p>

        <!-- FORM NA WALANG AJAX - Traditional method lang -->
        <form id="loginForm" method="POST" action="process-login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="your@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="auth-button" id="loginButton">Login</button>
        </form>
        
        <!-- Optional: Demo credentials display -->
        <div style="text-align: center; margin-top: 15px; font-size: 12px; color: #666;">
            <p>Try: admin@ecowaste.com / password</p>
        </div>
    </div>

<script>
// FIX: Tanggalin ang auto-redirect at localStorage check
// Ito ang main cause ng refresh issue

document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded - Simple version');
    
    // Clear any problematic localStorage para walang conflict
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('user');
    localStorage.removeItem('user_role');
    
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    
    // Simple form validation bago mag-submit
    loginForm.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        
        // Basic validation
        if (!email || !password) {
            e.preventDefault(); // Pigilan ang form submission
            alert('Please fill in both email and password.');
            return false;
        }
        
        // Simple email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return false;
        }
        
        // Show loading state
        loginButton.innerHTML = 'Logging in...';
        loginButton.classList.add('loading');
        loginButton.disabled = true;
        
        // Allow the form to submit normally
        return true;
    });
    
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        const message = document.getElementById('serverMessage');
        if (message) {
            message.style.display = 'none';
        }
    }, 5000);
    
    // Auto-focus sa email field
    document.getElementById('email').focus();
    
    // Optional: Auto-fill for testing (remove in production)
    // document.getElementById('email').value = 'admin@ecowaste.com';
    // document.getElementById('password').value = 'password';
});

// Simple function for testing
function testLogin(role) {
    if (role === 'admin') {
        document.getElementById('email').value = 'admin@ecowaste.com';
        document.getElementById('password').value = 'password';
    } else {
        document.getElementById('email').value = 'user@ecowaste.com';
        document.getElementById('password').value = 'password';
    }
    document.getElementById('loginForm').submit();
}
</script>

</body>
</html>