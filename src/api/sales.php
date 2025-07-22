<?php
// Suprimir todos los errores para evitar que interfieran con el JSON
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer de salida para capturar cualquier salida no deseada
ob_start();

// Establecer el header JSON inmediatamente
header('Content-Type: application/json');

try {
    require_once '../auth/session.php';
    require_once '../auth/permissions.php';
    require_once '../models/SalesManager.php';
    
    // Limpiar cualquier salida capturada
    if (ob_get_length()) ob_end_clean();
    
    // Volver a activar el logging de errores pero sin mostrarlos
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
} catch (Exception $e) {
    // Limpiar buffer si hay error
    if (ob_get_length()) ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar dependencias: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar autenticación para API
if (!SessionManager::isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción'
    ]);
    exit;
}

$userData = SessionManager::getUserData();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    PermissionManager::requirePermission($userData['role'], 'sales');
    
    try {
        $salesManager = new SalesManager();
        
        if (isset($_GET['stats'])) {
            $stats = $salesManager->getSalesStats();
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } elseif (isset($_GET['today'])) {
            $sales = $salesManager->getSalesToday();
            echo json_encode([
                'success' => true,
                'data' => $sales
            ]);
        } else {
            $limit = $_GET['limit'] ?? null;
            $sales = $salesManager->getAllSales($limit);
            echo json_encode([
                'success' => true,
                'data' => $sales
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar permisos
        if (!PermissionManager::hasPermission($userData['role'], 'create_sale')) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes permisos para crear ventas'
            ]);
            exit;
        }
        
        $rawInput = file_get_contents('php://input');
        error_log('Sales API - Raw input received: ' . $rawInput);
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                'success' => false,
                'message' => 'Error en el formato de datos JSON: ' . json_last_error_msg()
            ]);
            exit;
        }
        
        error_log('Sales API - Decoded input: ' . json_encode($input));
        
        // Validar campos requeridos
        $requiredFields = ['client', 'products', 'subtotal', 'iva_amount', 'total_amount', 'money_received', 'change_amount'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                echo json_encode([
                    'success' => false,
                    'message' => "Campo requerido faltante: $field"
                ]);
                exit;
            }
        }
        
        $input['user_id'] = $userData['id'];
        
        error_log('Sales API - Input with user_id: ' . json_encode($input));
        
        $salesManager = new SalesManager();
        $result = $salesManager->createSale($input);
        
        $response = [
            'success' => $result,
            'message' => $result ? 'Venta registrada exitosamente' : 'Error al registrar venta'
        ];
        
        error_log('Sales API - Response: ' . json_encode($response));
        echo json_encode($response);
    } catch (Exception $e) {
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        error_log('Sales API - Error response: ' . json_encode($errorResponse));
        echo json_encode($errorResponse);
    }
}
?>
