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
            
            // Validar estructura de productos
            foreach ($data['products'] as $index => $product) {
                if (!isset($product['product_id']) || empty($product['product_id'])) {
                    throw new Exception("El producto en la posición $index no tiene product_id válido");
                }
                if (!isset($product['quantity']) || $product['quantity'] <= 0) {
                    throw new Exception("El producto en la posición $index no tiene cantidad válida");
                }
                if (!isset($product['price']) && !isset($product['unit_price'])) {
                    throw new Exception("El producto en la posición $index no tiene precio válido");
                }
            }
            
            // Verificar stock disponible para todos los productos
            foreach ($data['products'] as $product) {
                $stockQuery = "SELECT id, stock, name FROM products WHERE id = :product_id AND status = 1";
                $stockStmt = $this->conn->prepare($stockQuery);
                $stockStmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
                $stockStmt->execute();
                $productInfo = $stockStmt->fetch();
                
                error_log('SalesManager::createSale - Product lookup for ID ' . $product['product_id'] . ': ' . json_encode($productInfo));
                
                if (!$productInfo) {
                    throw new Exception('El producto con ID ' . $product['product_id'] . ' no existe o está inactivo');
                }
                
                if ($productInfo['stock'] < $product['quantity']) {
                    throw new Exception('Stock insuficiente para el producto "' . $productInfo['name'] . '". Stock disponible: ' . $productInfo['stock'] . ', solicitado: ' . $product['quantity']);
                }
            }
            
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Crear la venta principal (SIN product_id, quantity, unit_price)
            $query = "INSERT INTO sales (user_id, client, subtotal, iva_amount, total_amount, money_received, change_amount, sale_date, status) 
                     VALUES (:user_id, :client, :subtotal, :iva_amount, :total_amount, :money_received, :change_amount, NOW(), :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':client', $data['client'], PDO::PARAM_STR);
            $stmt->bindValue(':subtotal', $data['subtotal'], PDO::PARAM_STR);
            $stmt->bindValue(':iva_amount', $data['iva_amount'], PDO::PARAM_STR);
            $stmt->bindValue(':total_amount', $data['total_amount'], PDO::PARAM_STR);
            $stmt->bindValue(':money_received', $data['money_received'], PDO::PARAM_STR);
            $stmt->bindValue(':change_amount', $data['change_amount'], PDO::PARAM_STR);
            $status = isset($data['status']) ? $data['status'] : 1;
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
            
            error_log('SalesManager::createSale - About to execute INSERT query for sales table');
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Error al insertar venta: ' . $errorInfo[2]);
            }
            
            error_log('SalesManager::createSale - Sales INSERT successful');
            
            $saleId = $this->conn->lastInsertId();
            error_log('SalesManager::createSale - Sale ID: ' . $saleId);
            
            // Insertar los detalles de productos
            $itemQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) 
                         VALUES (:sale_id, :product_id, :quantity, :unit_price)";
            $itemStmt = $this->conn->prepare($itemQuery);
            
            foreach ($data['products'] as $product) {
                error_log('SalesManager::createSale - Processing product: ' . json_encode($product));
                
                $productId = (int)$product['product_id'];
                $quantity = (int)$product['quantity'];
                $unitPrice = isset($product['price']) ? (float)$product['price'] : (float)$product['unit_price'];
                
                // Validar que los valores sean válidos
                if ($productId <= 0) {
                    throw new Exception('Product ID inválido: ' . $product['product_id']);
                }
                if ($quantity <= 0) {
                    throw new Exception('Cantidad inválida: ' . $product['quantity']);
                }
                if ($unitPrice <= 0) {
                    throw new Exception('Precio inválido: ' . $unitPrice);
                }
                
                error_log('SalesManager::createSale - Inserting: sale_id=' . $saleId . ', product_id=' . $productId . ', quantity=' . $quantity . ', unit_price=' . $unitPrice);
                
                $itemStmt->bindValue(':sale_id', $saleId, PDO::PARAM_INT);
                $itemStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $itemStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $itemStmt->bindValue(':unit_price', $unitPrice, PDO::PARAM_STR);
                
                $itemResult = $itemStmt->execute();
                
                if (!$itemResult) {
                    $errorInfo = $itemStmt->errorInfo();
                    throw new Exception('Error al insertar item de venta: ' . $errorInfo[2]);
                }
                
                error_log('SalesManager::createSale - Sale item inserted successfully');
                
                // Actualizar stock del producto
                $updateStockQuery = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateStockQuery);
                $updateResult = $updateStmt->execute([$quantity, $productId]);
                
                if (!$updateResult) {
                    $errorInfo = $updateStmt->errorInfo();
                    throw new Exception('Error al actualizar stock: ' . $errorInfo[2]);
                }
                
                error_log('SalesManager::createSale - Stock updated for product ' . $productId);
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
