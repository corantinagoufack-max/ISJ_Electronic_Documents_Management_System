<?php
// includes/UserManager.php

class UserManager
{
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    public function getAllUsers()
    {
        // Verified: column is 'fullname'
        $sql = "SELECT id, fullname, email, role, is_verified FROM users";
        $result = $this->db->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception($this->db->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoleBadgeClass($role)
    {
        $role = strtolower($role);
        if ($role === 'admin') return 'admin';
        if ($role === 'standard user' || $role === 'standard') return 'teacher';
        if ($role === 'visualizer') return 'student';
        return 'default';
    }

    public function getUserById($id)
    {
        // FIX: Changed 'full_name' to 'fullname'
        $stmt = $this->db->prepare("SELECT id, fullname, email, role FROM users WHERE id = ?");
        if (!$stmt) {
            die("SQL Prepare Error: " . $this->db->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateUser($id, $data)
    {
        // FIX: Changed 'full_name' to 'fullname'
        $stmt = $this->db->prepare("UPDATE users SET fullname = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $data['fullname'], $data['email'], $data['role'], $id);
        return $stmt->execute();
    }

    // Checks if a fullname is already taken by another user ID.
    public function isNameTaken($fullname, $excludeId)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE fullname = ? AND id != ?");
        $stmt->bind_param("si", $fullname, $excludeId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // Inside includes/UserManager.php

    public function createNewUser($data)
    {
        // Use $this->db (as defined in your constructor)
        $stmt = $this->db->prepare("INSERT INTO users (fullname, email, role, password, is_verified) VALUES (?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->db->error);
        }

        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $is_verified = 1; // Default to verified

        $stmt->bind_param(
            "ssssi",
            $data['fullname'],
            $data['email'],
            $data['role'],
            $password,
            $is_verified
        );

        return $stmt->execute();
    }
}
