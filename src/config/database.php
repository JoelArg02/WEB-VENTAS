<?php
class Database {
    private $host = 'mysql';
    private $db_name = 'mydatabase';
    private $username = 'myuser';
    private $password = 'mypassword';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos. Por favor verifica que el servidor esté disponible.");
        }
        
        return $this->conn;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT 1");
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Crear instancia global para APIs
$database = new Database();
$pdo = $database->getConnection();
?>
