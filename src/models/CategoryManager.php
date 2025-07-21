<?php
require_once __DIR__ . '/../config/database.php';

class CategoryManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllCategories() {
        try {
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener categorías: ' . $e->getMessage());
        }
    }
    
    public function getActiveCategories() {
        try {
            $query = "SELECT * FROM categories WHERE status = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener categorías activas: ' . $e->getMessage());
        }
    }
    
    public function getCategoryById($id) {
        try {
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     WHERE c.id = :id 
                     GROUP BY c.id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('Error al obtener categoría: ' . $e->getMessage());
        }
    }
    
    public function createCategory($data) {
        try {
            $query = "INSERT INTO categories (name, status) VALUES (:name, :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':status', $data['status'] ?? 1);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al crear categoría: ' . $e->getMessage());
        }
    }
    
    public function updateCategory($id, $data) {
        try {
            $query = "UPDATE categories SET name = :name, status = :status WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':status', $data['status']);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al actualizar categoría: ' . $e->getMessage());
        }
    }
    
    public function deleteCategory($id) {
        try {
            // Verificar que no tenga productos asociados
            $checkQuery = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $result = $checkStmt->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception('No se puede eliminar la categoría porque tiene productos asociados');
            }
            
            $query = "DELETE FROM categories WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al eliminar categoría: ' . $e->getMessage());
        }
    }
}
?>
