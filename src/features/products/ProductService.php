<?php
require_once __DIR__ . '/../../models/ProductManager.php';
require_once __DIR__ . '/ProductValidator.php';

class ProductService {
    private $productManager;
    
    public function __construct() {
        $this->productManager = new ProductManager();
    }
    
    public function getAllProducts() {
        return $this->productManager->getAllProducts();
    }
    
    public function getLowStockProducts() {
        return $this->productManager->getLowStockProducts();
    }
    
    public function createProduct($input) {
        $errors = ProductValidator::validateCreateProduct($input);
        
        if (!empty($errors)) {
            throw new Exception('Errores de validación: ' . implode('. ', $errors));
        }
        
        $result = $this->productManager->createProduct($input);
        
        if (!$result) {
            throw new Exception('Error: No se pudo crear el producto.');
        }
        
        return [
            'success' => true,
            'message' => 'Producto "' . $input['name'] . '" creado exitosamente'
        ];
    }
    
    public function updateProduct($id, $input) {
        if (!$id) {
            throw new Exception('ID de producto requerido');
        }
        
        $result = $this->productManager->updateProduct($id, $input);
        
        return [
            'success' => $result,
            'message' => $result ? 'Producto actualizado exitosamente' : 'Error al actualizar producto'
        ];
    }
    
    public function deleteProduct($id) {
        if (!$id) {
            throw new Exception('ID de producto requerido');
        }
        
        $result = $this->productManager->deleteProduct($id);
        
        return [
            'success' => $result,
            'message' => $result ? 'Producto eliminado exitosamente' : 'Error al eliminar producto'
        ];
    }
}
