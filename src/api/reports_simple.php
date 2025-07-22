<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/ReportsManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $type = $input['type'] ?? '';
    $format = $input['format'] ?? 'pdf';
    $filters = $input['filters'] ?? [];
    
    $reportsManager = new ReportsManager();
    $data = [];
    $title = '';
    $filename = '';
    
    // Obtener datos según el tipo de reporte
    switch ($type) {
        case 'sales':
            $data = $reportsManager->getDetailedSales(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            $title = 'Reporte de Ventas';
            $filename = 'reporte_ventas';
            break;
            
        case 'products':
            $data = $reportsManager->getBestSellingProducts(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            $title = 'Reporte de Productos Más Vendidos';
            $filename = 'reporte_productos';
            break;
            
        case 'low_stock':
            $data = $reportsManager->getLowStockProducts();
            $title = 'Reporte de Stock Bajo';
            $filename = 'reporte_stock_bajo';
            break;
            
        case 'categories':
            $data = $reportsManager->getCategoriesStats(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            $title = 'Reporte por Categorías';
            $filename = 'reporte_categorias';
            break;
            
        case 'sellers':
            $data = $reportsManager->getSellersStats(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            $title = 'Reporte de Vendedores';
            $filename = 'reporte_vendedores';
            break;
            
        case 'expiring':
            $data = $reportsManager->getExpiringProducts();
            $title = 'Productos Próximos a Caducar';
            $filename = 'reporte_proximos_caducar';
            break;
            
        default:
            throw new Exception('Tipo de reporte no válido');
    }
    
    // Generar el archivo según el formato
    if ($format === 'pdf') {
        generateSimplePDF($data, $title, $filename);
    } elseif ($format === 'excel') {
        generateSimpleExcel($data, $title, $filename);
    } else {
        throw new Exception('Formato no válido');
    }
    
} catch (Exception $e) {
    error_log("Error in reports API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateSimplePDF($data, $title, $filename) {
    // Crear contenido HTML optimizado para impresión/PDF
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            @media print {
                body { margin: 0; }
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .date {
                font-size: 10px;
                color: #666;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 4px;
                text-align: left;
                font-size: 10px;
            }
            th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .summary {
                margin-top: 20px;
                text-align: right;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">' . htmlspecialchars($title) . '</div>
            <div class="date">Generado el: ' . date('d/m/Y H:i:s') . '</div>
        </div>';
    
    if (empty($data)) {
        $html .= '<p>No hay datos disponibles para mostrar.</p>';
    } else {
        $html .= '<table>';
        
        // Headers
        $html .= '<thead><tr>';
        $firstRow = reset($data);
        foreach (array_keys($firstRow) as $header) {
            $html .= '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $header))) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Datos
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $cell) {
                $value = $cell ?? '';
                // Formatear valores monetarios
                if (strpos($key, 'amount') !== false || strpos($key, 'price') !== false || strpos($key, 'revenue') !== false) {
                    $value = '$' . number_format($value, 2);
                }
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        
        $html .= '<div class="summary">Total de registros: ' . count($data) . '</div>';
    }
    
    $html .= '</body></html>';
    
    // Headers para descarga
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.html"');
    echo $html;
    exit;
}

function generateSimpleExcel($data, $title, $filename) {
    // Headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xls"');
    
    // BOM para UTF-8
    echo "\xEF\xBB\xBF";
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    echo '<Worksheet ss:Name="' . htmlspecialchars($title) . '">' . "\n";
    echo '<Table>' . "\n";
    
    // Título
    if (!empty($data)) {
        $colCount = count(array_keys(reset($data)));
        echo '<Row><Cell ss:MergeAcross="' . ($colCount - 1) . '"><Data ss:Type="String">' . htmlspecialchars($title) . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:MergeAcross="' . ($colCount - 1) . '"><Data ss:Type="String">Generado el: ' . date('d/m/Y H:i:s') . '</Data></Cell></Row>' . "\n";
        echo '<Row></Row>' . "\n"; // Fila vacía
        
        // Headers
        echo '<Row>';
        $firstRow = reset($data);
        foreach (array_keys($firstRow) as $header) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $header))) . '</Data></Cell>';
        }
        echo '</Row>' . "\n";
        
        // Datos
        foreach ($data as $row) {
            echo '<Row>';
            foreach ($row as $key => $cell) {
                $value = $cell ?? '';
                $type = 'String';
                
                // Detectar números
                if (is_numeric($value)) {
                    $type = 'Number';
                }
                
                echo '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($value) . '</Data></Cell>';
            }
            echo '</Row>' . "\n";
        }
    } else {
        echo '<Row><Cell><Data ss:Type="String">No hay datos disponibles</Data></Cell></Row>' . "\n";
    }
    
    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    
    exit;
}
?>
