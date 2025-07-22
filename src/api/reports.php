<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../models/ReportsManager.php';

// Verificar autenticación
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Manejar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $reportsManager = new ReportsManager();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            handleReportGeneration($reportsManager);
            break;
        case 'GET':
            handleReportExport($reportsManager);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleReportGeneration($reportsManager) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }
    
    $type = $input['type'] ?? '';
    $dateFrom = $input['dateFrom'] ?? null;
    $dateTo = $input['dateTo'] ?? null;
    $categoryId = $input['categoryId'] ?? null;
    
    // Validar tipo de reporte
    $validTypes = ['sales', 'products', 'inventory', 'categories'];
    if (!in_array($type, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de reporte inválido']);
        return;
    }
    
    try {
        $data = generateReportData($reportsManager, $type, $dateFrom, $dateTo, $categoryId);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        error_log("Error generating report: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al generar el reporte']);
    }
}

function generateReportData($reportsManager, $type, $dateFrom, $dateTo, $categoryId) {
    switch ($type) {
        case 'sales':
            return generateSalesReport($reportsManager, $dateFrom, $dateTo);
            
        case 'products':
            return generateProductsReport($reportsManager, $dateFrom, $dateTo, $categoryId);
            
        case 'inventory':
            return generateInventoryReport($reportsManager, $categoryId);
            
        case 'categories':
            return generateCategoriesReport($reportsManager, $dateFrom, $dateTo);
            
        default:
            throw new Exception('Tipo de reporte no soportado');
    }
}

function generateSalesReport($reportsManager, $dateFrom, $dateTo) {
    // Obtener resumen de ventas
    $summary = $reportsManager->getBusinessSummary($dateFrom, $dateTo);
    
    // Obtener ventas detalladas
    $details = $reportsManager->getDetailedSales($dateFrom, $dateTo);
    
    return [
        'summary' => $summary,
        'details' => $details,
        'type' => 'sales',
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ]
    ];
}

function generateProductsReport($reportsManager, $dateFrom, $dateTo, $categoryId) {
    // Obtener productos más vendidos
    $bestSelling = $reportsManager->getBestSellingProducts($dateFrom, $dateTo, 50);
    
    // Filtrar por categoría si se especifica
    if ($categoryId) {
        $bestSelling = array_filter($bestSelling, function($product) use ($categoryId) {
            // Verificar si existe category_id en el producto o usar el name de la categoría
            return (isset($product['category_id']) && $product['category_id'] == $categoryId) ||
                   (isset($product['category_name']) && !empty($product['category_name']));
        });
        $bestSelling = array_values($bestSelling); // Reindexar el array
    }
    
    // Calcular resumen
    $totalProducts = count($bestSelling);
    $totalSold = array_sum(array_column($bestSelling, 'total_sold'));
    $totalRevenue = array_sum(array_column($bestSelling, 'total_revenue'));
    
    return [
        'summary' => [
            'total_products' => $totalProducts,
            'total_sold' => $totalSold,
            'total_revenue' => $totalRevenue
        ],
        'products' => $bestSelling,
        'type' => 'products',
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ],
        'category_id' => $categoryId
    ];
}

function generateInventoryReport($reportsManager, $categoryId) {
    // Obtener reporte completo de inventario
    $inventory = $reportsManager->getInventoryReport();
    
    // Filtrar por categoría si se especifica
    if ($categoryId) {
        $inventory = array_filter($inventory, function($product) use ($categoryId) {
            // Verificar si existe category_id en el producto o usar el name de la categoría
            return (isset($product['category_id']) && $product['category_id'] == $categoryId) ||
                   (isset($product['category_name']) && !empty($product['category_name']));
        });
        $inventory = array_values($inventory); // Reindexar el array
    }
    
    // Calcular resumen
    $totalProducts = count($inventory);
    $totalStockValue = array_sum(array_column($inventory, 'stock_value'));
    $totalStock = array_sum(array_column($inventory, 'stock'));
    $lowStockCount = count(array_filter($inventory, function($product) {
        return $product['stock'] <= 10;
    }));
    
    return [
        'summary' => [
            'total_products' => $totalProducts,
            'total_stock_value' => $totalStockValue,
            'total_stock' => $totalStock,
            'low_stock_count' => $lowStockCount
        ],
        'inventory' => $inventory,
        'type' => 'inventory',
        'category_id' => $categoryId
    ];
}

function generateCategoriesReport($reportsManager, $dateFrom, $dateTo) {
    // Obtener estadísticas por categorías
    $categories = $reportsManager->getCategoriesStats($dateFrom, $dateTo);
    
    // Calcular resumen
    $totalCategories = count($categories);
    $totalRevenue = array_sum(array_column($categories, 'total_revenue'));
    $totalProducts = array_sum(array_column($categories, 'total_products'));
    
    return [
        'summary' => [
            'total_categories' => $totalCategories,
            'total_revenue' => $totalRevenue,
            'total_products' => $totalProducts
        ],
        'categories' => $categories,
        'type' => 'categories',
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ]
    ];
}

function handleReportExport($reportsManager) {
    // TODO: Implementar exportación a PDF y Excel
    http_response_code(501);
    echo json_encode(['success' => false, 'message' => 'Exportación no implementada aún']);
}
?>
