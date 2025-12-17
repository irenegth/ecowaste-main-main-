<?php
class WasteRequest {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // para sa get all requests
    public function getRequestsByUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM pickup_requests WHERE user_id = :user_id ORDER BY preferred_date ASC");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // para sa get request by id
    public function getRequestById($id) {
        $stmt = $this->db->prepare("SELECT * FROM pickup_requests WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // para sa create request
    public function createRequest($userId, $category, $estimatedWeight, $preferredDate, $timePreference, $itemDescription, $specialInstructions, $requestedBy, $requestDate, $status = 'pending') {
        $stmt = $this->db->prepare("
            INSERT INTO pickup_requests 
            (user_id, waste_category, estimated_weight, preferred_date, time_preference, item_description, special_instructions, status, requested_by, request_date) 
            VALUES 
            (:user_id, :waste_category, :estimated_weight, :preferred_date, :time_preference, :item_description, :special_instructions, :status, :requested_by, :request_date)
        ");

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':waste_category', $category);
        $stmt->bindParam(':estimated_weight', $estimatedWeight);
        $stmt->bindParam(':preferred_date', $preferredDate);
        $stmt->bindParam(':time_preference', $timePreference);
        $stmt->bindParam(':item_description', $itemDescription);
        $stmt->bindParam(':special_instructions', $specialInstructions);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':requested_by', $requestedBy);
        $stmt->bindParam(':request_date', $requestDate);

        return $stmt->execute();
    }

    // para sa update status
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE pickup_requests SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    //para sa delete request
    public function deleteRequest($id) {
    $stmt = $this->db->prepare("DELETE FROM pickup_requests WHERE id = :id");
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

}
