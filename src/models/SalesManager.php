<?php
require_once __DIR__ . '/../config/database.php';

class SalesManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllSales($limit = null) {
        try {
            $query = "SELECT s.*, p.name as product_name, p.price, u.name as user_name 
                     FROM sales s 
                     LEFT JOIN products p ON s.product_id = p.id 
                     LEFT JOIN users u ON s.user_id = u.id 
                     ORDER BY s.id DESC";
            
            if ($limit) {
                $query .= " LIMIT " . $limit;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener ventas: ' . $e->getMessage());
        }
    }
    
    public function getSalesToday() {
        try {
            $query = "SELECT s.*, p.name as product_name, p.price, u.name as user_name 
                     FROM sales s 
                     LEFT JOIN products p ON s.product_id = p.id 
                     LEFT JOIN users u ON s.user_id = u.id 
                     WHERE DATE(s.sale_date) = CURDATE() 
                     ORDER BY s.sale_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener ventas de hoy: ' . $e->getMessage());
        }
    }
    
    public function getTotalSalesToday() {
        try {
            $query = "SELECT SUM(p.price * s.quantity) as total 
                     FROM sales s 
                     LEFT JOIN products p ON s.product_id = p.id 
                     WHERE DATE(s.sale_date) = CURDATE()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            throw new Exception('Error al calcular ventas del día: ' . $e->getMessage());
        }
    }
    
    public function createSale($data) {
        try {
            // Verificar stock disponible
            $stockQuery = "SELECT stock FROM products WHERE id = :product_id";
            $stockStmt = $this->conn->prepare($stockQuery);
            $stockStmt->bindParam(':product_id', $data['product_id']);
            $stockStmt->execute();
            $product = $stockStmt->fetch();
            
            if (!$product || $product['stock'] < $data['quantity']) {
                throw new Exception('Stock insuficiente para realizar la venta');
            }
            
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Crear la venta
            $query = "INSERT INTO sales (product_id, user_id, client, quantity, sale_date, status) 
                     VALUES (:product_id, :user_id, :client, :quantity, NOW(), :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':client', $data['client']);
            $stmt->bindParam(':quantity', $data['quantity']);
            $stmt->bindParam(':status', $data['status'] ?? 1);
            
            $stmt->execute();
            
            // Actualizar stock del producto
            $updateStockQuery = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
            $updateStmt = $this->conn->prepare($updateStockQuery);
            $updateStmt->bindParam(':quantity', $data['quantity']);
            $updateStmt->bindParam(':product_id', $data['product_id']);
            $updateStmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw new Exception('Error al crear venta: ' . $e->getMessage());
        }
    }
    
    public function getSalesStats() {
        try {
            $stats = [];
            
            // Ventas de hoy
            $todayQuery = "SELECT COUNT(*) as count, COALESCE(SUM(p.price * s.quantity), 0) as total 
                          FROM sales s 
                          LEFT JOIN products p ON s.product_id = p.id 
                          WHERE DATE(s.sale_date) = CURDATE()";
            $stmt = $this->conn->prepare($todayQuery);
            $stmt->execute();
            $stats['today'] = $stmt->fetch();
            
            // Ventas del mes
            $monthQuery = "SELECT COUNT(*) as count, COALESCE(SUM(p.price * s.quantity), 0) as total 
                          FROM sales s 
                          LEFT JOIN products p ON s.product_id = p.id 
                          WHERE YEAR(s.sale_date) = YEAR(CURDATE()) 
                          AND MONTH(s.sale_date) = MONTH(CURDATE())";
            $stmt = $this->conn->prepare($monthQuery);
            $stmt->execute();
            $stats['month'] = $stmt->fetch();
            
            return $stats;
        } catch (Exception $e) {
            throw new Exception('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }
}
?>
