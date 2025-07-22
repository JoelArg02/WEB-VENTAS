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
    
    public function getProductById($id) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('Error al obtener producto: ' . $e->getMessage());
        }
    }
    
    public function createProduct($data) {
        try {
            // Verificar que la conexión esté activa
            if (!$this->conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            $query = "INSERT INTO products (name, price, stock, image, category_id, status, expiration_date) 
                     VALUES (:name, :price, :stock, :image, :category_id, :status, :expiration_date)";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta SQL: ' . implode(', ', $this->conn->errorInfo()));
            }
            
            $image = isset($data['image']) && !empty($data['image']) ? $data['image'] : null;
            $status = isset($data['status']) ? $data['status'] : 1;
            $expiration_date = isset($data['expiry_date']) && !empty($data['expiry_date']) ? $data['expiry_date'] : null;
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock']);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':expiration_date', $expiration_date);
            
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Error al ejecutar la consulta: ' . $errorInfo[2]);
            }
            
            return $result;
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Errores específicos de MySQL/PDO
            if ($errorCode == 23000 && strpos($errorMessage, 'Duplicate entry') !== false) {
                if (strpos($errorMessage, 'name') !== false) {
                    throw new Exception('Ya existe un producto con este nombre');
                } else {
                    throw new Exception('Ya existe un registro con esos datos');
                }
            } elseif ($errorCode == 22001) {
                throw new Exception('Uno de los campos excede la longitud máxima permitida');
            } elseif ($errorCode == 23502) {
                throw new Exception('Falta información requerida (campo nulo)');
            } elseif (strpos($errorMessage, 'Unknown column') !== false) {
                throw new Exception('Error en la estructura de la base de datos');
            } elseif (strpos($errorMessage, 'Table') !== false && strpos($errorMessage, "doesn't exist") !== false) {
                throw new Exception('La tabla de productos no existe en la base de datos');
            } elseif ($errorCode == 23503) {
                throw new Exception('La categoría seleccionada no existe o no es válida');
            } else {
                throw new Exception('Error de base de datos [' . $errorCode . ']: ' . $errorMessage);
            }
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
