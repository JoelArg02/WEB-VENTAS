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
    $dateFrom = $input['date_from'] ?? null;
    $dateTo = $input['date_to'] ?? null;
    $categoryId = $input['category_id'] ?? null;
    $sellerId = $input['seller_id'] ?? null;
    $minStock = $input['min_stock'] ?? null;
    $quickReport = $input['quick_report'] ?? null;
    $stockStatus = $input['stock_status'] ?? null;
    $expiring = $input['expiring'] ?? false;
    
    $validTypes = ['sales', 'products', 'inventory', 'categories'];
    if (!in_array($type, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de reporte inválido']);
        return;
    }
    
    try {
        if ($quickReport) {
            $data = generateQuickReportData($reportsManager, $quickReport, $dateFrom, $dateTo, $stockStatus, $expiring);
        } else {
            $data = generateReportData($reportsManager, $type, $dateFrom, $dateTo, $categoryId, $sellerId, $minStock);
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        error_log("Error generating report: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al generar el reporte']);
    }
}

function generateReportData($reportsManager, $type, $dateFrom, $dateTo, $categoryId, $sellerId = null, $minStock = null) {
    switch ($type) {
        case 'sales':
            return generateSalesReport($reportsManager, $dateFrom, $dateTo, $sellerId, $categoryId, $minStock);
            
        case 'products':
            return generateProductsReport($reportsManager, $dateFrom, $dateTo, $categoryId, $minStock);
            
        case 'inventory':
            return generateInventoryReport($reportsManager, $categoryId, $minStock);
            
        case 'categories':
            return generateCategoriesReport($reportsManager, $dateFrom, $dateTo);
            
        default:
            throw new Exception('Tipo de reporte no soportado');
    }
}

function generateSalesReport($reportsManager, $dateFrom, $dateTo, $sellerId = null, $categoryId = null, $minStock = null) {
    // Obtener resumen de ventas
    $summary = $reportsManager->getBusinessSummary($dateFrom, $dateTo, $sellerId);
    // Obtener ventas detalladas
    $details = $reportsManager->getDetailedSales($dateFrom, $dateTo, $sellerId, $categoryId);
    
    // Filtrar por stock mínimo si se especifica
    if ($minStock !== null && $minStock > 0) {
        $details = array_filter($details, function($sale) use ($minStock) {
            return isset($sale['stock']) && $sale['stock'] >= $minStock;
        });
        $details = array_values($details);
    }
    
    return [
        'summary' => $summary,
        'details' => $details,
        'type' => 'sales',
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ],
        'seller_id' => $sellerId,
        'category_id' => $categoryId,
        'min_stock' => $minStock
    ];
}

function generateProductsReport($reportsManager, $dateFrom, $dateTo, $categoryId, $minStock = null) {
    $bestSelling = $reportsManager->getBestSellingProducts($dateFrom, $dateTo, 50);
    
    if ($categoryId) {
        $bestSelling = array_filter($bestSelling, function($product) use ($categoryId) {
            return (isset($product['category_id']) && $product['category_id'] == $categoryId) ||
                   (isset($product['category_name']) && !empty($product['category_name']));
        });
        $bestSelling = array_values($bestSelling);
    }
    
    if ($minStock !== null && $minStock > 0) {
        $bestSelling = array_filter($bestSelling, function($product) use ($minStock) {
            return isset($product['stock']) && $product['stock'] >= $minStock;
        });
        $bestSelling = array_values($bestSelling);
    }
    
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
        'category_id' => $categoryId,
        'min_stock' => $minStock
    ];
}

function generateInventoryReport($reportsManager, $categoryId, $minStock = null) {
    $inventory = $reportsManager->getInventoryReport();
    
    if ($categoryId) {
        $inventory = array_filter($inventory, function($product) use ($categoryId) {
            return (isset($product['category_id']) && $product['category_id'] == $categoryId) ||
                   (isset($product['category_name']) && !empty($product['category_name']));
        });
        $inventory = array_values($inventory);
    }
    
    if ($minStock !== null && $minStock > 0) {
        $inventory = array_filter($inventory, function($product) use ($minStock) {
            return isset($product['stock']) && $product['stock'] >= $minStock;
        });
        $inventory = array_values($inventory);
    }
    
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
        'category_id' => $categoryId,
        'min_stock' => $minStock
    ];
}

