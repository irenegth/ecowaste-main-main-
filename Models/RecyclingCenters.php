<?php
class RecyclingCenter {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // para sa get all centers
    public function getAllCenters() {
        $stmt = $this->db->prepare("SELECT * FROM recycling_centers ORDER BY center_name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // para sa get center by id
    public function getCenterById($id) {
        $stmt = $this->db->prepare("SELECT * FROM recycling_centers WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // para sa add center
    public function addCenter($name, $type, $address, $phone, $operatingHours, $acceptedItems, $latitude = null, $longitude = null, $distanceKm = null) {
        $stmt = $this->db->prepare("
            INSERT INTO recycling_centers 
            (center_name, center_type, address, phone, operating_hours, accepted_items, latitude, longitude, distance_km) 
            VALUES 
            (:center_name, :center_type, :address, :phone, :operating_hours, :accepted_items, :latitude, :longitude, :distance_km)
        ");
        $stmt->bindParam(':center_name', $name);
        $stmt->bindParam(':center_type', $type);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':operating_hours', $operatingHours);
        $stmt->bindParam(':accepted_items', $acceptedItems);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':distance_km', $distanceKm);

        return $stmt->execute();
    }

    // para sa update center
    public function updateCenter($id, $name, $type, $address, $phone, $operatingHours, $acceptedItems, $latitude = null, $longitude = null, $distanceKm = null) {
        $stmt = $this->db->prepare("
            UPDATE recycling_centers SET 
                center_name = :center_name,
                center_type = :center_type,
                address = :address,
                phone = :phone,
                operating_hours = :operating_hours,
                accepted_items = :accepted_items,
                latitude = :latitude,
                longitude = :longitude,
                distance_km = :distance_km
            WHERE id = :id
        ");
        $stmt->bindParam(':center_name', $name);
        $stmt->bindParam(':center_type', $type);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':operating_hours', $operatingHours);
        $stmt->bindParam(':accepted_items', $acceptedItems);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':distance_km', $distanceKm);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // para sa delete center
    public function deleteCenter($id) {
        $stmt = $this->db->prepare("DELETE FROM recycling_centers WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
