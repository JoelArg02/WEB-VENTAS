<?php
require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/ProductManager.php';
require_once '../models/SalesManager.php';
require_once '../models/UserManager.php';
require_once '../config/database.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $database = new Database();
        if (!$database->testConnection()) {
            throw new Exception('No se pudo conectar a la base de datos. Verifica que el servidor MySQL esté ejecutándose.');
        }
        
        $dashboardData = [];
        
        if (PermissionManager::hasPermission($userData['role'], 'sales')) {
            try {
                $salesManager = new SalesManager();
                $dashboardData['sales_stats'] = $salesManager->getSalesStats();
            } catch (Exception $e) {
                $dashboardData['sales_stats'] = null;
                error_log('Error getting sales stats: ' . $e->getMessage());
            }
        }
        
        // Productos con stock bajo (si tiene permiso)
        if (PermissionManager::hasPermission($userData['role'], 'products')) {
            try {
                $productManager = new ProductManager();
                $dashboardData['low_stock_products'] = $productManager->getLowStockProducts();
                $dashboardData['expiring_products'] = $productManager->getExpiringProducts(30);
                
                // Contar total de productos
                $allProducts = $productManager->getAllProducts();
                $dashboardData['total_products'] = count($allProducts);
            } catch (Exception $e) {
                $dashboardData['low_stock_products'] = [];
                $dashboardData['expiring_products'] = [];
                $dashboardData['total_products'] = 0;
                error_log('Error getting products: ' . $e->getMessage());
            }
        }
        
        // Total de usuarios (solo admin)
        if (PermissionManager::hasPermission($userData['role'], 'users')) {
            try {
                $userManager = new UserManager();
                $allUsers = $userManager->getAllUsers();
                $dashboardData['total_users'] = count($allUsers);
            } catch (Exception $e) {
                $dashboardData['total_users'] = 0;
                error_log('Error getting users: ' . $e->getMessage());
            }
        }
        
        
        if (PermissionManager::hasPermission($userData['role'], 'sales')) {
            try {
                $dashboardData['recent_sales'] = $salesManager->getAllSales(5);
            } catch (Exception $e) {
                $dashboardData['recent_sales'] = [];
                error_log('Error getting recent sales: ' . $e->getMessage());
            }
        }
        
        // Permisos del usuario actual
        $dashboardData['permissions'] = PermissionManager::getRolePermissions($userData['role']);
        
        echo json_encode([
            'success' => true,
            'data' => $dashboardData
        ]);
        
    } catch (Exception $e) {
        error_log('Dashboard API Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
