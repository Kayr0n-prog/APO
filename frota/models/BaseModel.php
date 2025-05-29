<?php
require_once __DIR__ . '/../config/database.php';

class BaseModel {
    protected $conn;
    protected $table;

    public function __construct($table) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->table = $table;
    }

    public function create($data) {
        $fields = array_keys($data);
        $values = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO " . $this->table . " (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function read($id = null) {
        if ($id) {
            $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll();
        } else {
            $sql = "SELECT * FROM " . $this->table;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }

    public function update($id, $data) {
        $fields = array_keys($data);
        $set = array_map(function($field) {
            return "$field = ?";
        }, $fields);
        
        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set) . " WHERE id = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id) {
        $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?> 