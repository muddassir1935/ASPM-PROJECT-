<?php
class User {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Register a new user with password hashing.
     * Ideal for Unit Testing.
     */
    public function register($name, $email, $password) {
        if(empty($name) || empty($email) || empty($password)) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hash', $hash);
        
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false; // E.g. Duplicate email
        }
    }

    /**
     * Verify user password during login.
     * Ideal for Unit Testing.
     */
    public function verifyPassword($email, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash, role FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }
}