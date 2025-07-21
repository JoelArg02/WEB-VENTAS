<?php
require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/SalesManager.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();

header('Content-Type: application/json');

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
    PermissionManager::requirePermission($userData['role'], 'create_sale');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Agregar el ID del usuario actual
    $input['user_id'] = $userData['id'];
    
    try {
        $salesManager = new SalesManager();
        $result = $salesManager->createSale($input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Venta registrada exitosamente' : 'Error al registrar venta'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
