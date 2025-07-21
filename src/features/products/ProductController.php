<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/permissions.php';
require_once __DIR__ . '/ProductService.php';

class ProductController {
    private $productService;
    
    public function __construct() {
        $this->productService = new ProductService();
    }
    
    public function handleRequest() {
        $userData = SessionManager::getUserData();
        
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    return $this->handleGet($userData);
                case 'POST':
                    return $this->handlePost($userData);
                case 'PUT':
                    return $this->handlePut($userData);
                case 'DELETE':
                    return $this->handleDelete($userData);
                default:
                    throw new Exception('Método no permitido');
            }
        } catch (Exception $e) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function handleGet($userData) {
        PermissionManager::requirePermission($userData['role'], 'products');
        
        if (isset($_GET['low_stock'])) {
            $products = $this->productService->getLowStockProducts();
        } else {
            $products = $this->productService->getAllProducts();
        }
        
        return [
            'success' => true,
            'data' => $products
        ];
    }
    
    private function handlePost($userData) {
        PermissionManager::requirePermission($userData['role'], 'create_product');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('No se recibieron datos válidos');
        }
        
        return $this->productService->createProduct($input);
    }
    
    private function handlePut($userData) {
        PermissionManager::requirePermission($userData['role'], 'edit_product');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        return $this->productService->updateProduct($id, $input);
    }
    
    private function handleDelete($userData) {
        PermissionManager::requirePermission($userData['role'], 'delete_product');
        
        $id = $_GET['id'] ?? null;
        
        return $this->productService->deleteProduct($id);
    }
}
