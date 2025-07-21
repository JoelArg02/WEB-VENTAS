<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $isConnected = $database->testConnection();
    
    if ($isConnected) {
        echo json_encode([
            'success' => true,
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'status' => 'unhealthy',
            'database' => 'disconnected',
            'message' => 'No se pudo conectar a la base de datos',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'database' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
