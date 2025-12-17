<?php
require_once "../config/db.php";
require_once "../models/SchedulePickUp.php";

$db = (new Database())->connect();
$pickupModel = new WastePickup($db);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

   
    switch ($action) {

        // dito naman is para sa fetch all pickups
        case 'all':
            $userId = $_SESSION['user_id'];

            if (empty($userId)) {
                echo "No user ID";
                exit;
            }

            $pickups = $pickupModel->getPickupsByUser($userId);

            if (!$pickups) {
                echo "No data";
                exit;
            }

           echo json_encode($pickups);
            break;

        // para sa fetch single pickup
        case 'byId':
            $id = trim($_GET['id'] ?? '');

            if (empty($id)) {
                echo "No ID";
                exit;
            }

            $pickup = $pickupModel->getPickupById($id);

            if (!$pickup) {
                echo "No data";
                exit;
            }

            echo json_encode($pickup);
            break;

        default:
            echo "Invalid action";
            break;
    }

} else {
    echo "Invalid request method";
}
