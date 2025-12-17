<?php
// auth_simple.php - Place in same folder as dashboard.php

class SimpleAuth {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? 'user@example.com',
                'firstname' => $_SESSION['user_firstname'] ?? 'User',
                'lastname' => $_SESSION['user_lastname'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'user'
            ];
        }
        return null;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
}
?>