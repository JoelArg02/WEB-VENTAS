<?php
require_once __DIR__ . '/../config/database.php';

class ProductManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllProducts() {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener productos: ' . $e->getMessage());
        }
    }
    
    public function getLowStockProducts($minStock = 10) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.stock <= :minStock AND p.status = 1 
                     ORDER BY p.stock ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':minStock', $minStock);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener productos con stock bajo: ' . $e->getMessage());
        }
    }
    
    public function getExpiringProducts($days = 30) {
        try {
            $query = "SELECT p.*, c.name as category_name,
                     DATEDIFF(p.expiration_date, NOW()) as days_until_expiration
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.expiration_date IS NOT NULL 
                     AND p.expiration_date <= DATE_ADD(NOW(), INTERVAL :days DAY)
                     AND p.expiration_date >= NOW()
                     AND p.status = 1 
                     ORDER BY p.expiration_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener productos próximos a caducar: ' . $e->getMessage());
        }
    }
    
    public function createProduct($data) {
        try {
            $query = "INSERT INTO products (name, price, stock, image, category_id, status, expiration_date) 
                     VALUES (:name, :price, :stock, :image, :category_id, :status, :expiration_date)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock']);
            $stmt->bindParam(':image', $data['image'] ?? null);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':status', $data['status'] ?? 1);
            $stmt->bindParam(':expiration_date', $data['expiration_date'] ?? null);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al crear producto: ' . $e->getMessage());
        }
    }
    
    public function updateProduct($id, $data) {
        try {
            $query = "UPDATE products SET name = :name, price = :price, stock = :stock, 
                     image = :image, category_id = :category_id, status = :status, 
                     expiration_date = :expiration_date WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock']);
            $stmt->bindParam(':image', $data['image'] ?? null);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':expiration_date', $data['expiration_date'] ?? null);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al actualizar producto: ' . $e->getMessage());
        }
    }
    
    public function updateStock($id, $newStock) {
        try {
            $query = "UPDATE products SET stock = :stock WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':stock', $newStock);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al actualizar stock: ' . $e->getMessage());
        }
    }
    
    public function deleteProduct($id) {
        try {
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al eliminar producto: ' . $e->getMessage());
        }
    }
}
?>
