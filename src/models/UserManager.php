<?php
require_once __DIR__ . '/../config/database.php';

class UserManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllUsers() {
        try {
            $query = "SELECT id, name, email, phone, role, status, reason FROM users ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('Error al obtener usuarios: ' . $e->getMessage());
        }
    }
    
    public function getUserByEmail($email) {
        try {
            $query = "SELECT id, name, email, phone, role, status, reason FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception('Usuario no encontrado');
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Error al buscar usuario: ' . $e->getMessage());
        }
    }
    
    public function createUser($data) {
        try {
            // Verificar que la conexión esté activa
            if (!$this->conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            // Validar que el hash de password funcione
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            if (!$hashedPassword) {
                throw new Exception('Error al procesar la contraseña');
            }
            
            $query = "INSERT INTO users (name, email, phone, password, role, status, reason) 
                     VALUES (:name, :email, :phone, :password, :role, :status, :reason)";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta SQL: ' . implode(', ', $this->conn->errorInfo()));
            }
            
            $phone = isset($data['phone']) && !empty($data['phone']) ? $data['phone'] : null;
            $status = isset($data['status']) ? $data['status'] : 1;
            $reason = isset($data['reason']) ? $data['reason'] : null;
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':reason', $reason);
            
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
                if (strpos($errorMessage, 'email') !== false) {
                    throw new Exception('El email ya está registrado en el sistema');
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
                throw new Exception('La tabla de usuarios no existe en la base de datos');
            } else {
                throw new Exception('Error de base de datos [' . $errorCode . ']: ' . $errorMessage);
            }
        } catch (Exception $e) {
            throw new Exception('Error al crear usuario: ' . $e->getMessage());
        }
    }
    
    public function updateUser($id, $data) {
        try {
            $query = "UPDATE users SET name = :name, email = :email, phone = :phone, role = :role, status = :status, reason = :reason WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone'] ?? null);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':reason', $data['reason'] ?? null);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al actualizar usuario: ' . $e->getMessage());
        }
    }
    
    public function deleteUser($id) {
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception('Error al eliminar usuario: ' . $e->getMessage());
        }
    }
}
?>
