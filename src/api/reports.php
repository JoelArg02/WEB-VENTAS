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
    
    switch ($type) {
        case 'sales':
            $data = $reportsManager->getDetailedSales(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            generateReportFile($data, $format, 'Reporte de Ventas', 'ventas');
            break;
            
        case 'products':
            $data = $reportsManager->getBestSellingProducts(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            generateReportFile($data, $format, 'Reporte de Productos', 'productos');
            break;
            
        case 'low_stock':
            $data = $reportsManager->getLowStockProducts();
            generateReportFile($data, $format, 'Reporte de Stock Bajo', 'stock_bajo');
            break;
            
        case 'categories':
            $data = $reportsManager->getCategoriesStats(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            generateReportFile($data, $format, 'Reporte por Categorías', 'categorias');
            break;
            
        case 'sellers':
            $data = $reportsManager->getSellersStats(
                $filters['from'] ?? null, 
                $filters['to'] ?? null
            );
            generateReportFile($data, $format, 'Reporte de Vendedores', 'vendedores');
            break;
            
        case 'expiring':
            $data = $reportsManager->getExpiringProducts();
            generateReportFile($data, $format, 'Productos Próximos a Caducar', 'proximos_caducar');
            break;
            
        default:
            throw new Exception('Tipo de reporte no válido');
    }
    
} catch (Exception $e) {
    error_log("Error in reports API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateReportFile($data, $format, $title, $filename) {
    if ($format === 'pdf') {
        generatePDF($data, $title, $filename);
    } elseif ($format === 'excel') {
        generateExcel($data, $title, $filename);
    }
}

function generatePDF($data, $title, $filename) {
    // Generar PDF simple usando HTML y CSS
    $html = generatePDFContent($data, $title);
    
    // Headers para forzar descarga como PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Usar wkhtmltopdf o similar, por ahora HTML con estilo PDF
    echo $html;
    exit;
}

function generateExcel($data, $title, $filename) {
    // Headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Generar contenido Excel básico
    echo "\xEF\xBB\xBF"; // BOM para UTF-8
    echo generateExcelContent($data, $title);
    exit;
}

function generateHTMLTable($data, $title) {
    if (empty($data)) {
        return "<p>No hay datos disponibles para mostrar.</p>";
    }
    
    $html = "<table>";
    
    // Headers de la tabla
    $html .= "<thead><tr>";
    $firstRow = reset($data);
    foreach (array_keys($firstRow) as $header) {
        $html .= "<th>" . ucfirst(str_replace('_', ' ', $header)) . "</th>";
    }
    $html .= "</tr></thead>";
    
    // Datos de la tabla
    $html .= "<tbody>";
    foreach ($data as $row) {
        $html .= "<tr>";
        foreach ($row as $cell) {
            $html .= "<td>" . htmlspecialchars($cell ?? '') . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody>";
    
    $html .= "</table>";
    
    return $html;
}

function generatePDFContent($data, $title) {
    $html = "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$title</title>
        <style>
            @page { margin: 2cm; }
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .title { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 10px; }
            .date { font-size: 10px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='title'>$title</div>
            <div class='date'>Generado el: " . date('d/m/Y H:i:s') . "</div>
        </div>";
    
    $html .= generateHTMLTable($data, $title);
    
    $html .= "
        <div class='footer'>
            <p>Sistema de Ventas - Reporte generado automáticamente</p>
        </div>
    </body>
    </html>";
    
    return $html;
}

function generateExcelContent($data, $title) {
    if (empty($data)) {
        return "<table><tr><td>No hay datos disponibles</td></tr></table>";
    }
    
    $content = "<table border='1'>";
    $content .= "<tr><td colspan='" . count(array_keys(reset($data))) . "' style='text-align:center;font-weight:bold;font-size:16px;'>$title</td></tr>";
    $content .= "<tr><td colspan='" . count(array_keys(reset($data))) . "' style='text-align:center;'>Generado el: " . date('d/m/Y H:i:s') . "</td></tr>";
    $content .= "<tr><td></td></tr>"; // Espacio
    
    // Headers
    $content .= "<tr style='background-color:#f2f2f2;font-weight:bold;'>";
    $firstRow = reset($data);
    foreach (array_keys($firstRow) as $header) {
        $content .= "<td>" . ucfirst(str_replace('_', ' ', $header)) . "</td>";
    }
    $content .= "</tr>";
    
    // Datos
    foreach ($data as $row) {
        $content .= "<tr>";
        foreach ($row as $cell) {
            $content .= "<td>" . htmlspecialchars($cell ?? '') . "</td>";
        }
        $content .= "</tr>";
    }
    
    $content .= "</table>";
    return $content;
}
?>
                
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
