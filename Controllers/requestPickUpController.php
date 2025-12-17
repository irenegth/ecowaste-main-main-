<?php
require_once "../config/db.php";
require_once "../models/RequestPickUp.php";

$db = (new Database())->connect();
$requestModel = new WasteRequest($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   

    switch ($action) {
        case 'add':
            $userId = trim($_POST['user_id'] ?? '');
            $category = trim($_POST['waste_category'] ?? '');
            $estimatedWeight = trim($_POST['estimated_weight'] ?? '');
            $preferredDate = trim($_POST['preferred_date'] ?? '');
            $timePreference = trim($_POST['time_preference'] ?? '');
            $itemDescription = trim($_POST['item_description'] ?? '');
            $specialInstructions = trim($_POST['special_instructions'] ?? '');
            $requestedBy = trim($_POST['requested_by'] ?? '');
            $requestDate = trim($_POST['request_date'] ?? '');

            if (empty($userId) || empty($category) || empty($preferredDate) || empty($itemDescription) || empty($requestedBy) || empty($requestDate)) {
                echo "Please fill all required fields.";
                return;
            }

            $success = $requestModel->createRequest(
                $userId, $category, $estimatedWeight, $preferredDate, $timePreference, $itemDescription, $specialInstructions, $requestedBy, $requestDate
            );

            echo $success ? "Request submitted successfully." : "Error creating request.";
            break;

        case 'update':
            $id = trim($_POST['id'] ?? '');
            $status = trim($_POST['status'] ?? '');

            if (empty($id) || empty($status)) {
                echo "Invalid request.";
                return;
            }

            $success = $requestModel->updateStatus($id, $status);
            echo $success ? "Request status updated." : "Error updating status.";
            break;
            
        case 'delete':
            $id = trim($_POST['id'] ?? '');

            if (empty($id)) {
                echo "Invalid request ID.";
                exit;
            }

            $success = $requestModel->deleteRequest($id);
            echo $success ? "Request deleted successfully." : "Error deleting request.";
            break;

        default:
            echo "Invalid action.";
            break;
    }
} else {
    echo "Invalid request method.";
}
