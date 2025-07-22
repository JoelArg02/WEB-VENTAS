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
            $query = "SELECT s.*, u.name as user_name 
                     FROM sales s 
                     LEFT JOIN users u ON s.user_id = u.id 
                     ORDER BY s.id DESC";
            
            if ($limit) {
                $query .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $sales = $stmt->fetchAll();
            
            // Para cada venta, obtener los items
            foreach ($sales as &$sale) {
                $sale['items'] = $this->getSaleItems($sale['id']);
            }
            
            return $sales;
        } catch (Exception $e) {
            throw new Exception('Error al obtener ventas: ' . $e->getMessage());
        }
    }
    
    public function getSalesToday() {
        try {
            $query = "SELECT s.*, u.name as user_name 
                     FROM sales s 
                     LEFT JOIN users u ON s.user_id = u.id 
                     WHERE DATE(s.sale_date) = CURDATE() 
                     ORDER BY s.sale_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $sales = $stmt->fetchAll();
            
            // Para cada venta, obtener los items
            foreach ($sales as &$sale) {
                $sale['items'] = $this->getSaleItems($sale['id']);
            }
            
            return $sales;
        } catch (Exception $e) {
            throw new Exception('Error al obtener ventas de hoy: ' . $e->getMessage());
        }
    }
    
    public function getSaleItems($saleId) {
        try {
            $query = "SELECT si.*, p.name as product_name 
                     FROM sale_items si 
                     LEFT JOIN products p ON si.product_id = p.id 
                     WHERE si.sale_id = :sale_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sale_id', $saleId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener items de venta: ' . $e->getMessage());
        }
    }
    
    public function getTotalSalesToday() {
        try {
            $query = "SELECT SUM(total_amount) as total 
                     FROM sales 
                     WHERE DATE(sale_date) = CURDATE() AND status = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            throw new Exception('Error al obtener total de ventas de hoy: ' . $e->getMessage());
        }
    }
    
    public function createSale($data) {
        try {
            error_log('SalesManager::createSale - Input data: ' . json_encode($data));
            
            // Verificar que hay productos
            if (!isset($data['products']) || !is_array($data['products']) || count($data['products']) === 0) {
                throw new Exception('No se especificaron productos para la venta');
            }
            
            // Verificar stock disponible para todos los productos
            foreach ($data['products'] as $product) {
                $stockQuery = "SELECT stock FROM products WHERE id = :product_id";
                $stockStmt = $this->conn->prepare($stockQuery);
                $stockStmt->bindParam(':product_id', $product['product_id']);
                $stockStmt->execute();
                $productInfo = $stockStmt->fetch();
                
                if (!$productInfo || $productInfo['stock'] < $product['quantity']) {
                    throw new Exception('Stock insuficiente para uno de los productos seleccionados');
                }
            }
            
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Crear la venta principal
            $query = "INSERT INTO sales (user_id, client, subtotal, iva_amount, total_amount, money_received, change_amount, sale_date, status) 
                     VALUES (:user_id, :client, :subtotal, :iva_amount, :total_amount, :money_received, :change_amount, NOW(), :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':client', $data['client']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':iva_amount', $data['iva_amount']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':money_received', $data['money_received']);
            $stmt->bindParam(':change_amount', $data['change_amount']);
            $status = isset($data['status']) ? $data['status'] : 1;
            $stmt->bindParam(':status', $status);
            
            error_log('SalesManager::createSale - About to execute INSERT query');
            $result = $stmt->execute();
            error_log('SalesManager::createSale - INSERT result: ' . ($result ? 'true' : 'false'));
            
            $saleId = $this->conn->lastInsertId();
            
            // Insertar los detalles de productos
            $itemQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) 
                         VALUES (:sale_id, :product_id, :quantity, :unit_price)";
            $itemStmt = $this->conn->prepare($itemQuery);
            
            foreach ($data['products'] as $product) {
                $itemStmt->bindParam(':sale_id', $saleId);
                $itemStmt->bindParam(':product_id', $product['product_id']);
                $itemStmt->bindParam(':quantity', $product['quantity']);
                // Usar 'price' si existe, sino usar 'unit_price'
                $unitPrice = isset($product['price']) ? $product['price'] : $product['unit_price'];
                $itemStmt->bindParam(':unit_price', $unitPrice);
                $itemStmt->execute();
                
                // Actualizar stock del producto
                $updateStockQuery = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
                $updateStmt = $this->conn->prepare($updateStockQuery);
                $updateStmt->bindParam(':quantity', $product['quantity']);
                $updateStmt->bindParam(':product_id', $product['product_id']);
                $updateResult = $updateStmt->execute();
                
                error_log('SalesManager::createSale - Stock update result for product ' . $product['product_id'] . ': ' . ($updateResult ? 'true' : 'false'));
            }
            
            $this->conn->commit();
            error_log('SalesManager::createSale - Transaction committed successfully');
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log('SalesManager::createSale - Error: ' . $e->getMessage());
            throw new Exception('Error al crear venta: ' . $e->getMessage());
        }
    }
    
    public function getSalesStats() {
        try {
            $stats = [];
            
            // Ventas de hoy
            $todayQuery = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
                          FROM sales
                          WHERE DATE(sale_date) = CURDATE()";
            $stmt = $this->conn->prepare($todayQuery);
            $stmt->execute();
            $stats['today'] = $stmt->fetch();
            
            // Ventas del mes
            $monthQuery = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
                          FROM sales
                          WHERE YEAR(sale_date) = YEAR(CURDATE()) 
                          AND MONTH(sale_date) = MONTH(CURDATE())";
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
