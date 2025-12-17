<?php
require_once "../config/db.php";
require_once "../models/RequestPickUp.php";
header('Content-Type: application/json');

$db = (new Database())->connect();
$requestModel = new WasteRequest($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $action = $_GET['action'] ?? '';

    switch ($action) {

        // para sa fetch all requests
        case 'all':
            $userId = trim($_GET['user_id'] ?? '');

            if (empty($userId)) {
                echo "No user ID";
                exit;
            }

            $requests = $requestModel->getRequestsByUser($userId);

            if (!$requests) {
                echo "No data";
                exit;
            }

            echo json_encode($requests);
            break;

        // dito naman fetch single request
        case 'byId':
            $id = trim($_GET['id'] ?? '');

            if (empty($id)) {
                echo "No ID";
                exit;
            }

            $request = $requestModel->getRequestById($id);

            if (!$request) {
                echo "No data";
                exit;
            }

            echo json_encode($request);
            break;

        default:
            echo "Invalid action";
            break;
    }

} else {
    echo "Invalid request method";
}
