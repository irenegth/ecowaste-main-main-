<?php
// models/User.php

class UserModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, password, phone, address, 
                   role, status, created_at, updated_at
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, phone, address, 
                   role, status, created_at, updated_at
            FROM users 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find user by ID with password (for admin/verification purposes)
     */
    public function findByIdWithPassword($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Register new user
     */
    public function registerUser($fullName, $email, $password, $contact, $address, $role = 'user', $status = 'pending') {
        // Validate email first
        if ($this->emailExists($email)) {
            throw new Exception("Email already registered");
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users 
            (full_name, email, password, phone, address, role, status, created_at) 
            VALUES 
            (:full_name, :email, :password, :phone, :address, :role, :status, NOW())
        ");
        
        $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $contact, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update user information
     */
    public function updateUserInfo($id, $fullName, $phone, $address) {
        // Get old values for logging
        $oldUser = $this->findById($id);
        
        $stmt = $this->db->prepare("
            UPDATE users SET
                full_name = :full_name,
                phone = :phone,
                address = :address,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $result = $stmt->execute();
        
        // Log changes if successful
        if ($result && $oldUser) {
            $this->logChanges($id, $oldUser, [
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address
            ]);
        }
        
        return $result;
    }

    /**
     * Update user password
     */
    public function updatePassword($id, $newPassword, $currentPassword = null) {
        // Verify current password if provided
        if ($currentPassword !== null) {
            $user = $this->findByIdWithPassword($id);
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("
            UPDATE users SET
                password = :password,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Update user role (admin function)
     */
    public function updateUserRole($id, $role) {
        $stmt = $this->db->prepare("
            UPDATE users SET
                role = :role,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Update user status
     */
    public function updateUserStatus($id, $status) {
        $stmt = $this->db->prepare("
            UPDATE users SET
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Get all users (with pagination for admin)
     */
    public function getAllUsers($limit = 50, $offset = 0, $status = null, $role = null) {
        $sql = "SELECT id, full_name, email, phone, address, role, status, created_at 
                FROM users WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if ($role) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total users
     */
    public function countUsers($status = null, $role = null) {
        $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if ($role) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'];
    }

    /**
     * Search users
     */
    public function searchUsers($searchTerm, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, phone, address, role, status, created_at
            FROM users 
            WHERE 
                full_name LIKE :search OR
                email LIKE :search OR
                phone LIKE :search OR
                address LIKE :search
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        
        $searchTerm = "%$searchTerm%";
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log update history
     */
    public function logUpdateHistory($userId, $field, $oldValue, $newValue, $updatedBy) {
        $stmt = $this->db->prepare("
            INSERT INTO user_update_history
            (user_id, field_name, old_value, new_value, updated_by, updated_at)
            VALUES
            (:user_id, :field_name, :old_value, :new_value, :updated_by, NOW())
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':field_name', $field, PDO::PARAM_STR);
        $stmt->bindParam(':old_value', $oldValue, PDO::PARAM_STR);
        $stmt->bindParam(':new_value', $newValue, PDO::PARAM_STR);
        $stmt->bindParam(':updated_by', $updatedBy, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Get user update history
     */
    public function getUserUpdateHistory($userId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT uh.*, u.full_name as updated_by_name
            FROM user_update_history uh
            LEFT JOIN users u ON uh.updated_by = u.id
            WHERE uh.user_id = :user_id
            ORDER BY uh.updated_at DESC
            LIMIT :limit
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper method to log multiple changes
     */
    private function logChanges($userId, $oldData, $newData) {
        $updatedBy = $_SESSION['user_id'] ?? $userId; // Self-update or admin update
        
        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? '';
            
            if ($oldValue != $newValue) {
                $this->logUpdateHistory($userId, $field, $oldValue, $newValue, $updatedBy);
            }
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function deleteUser($id) {
        $stmt = $this->db->prepare("
            UPDATE users SET
                status = 'deleted',
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Verify password
     */
    public function verifyPassword($userId, $password) {
        $user = $this->findByIdWithPassword($userId);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($role, $status = 'active') {
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, phone, address
            FROM users 
            WHERE role = :role AND status = :status
            ORDER BY full_name
        ");
        
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>