<?php
class WastePickup {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // fetch all pickups for a user
    public function getPickupsByUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM pickup_schedules WHERE user_id = :user_id ORDER BY pickup_date ASC");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fetch a single pickup by ID
    public function getPickupById($id) {
        $stmt = $this->db->prepare("SELECT * FROM pickup_schedules WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // para sa schedule pickup
    public function schedulePickup($userId, $wasteType, $pickupDate, $timeSlot, $notes = '', $status = 'scheduled') {
        $stmt = $this->db->prepare("
            INSERT INTO pickup_schedules 
            (user_id, waste_type, pickup_date, time_slot, notes, status) 
            VALUES 
            (:user_id, :waste_type, :pickup_date, :time_slot, :notes, :status)
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':waste_type', $wasteType);
        $stmt->bindParam(':pickup_date', $pickupDate);
        $stmt->bindParam(':time_slot', $timeSlot);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    // para sa update pickup
    public function updatePickup($id, $wasteType, $pickupDate, $timeSlot, $notes = '', $status = 'scheduled') {
        $stmt = $this->db->prepare("
            UPDATE pickup_schedules SET
                waste_type = :waste_type,
                pickup_date = :pickup_date,
                time_slot = :time_slot,
                notes = :notes,
                status = :status
            WHERE id = :id
        ");
        $stmt->bindParam(':waste_type', $wasteType);
        $stmt->bindParam(':pickup_date', $pickupDate);
        $stmt->bindParam(':time_slot', $timeSlot);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }



    // para sa delete request
    public function deletePickup($id) {
    $stmt = $this->db->prepare("DELETE FROM pickup_schedules WHERE id = :id");
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

}
