<?php
class Participant {
    private $conn;
    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT p.*, c.name as club_name FROM participants p LEFT JOIN clubs c ON p.club_id = c.id ORDER BY p.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($name, $club_id, $birth_date, $address, $gender = null) {
        $stmt = $this->conn->prepare("INSERT INTO participants (name, club_id, birth_date, address, gender) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $club_id, $birth_date, $address, $gender]);
        return $this->conn->lastInsertId();
    }
}