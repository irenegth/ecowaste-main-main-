<?php
require_once "../config/db.php";
require_once "../models/SchedulePickUp.php";

$db = (new Database())->connect();
$pickupModel = new WastePickup($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  

    switch ($action) {
        case 'add':
            $userId = trim($_POST['user_id'] ?? '');
            $wasteType = trim($_POST['waste_type'] ?? '');
            $pickupDate = trim($_POST['pickup_date'] ?? '');
            $timeSlot = trim($_POST['time_slot'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            if (empty($userId) || empty($wasteType) || empty($pickupDate) || empty($timeSlot)) {
                echo "Please fill all required fields.";
                return;
            }

            $success = $pickupModel->schedulePickup($userId, $wasteType, $pickupDate, $timeSlot, $notes);
            echo $success ? "Pickup scheduled successfully." : "Error scheduling pickup.";
            break;

        case 'update':
            $id = trim($_POST['id'] ?? '');
            if (empty($id)) {
                echo "Invalid pickup ID.";
                return;
            }

            $success = $pickupModel->updatePickup(
                $id,
                trim($_POST['waste_type'] ?? ''),
                trim($_POST['pickup_date'] ?? ''),
                trim($_POST['time_slot'] ?? ''),
                trim($_POST['notes'] ?? ''),
                $_POST['status'] ?? 'scheduled'
            );

            echo $success ? "Pickup updated successfully." : "Error updating pickup.";
            break;

        case 'delete':
            $id = trim($_POST['id'] ?? '');

            if (empty($id)) {
                echo "Invalid pickup ID.";
                exit;
            }

            $success = $pickupModel->deletePickup($id);
            echo $success ? "Pickup deleted successfully." : "Error deleting pickup.";
            break;

        default:
            echo "Invalid action.";
            break;
    }
} else {
    echo "Invalid request method.";
}