function generateQuickReportData($reportsManager, $quickReport, $dateFrom, $dateTo, $stockStatus, $expiring) {
    switch($quickReport) {
        case 'daily_sales':
            $dateFrom = $dateTo = date('Y-m-d');
            return generateSalesReport($reportsManager, $dateFrom, $dateTo);
            
        case 'weekly_sales':
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            $dateTo = date('Y-m-d');
            return generateSalesReport($reportsManager, $dateFrom, $dateTo);
            
        case 'monthly_sales':
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
            return generateSalesReport($reportsManager, $dateFrom, $dateTo);
            
        case 'yearly_sales':
            $dateFrom = date('Y-01-01');
            $dateTo = date('Y-12-31');
            return generateSalesReport($reportsManager, $dateFrom, $dateTo);
            
        case 'sales_today':
        case 'sales_month':
            return generateSalesReport($reportsManager, $dateFrom, $dateTo);
            
        case 'low_stock':
        case 'out_of_stock':
            $inventory = $reportsManager->getLowStockProducts(10);
            return [
                'summary' => [
                    'total_products' => count($inventory),
                    'total_stock_value' => array_sum(array_column($inventory, 'stock_value')),
                    'total_stock' => array_sum(array_column($inventory, 'stock')),
                    'low_stock_count' => count($inventory)
                ],
                'inventory' => $inventory,
                'type' => 'inventory'
            ];
            
        case 'product_performance':
            $dateFrom = $dateFrom ?: date('Y-m-01');
            $dateTo = $dateTo ?: date('Y-m-t');
            return generateProductsReport($reportsManager, $dateFrom, $dateTo, null);
            
        case 'expiring_products':
            $expiring = $reportsManager->getExpiringProducts();
            return [
                'summary' => [
                    'total_products' => count($expiring),
                    'expiring_soon' => count($expiring)
                ],
                'products' => $expiring,
                'type' => 'products'
            ];
            
        default:
            throw new Exception('Tipo de reporte rápido no soportado: ' . $quickReport);
    }
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
    $format = $_GET['format'] ?? '';
    $type = $_GET['type'] ?? '';
    
    if (!$format || !$type) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato y tipo de reporte requeridos']);
        return;
    }
    
    try {
        // Obtener parámetros del reporte
        $dateFrom = $_GET['dateFrom'] ?? null;
        $dateTo = $_GET['dateTo'] ?? null;
        $categoryId = $_GET['categoryId'] ?? null;
        $quickReport = $_GET['quickReport'] ?? null;
        $stockStatus = $_GET['stockStatus'] ?? null;
        $expiring = $_GET['expiring'] ?? false;
        
        // Generar datos del reporte
        if ($quickReport) {
            $data = generateQuickReportData($reportsManager, $quickReport, $dateFrom, $dateTo, $stockStatus, $expiring);
        } else {
            $data = generateReportData($reportsManager, $type, $dateFrom, $dateTo, $categoryId);
        }
        
        if ($format === 'pdf') {
            generatePDFExport($type, $data);
        } elseif ($format === 'excel') {
            generateExcelExport($type, $data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato no soportado']);
        }
        
    } catch (Exception $e) {
        error_log("Error in export: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al generar la exportación']);
    }
}

function generatePDFExport($type, $data) {
    // Esta función será manejada por el frontend con jsPDF
    // Devolvemos los datos para que el frontend los procese
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data, 'type' => $type]);
}

function generateExcelExport($type, $data) {
    // Esta función será manejada por el frontend con SheetJS
    // Devolvemos los datos para que el frontend los procese
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data, 'type' => $type]);
}
?>
