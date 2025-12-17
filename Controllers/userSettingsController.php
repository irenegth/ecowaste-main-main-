<?php
require_once "../config/db.php";
require_once "../models/User.php";

$db = (new Database())->connect();
$userModel = new UserModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  
    $userId = trim($_POST['user_id'] ?? '');

    if (empty($userId)) {
        echo "Invalid user ID.";
        exit;
    }

    $currentUser = $userModel->findById($userId);

    if (!$currentUser) {
        echo "User not found.";
        exit;
    }

    switch ($action) {

        // para makapag edit ng user info
        case 'editInfo':
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $updatedBy = trim($_POST['updated_by'] ?? 'user');

            if (empty($fullName) || empty($phone) || empty($address)) {
                echo "Please fill all fields.";
                exit;
            }

            // dito naman e para sa logging changes
            if ($currentUser['full_name'] !== $fullName) {
                $userModel->logUpdateHistory($userId, 'full_name', $currentUser['full_name'], $fullName, $updatedBy);
            }
            if ($currentUser['phone'] !== $phone) {
                $userModel->logUpdateHistory($userId, 'phone', $currentUser['phone'], $phone, $updatedBy);
            }
            if ($currentUser['address'] !== $address) {
                $userModel->logUpdateHistory($userId, 'address', $currentUser['address'], $address, $updatedBy);
            }

            $success = $userModel->updateUserInfo($userId, $fullName, $phone, $address);
            echo $success ? "User information updated successfully." : "Update failed.";
            break;

        // ppara sa change password
        case 'changePassword':
            $newPassword = trim($_POST['new_password'] ?? '');
            $updatedBy = trim($_POST['updated_by'] ?? 'user');

            if (strlen($newPassword) < 6) {
                echo "Password must be at least 6 characters.";
                exit;
            }
            $userModel->logUpdateHistory($userId, 'password', '********', '********', $updatedBy);

            $success = $userModel->updatePassword($userId, $newPassword);
            echo $success ? "Password updated successfully." : "Password update failed.";
            break;

        default:
            echo "Invalid action.";
            break;
    }

} else {
    echo "Invalid request method.";
}
