<?php
require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/ProductManager.php';
require_once '../models/CategoryManager.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    PermissionManager::requirePermission($userData['role'], 'products');
    
    try {
        $productManager = new ProductManager();
        
        if (isset($_GET['low_stock'])) {
            $products = $productManager->getLowStockProducts();
        } else {
            $products = $productManager->getAllProducts();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionManager::requirePermission($userData['role'], 'create_product');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $productManager = new ProductManager();
        $result = $productManager->createProduct($input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Producto creado exitosamente' : 'Error al crear producto'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    PermissionManager::requirePermission($userData['role'], 'edit_product');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
        exit;
    }
    
    try {
        $productManager = new ProductManager();
        $result = $productManager->updateProduct($id, $input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Producto actualizado exitosamente' : 'Error al actualizar producto'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    PermissionManager::requirePermission($userData['role'], 'delete_product');
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
        exit;
    }
    
    try {
        $productManager = new ProductManager();
        $result = $productManager->deleteProduct($id);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Producto eliminado exitosamente' : 'Error al eliminar producto'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
