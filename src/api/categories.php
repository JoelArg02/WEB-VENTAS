<?php
require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/CategoryManager.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    PermissionManager::requirePermission($userData['role'], 'categories');
    
    try {
        $categoryManager = new CategoryManager();
        
        // Si se pide una categoría específica
        if (isset($_GET['id'])) {
            $category = $categoryManager->getCategoryById($_GET['id']);
            echo json_encode([
                'success' => true,
                'data' => $category
            ]);
        }
        // Si se piden solo las activas
        elseif (isset($_GET['active_only'])) {
            $categories = $categoryManager->getActiveCategories();
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
        }
        // Todas las categorías
        else {
            $categories = $categoryManager->getAllCategories();
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        PermissionManager::requirePermission($userData['role'], 'create_category');
        
        $inputRaw = file_get_contents('php://input');
        $input = json_decode($inputRaw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido recibido: ' . json_last_error_msg());
        }

        $categoryManager = new CategoryManager();
        $result = $categoryManager->createCategory($input);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Categoría creada exitosamente' : 'Error al crear categoría'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }


    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    PermissionManager::requirePermission($userData['role'], 'edit_category');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
        exit;
    }
    
    try {
        $categoryManager = new CategoryManager();
        $result = $categoryManager->updateCategory($id, $input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Categoría actualizada exitosamente' : 'Error al actualizar categoría'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    PermissionManager::requirePermission($userData['role'], 'delete_category');
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
        exit;
    }
    
    try {
        $categoryManager = new CategoryManager();
        $result = $categoryManager->deleteCategory($id);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Categoría eliminada exitosamente' : 'Error al eliminar categoría'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
