<?php
// simple-login.php - Fallback traditional login
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple test credentials
    if ($email === 'test@example.com' && $password === 'password') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_firstname'] = 'John';
        $_SESSION['user_lastname'] = 'Doe';
        $_SESSION['user_role'] = 'user';
        $_SESSION['logged_in'] = true;
        
        // Redirect to dashboard
        header("Location: ../User part/dashboard.php");
        exit();
    } else {
        // Redirect back to login with error
        header("Location: login.php?error=Invalid credentials. Try test@example.com / password");
        exit();
    }
}

// Invalid access
header("Location: login.php");
exit();
?>