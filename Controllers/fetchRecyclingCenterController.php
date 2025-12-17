<?php
require_once "../config/db.php";
require_once "../models/RecyclingCenters.php";
header("Content-Type: application/json");

$db = (new Database())->connect();
$centerModel = new RecyclingCenter($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    

    switch ($action) {

        // case parasa fetch all centers
        case 'all':
            $centers = $centerModel->getAllCenters();

            if (!$centers) {
                echo "No data";
                exit;
            }

            echo json_encode($centers);
            break;
        // case para sa get center by id
        case 'byId':
            $id = trim($_GET['id'] ?? '');

            if (empty($id)) {
                echo "No ID";
                exit;
            }

            $center = $centerModel->getCenterById($id);

            if (!$center) {
                echo "No data";
                exit;
            }

            echo json_encode($center);
            break;
        default:
            echo "Invalid action";
            break;
    }

} else {
    echo "Invalid request method";
}
