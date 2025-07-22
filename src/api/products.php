<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/ProductManager.php';
require_once '../models/CategoryManager.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        PermissionManager::requirePermission($userData['role'], 'products');
        
        $productManager = new ProductManager();
        
        if (isset($_GET['id'])) {
            // Obtener un producto específico por ID
            $product = $productManager->getProductById($_GET['id']);
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'data' => $product
                ]);
            } else {
                throw new Exception('Producto no encontrado');
            }
        } elseif (isset($_GET['low_stock'])) {
            $products = $productManager->getLowStockProducts();
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
        } else {
            $products = $productManager->getAllProducts();
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        PermissionManager::requirePermission($userData['role'], 'create_product');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Error: No se recibieron datos válidos.');
        }
        
        $errors = [];
        
        if (empty($input['name'])) {
            $errors[] = 'El campo "Nombre" es obligatorio';
        } elseif (strlen($input['name']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        }
        
        if (!isset($input['price']) || $input['price'] === '') {
            $errors[] = 'El campo "Precio" es obligatorio';
        } elseif (!is_numeric($input['price']) || floatval($input['price']) <= 0) {
            $errors[] = 'El precio debe ser un número mayor a 0';
        }
        
        if (!isset($input['stock']) || $input['stock'] === '') {
            $errors[] = 'El campo "Stock" es obligatorio';
        } elseif (!is_numeric($input['stock']) || intval($input['stock']) < 0) {
            $errors[] = 'El stock debe ser un número mayor o igual a 0';
        }
        
        if (empty($input['category_id'])) {
            $errors[] = 'El campo "Categoría" es obligatorio';
        } else {
            try {
                $categoryManager = new CategoryManager();
                $category = $categoryManager->getCategoryById($input['category_id']);
                if (!$category) {
                    $errors[] = 'La categoría seleccionada no existe';
                }
            } catch (Exception $e) {
                $errors[] = 'La categoría seleccionada no es válida';
            }
        }
        
        if (!empty($input['expiry_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', $input['expiry_date']);
            if (!$date || $date->format('Y-m-d') !== $input['expiry_date']) {
                $errors[] = 'El formato de fecha de vencimiento no es válido';
            } elseif ($date < new DateTime('today')) {
                $errors[] = 'La fecha de vencimiento no puede ser anterior a hoy';
            }
        }
        
        if (!empty($errors)) {
            throw new Exception('Errores de validación: ' . implode('. ', $errors));
        }
        
        $productManager = new ProductManager();
        $result = $productManager->createProduct($input);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Producto "' . $input['name'] . '" creado exitosamente'
            ]);
        } else {
            throw new Exception('Error: No se pudo crear el producto.');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        PermissionManager::requirePermission($userData['role'], 'edit_product');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID de producto requerido');
        }
        
        $productManager = new ProductManager();
        $result = $productManager->updateProduct($id, $input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Producto actualizado exitosamente' : 'Error al actualizar producto'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        PermissionManager::requirePermission($userData['role'], 'delete_product');
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID de producto requerido');
        }
        
        $productManager = new ProductManager();
        $result = $productManager->deleteProduct($id);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Producto eliminado exitosamente' : 'Error al eliminar producto'
        ]);
    } else {
        throw new Exception('Método no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
