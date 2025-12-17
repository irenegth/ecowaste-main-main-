<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Please fill in all fields';
        header('Location: login.php');
        exit();
    }
    
    // Demo credentials (REPLACE THIS WITH DATABASE CHECK)
    $valid_users = [
        'admin@ecowaste.com' => [
            'password' => 'password',
            'name' => 'Admin User',
            'role' => 'admin',
            'id' => 1
        ],
        'user@ecowaste.com' => [
            'password' => 'password',
            'name' => 'Regular User',
            'role' => 'user',
            'id' => 2
        ]
    ];
    
    // Check if user exists
    if (isset($valid_users[$email])) {
        $user = $valid_users[$email];
        
        // Check password (in real app, use password_verify())
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../admin-part/dashboard.php');
            } else {
                header('Location: ../User part/dashboard.php');
            }
            exit();
        } else {
            $_SESSION['login_error'] = 'Invalid password';
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['login_error'] = 'User not found';
        header('Location: login.php');
        exit();
    }
} else {
    // If not POST request, redirect to login
    header('Location: login.php');
    exit();
}