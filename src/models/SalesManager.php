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
            $query = "SELECT s.id, s.client, s.sale_date, s.status, u.name as user_name,
                            COUNT(si.id) as items_count,
                            SUM(si.quantity * si.unit_price) as total_amount
                     FROM sales s 
                     LEFT JOIN users u ON s.user_id = u.id 
                     LEFT JOIN sale_items si ON s.id = si.sale_id
                     GROUP BY s.id, s.client, s.sale_date, s.status, u.name
                     ORDER BY s.id DESC";
            
            if ($limit) {
                $query .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener ventas: ' . $e->getMessage());
        }
    }
    
    public function getSaleById($id) {
        try {
            $query = "SELECT s.*, u.name as user_name
                     FROM sales s 
                     LEFT JOIN users u ON s.user_id = u.id 
                     WHERE s.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $sale = $stmt->fetch();
            
            if ($sale) {
                // Obtener los items de la venta
                $itemsQuery = "SELECT si.*, p.name as product_name
                              FROM sale_items si
                              LEFT JOIN products p ON si.product_id = p.id
                              WHERE si.sale_id = :sale_id";
                
                $itemsStmt = $this->conn->prepare($itemsQuery);
                $itemsStmt->bindParam(':sale_id', $id);
                $itemsStmt->execute();
                $sale['items'] = $itemsStmt->fetchAll();
            }
            
            return $sale;
        } catch (Exception $e) {
            throw new Exception('Error al obtener venta: ' . $e->getMessage());
        }
    }
    
    public function getSalesToday() {
        try {
            $query = "SELECT s.id, s.client, s.sale_date, s.status, u.name as user_name,
                            COUNT(si.id) as items_count,
                            SUM(si.quantity * si.unit_price) as total_amount
                     FROM sales s 
                     LEFT JOIN users u ON s.user_id = u.id 
                     LEFT JOIN sale_items si ON s.id = si.sale_id
                     WHERE DATE(s.sale_date) = CURDATE()
                     GROUP BY s.id, s.client, s.sale_date, s.status, u.name
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
            $query = "SELECT SUM(si.quantity * si.unit_price) as total 
                     FROM sales s
                     LEFT JOIN sale_items si ON s.id = si.sale_id
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
            // Validar que hay items en la venta
            if (!isset($data['items']) || empty($data['items'])) {
                throw new Exception('La venta debe tener al menos un producto');
            }
            
            // Verificar stock para todos los productos
            foreach ($data['items'] as $item) {
                $stockQuery = "SELECT stock, name FROM products WHERE id = :product_id";
                $stockStmt = $this->conn->prepare($stockQuery);
                $stockStmt->bindParam(':product_id', $item['product_id']);
                $stockStmt->execute();
                $product = $stockStmt->fetch();
                
                if (!$product) {
                    throw new Exception('Producto no encontrado');
                }
                
                if ($product['stock'] < $item['quantity']) {
                    throw new Exception("Stock insuficiente para {$product['name']}. Disponible: {$product['stock']}, Solicitado: {$item['quantity']}");
                }
            }
            
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Crear la venta principal
            $query = "INSERT INTO sales (user_id, client, sale_date, status) 
                     VALUES (:user_id, :client, NOW(), :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':client', $data['client']);
            $stmt->bindParam(':status', $data['status'] ?? 1);
            
            if (!$stmt->execute()) {
                throw new Exception('Error al crear la venta');
            }
            
            $saleId = $this->conn->lastInsertId();
            
            // Insertar los items de la venta
            foreach ($data['items'] as $item) {
                $itemQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) 
                             VALUES (:sale_id, :product_id, :quantity, :unit_price)";
                
                $itemStmt = $this->conn->prepare($itemQuery);
                $itemStmt->bindParam(':sale_id', $saleId);
                $itemStmt->bindParam(':product_id', $item['product_id']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':unit_price', $item['unit_price']);
                
                if (!$itemStmt->execute()) {
                    throw new Exception('Error al insertar item de venta');
                }
                
                // Actualizar stock del producto
                $updateStockQuery = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
                $updateStmt = $this->conn->prepare($updateStockQuery);
                $updateStmt->bindParam(':quantity', $item['quantity']);
                $updateStmt->bindParam(':product_id', $item['product_id']);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Error al actualizar stock');
                }
            }
            
            // Confirmar transacción
            $this->conn->commit();
            
            return $saleId;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception('Error al crear venta: ' . $e->getMessage());
        }
    }
    
    public function getSalesStats() {
        try {
            $stats = [];
            
            // Total de ventas del día
            $stats['today_sales'] = $this->getTotalSalesToday();
            
            // Número de ventas del día
            $todayCountQuery = "SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) = CURDATE()";
            $stmt = $this->conn->prepare($todayCountQuery);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['today_count'] = $result['count'];
            
            // Total del mes
            $monthQuery = "SELECT SUM(si.quantity * si.unit_price) as total 
                          FROM sales s
                          LEFT JOIN sale_items si ON s.id = si.sale_id
                          WHERE YEAR(s.sale_date) = YEAR(CURDATE()) 
                          AND MONTH(s.sale_date) = MONTH(CURDATE())";
            $stmt = $this->conn->prepare($monthQuery);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['month_sales'] = $result['total'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            throw new Exception('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }
}
?>
            
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
