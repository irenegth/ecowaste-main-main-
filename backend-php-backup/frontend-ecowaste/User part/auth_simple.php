<?php
// Backup: auth_simple.php (helper)
?>
<?php
// auth_simple.php - backup
class SimpleAuth {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}
?>
