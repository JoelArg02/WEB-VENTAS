<?php
require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/ProductManager.php';
require_once '../models/CategoryManager.php';
require_once '../models/SalesManager.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $reportType = $_GET['type'] ?? 'sales';
        $reportData = [];
        
        switch ($reportType) {
            case 'sales':
                if (PermissionManager::hasPermission($userData['role'], 'view_reports')) {
                    $salesManager = new SalesManager();
                    $reportData = [
                        'stats' => $salesManager->getSalesStats(),
                        'recent_sales' => $salesManager->getAllSales(20),
                        'today_sales' => $salesManager->getSalesToday()
                    ];
                }
                break;
                
            case 'inventory':
                if (PermissionManager::hasPermission($userData['role'], 'products')) {
                    $productManager = new ProductManager();
                    $reportData = [
                        'all_products' => $productManager->getAllProducts(),
                        'low_stock' => $productManager->getLowStockProducts(),
                        'categories_stats' => []
                    ];
                    
                    // Estadísticas por categoría
                    $categoryManager = new CategoryManager();
                    $categories = $categoryManager->getAllCategories();
                    foreach ($categories as $category) {
                        $reportData['categories_stats'][] = [
                            'name' => $category['name'],
                            'product_count' => $category['product_count']
                        ];
                    }
                }
                break;
                
            case 'users':
                if (PermissionManager::hasPermission($userData['role'], 'users')) {
                    $userManager = new UserManager();
                    $users = $userManager->getAllUsers();
                    $reportData = [
                        'total_users' => count($users),
                        'users_by_role' => []
                    ];
                    
                    // Contar usuarios por rol
                    $roleCount = [];
                    foreach ($users as $user) {
                        $role = $user['role'];
                        $roleCount[$role] = ($roleCount[$role] ?? 0) + 1;
                    }
                    $reportData['users_by_role'] = $roleCount;
                }
                break;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $reportData
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
