<?php
require_once "../config/db.php";
require_once "../models/RecyclingCenters.php";

$db = (new Database())->connect();
$centerModel = new RecyclingCenter($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 

    switch ($action) {
        case 'add':
            $name = trim($_POST['center_name'] ?? '');
            $type = trim($_POST['center_type'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $hours = trim($_POST['operating_hours'] ?? '');
            $items = trim($_POST['accepted_items'] ?? '');
            $lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
            $lng = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
            $distance = !empty($_POST['distance_km']) ? $_POST['distance_km'] : null;

            if (empty($name) || empty($address) || empty($items)) {
                echo "Please fill required fields: Name, Address, Accepted Items.";
                return;
            }

            $success = $centerModel->addCenter($name, $type, $address, $phone, $hours, $items, $lat, $lng, $distance);
            echo $success ? "Recycling center added successfully." : "Error adding center.";
            break;

        case 'update':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo "Invalid center ID.";
                return;
            }

            $success = $centerModel->updateCenter(
                $id,
                trim($_POST['center_name'] ?? ''),
                trim($_POST['center_type'] ?? ''),
                trim($_POST['address'] ?? ''),
                trim($_POST['phone'] ?? ''),
                trim($_POST['operating_hours'] ?? ''),
                trim($_POST['accepted_items'] ?? ''),
                $_POST['latitude'] ?? null,
                $_POST['longitude'] ?? null,
                $_POST['distance_km'] ?? null
            );

            echo $success ? "Recycling center updated successfully." : "Error updating center.";
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo "Invalid center ID.";
                return;
            }

            $success = $centerModel->deleteCenter($id);
            echo $success ? "Recycling center deleted successfully." : "Error deleting center.";
            break;

        default:
            echo "Invalid action.";
            break;
    }
} else {
    echo "Invalid request method.";
}
