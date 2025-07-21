<?php
require_once '../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($email, $password) {
        try {
            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = :email AND status = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    return [
                        'success' => true,
                        'user' => $user
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Contraseña incorrecta'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado o inactivo'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ];
        }
    }
    
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
