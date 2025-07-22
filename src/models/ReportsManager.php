<?php
require_once __DIR__ . '/../config/database.php';

class ReportsManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Obtener estadísticas de ventas por periodo
    public function getSalesStats($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        COUNT(s.id) as total_sales,
                        SUM(s.total_amount) as total_revenue,
                        AVG(s.total_amount) as avg_sale_amount,
                        SUM(si.quantity) as total_items_sold,
                        DATE(s.sale_date) as sale_date
                    FROM sales s
                    LEFT JOIN sale_items si ON s.id = si.sale_id
                    WHERE s.status = 1";
            
            $params = [];
            
            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY DATE(s.sale_date) ORDER BY s.sale_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getSalesStats: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener ventas detalladas con información completa
    public function getDetailedSales($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        s.id,
                        s.client,
                        s.subtotal,
                        s.iva_amount,
                        s.total_amount,
                        s.money_received,
                        s.change_amount,
                        s.sale_date,
                        u.name as seller_name,
                        COUNT(si.id) as total_items,
                        GROUP_CONCAT(
                            CONCAT(p.name, ' (', si.quantity, ' x ', FORMAT(si.unit_price, 2), ')')
                            SEPARATOR ', '
                        ) as products_detail
                    FROM sales s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN sale_items si ON s.id = si.sale_id
                    LEFT JOIN products p ON si.product_id = p.id
                    WHERE s.status = 1";
            
            $params = [];
            
            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY s.id ORDER BY s.sale_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getDetailedSales: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener productos más vendidos
    public function getBestSellingProducts($dateFrom = null, $dateTo = null, $limit = 20) {
        try {
            $sql = "SELECT 
                        p.id,
                        p.name,
                        p.price,
                        p.stock,
                        p.image,
                        p.category_id,
                        c.name as category_name,
                        SUM(si.quantity) as total_sold,
                        SUM(si.quantity * si.unit_price) as total_revenue,
                        COUNT(DISTINCT s.id) as sales_count
                    FROM products p
                    LEFT JOIN sale_items si ON p.id = si.product_id
                    LEFT JOIN sales s ON si.sale_id = s.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 1";
            
            $params = [];
            
            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY p.id 
                     ORDER BY total_sold DESC, total_revenue DESC 
                     LIMIT ?";
            
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getBestSellingProducts: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener productos con stock bajo
    public function getLowStockProducts($threshold = 10) {
        try {
            $sql = "SELECT 
                        p.id,
                        p.name,
                        p.price,
                        p.stock,
                        p.image,
                        p.expiration_date,
                        p.category_id,
                        c.name as category_name,
                        (p.stock * p.price) as stock_value,
                        CASE 
                            WHEN p.stock <= 5 THEN 'Crítico'
                            WHEN p.stock <= 10 THEN 'Bajo'
                            ELSE 'Advertencia'
                        END as stock_status
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 1 AND p.stock <= ?
                    ORDER BY p.stock ASC, p.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$threshold]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getLowStockProducts: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener productos próximos a caducar
    public function getExpiringProducts($days = 30) {
        try {
            $sql = "SELECT 
                        p.id,
                        p.name,
                        p.price,
                        p.stock,
                        p.image,
                        p.expiration_date,
                        p.category_id,
                        c.name as category_name,
                        (p.stock * p.price) as stock_value,
                        DATEDIFF(p.expiration_date, CURDATE()) as days_until_expiration,
                        CASE 
                            WHEN DATEDIFF(p.expiration_date, CURDATE()) <= 7 THEN 'Crítico'
                            WHEN DATEDIFF(p.expiration_date, CURDATE()) <= 15 THEN 'Urgente'
                            WHEN DATEDIFF(p.expiration_date, CURDATE()) <= 30 THEN 'Próximo'
                            ELSE 'Normal'
                        END as expiration_status
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 1 
                        AND p.expiration_date IS NOT NULL 
                        AND DATEDIFF(p.expiration_date, CURDATE()) <= ?
                    ORDER BY p.expiration_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getExpiringProducts: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener estadísticas por categorías
    public function getCategoriesStats($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        c.id,
                        c.name as category_name,
                        COUNT(DISTINCT p.id) as total_products,
                        SUM(p.stock) as total_stock,
                        COALESCE(SUM(si.quantity), 0) as total_sold,
                        COALESCE(SUM(si.quantity * si.unit_price), 0) as total_revenue,
                        COUNT(DISTINCT s.id) as sales_count,
                        AVG(p.price) as avg_product_price
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
                    LEFT JOIN sale_items si ON p.id = si.product_id
                    LEFT JOIN sales s ON si.sale_id = s.id AND s.status = 1";
            
            $params = [];
            
            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " WHERE c.status = 1 
                     GROUP BY c.id 
                     ORDER BY total_revenue DESC, total_sold DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getCategoriesStats: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener estadísticas por vendedores
    public function getSellersStats($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        u.id,
                        u.name as seller_name,
                        u.role,
                        COUNT(s.id) as total_sales,
                        SUM(s.total_amount) as total_revenue,
                        AVG(s.total_amount) as avg_sale_amount,
                        SUM(si.quantity) as total_items_sold,
                        MAX(s.sale_date) as last_sale_date,
                        MIN(s.sale_date) as first_sale_date
                    FROM users u
                    LEFT JOIN sales s ON u.id = s.user_id AND s.status = 1
                    LEFT JOIN sale_items si ON s.id = si.sale_id";
            
            $params = [];
            
            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " WHERE u.status = 1 
                     GROUP BY u.id 
                     ORDER BY total_revenue DESC, total_sales DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getSellersStats: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener inventario completo
    public function getInventoryReport() {
        try {
            $sql = "SELECT 
                        p.id,
                        p.name,
                        p.price,
                        p.stock,
                        p.image,
                        p.expiration_date,
                        p.category_id,
                        c.name as category_name,
                        COALESCE(SUM(si.quantity), 0) as total_sold,
                        COALESCE(SUM(si.quantity * si.unit_price), 0) as total_revenue,
                        (p.stock * p.price) as stock_value,
                        CASE 
                            WHEN p.stock <= 5 THEN 'Stock Crítico'
                            WHEN p.stock <= 10 THEN 'Stock Bajo'
                            WHEN p.stock <= 20 THEN 'Stock Medio'
                            ELSE 'Stock Bueno'
                        END as stock_status
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN sale_items si ON p.id = si.product_id
                    WHERE p.status = 1
                    GROUP BY p.id
                    ORDER BY c.name, p.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getInventoryReport: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener resumen general del negocio
    public function getBusinessSummary($dateFrom = null, $dateTo = null) {
        try {
            // Total de ventas
            $sql = "SELECT 
                        COUNT(id) as total_sales,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as avg_sale_amount
                    FROM sales 
                    WHERE status = 1";
            
            $params = [];
            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE(sale_date) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Total de productos
            $stmt = $this->db->prepare("SELECT COUNT(id) as total_products, SUM(stock) as total_stock FROM products WHERE status = 1");
            $stmt->execute();
            $productsData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Categorías activas
            $stmt = $this->db->prepare("SELECT COUNT(id) as total_categories FROM categories WHERE status = 1");
            $stmt->execute();
            $categoriesData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Usuarios activos
            $stmt = $this->db->prepare("SELECT COUNT(id) as total_users FROM users WHERE status = 1");
            $stmt->execute();
            $usersData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return array_merge($salesData, $productsData, $categoriesData, $usersData);
            
        } catch (Exception $e) {
            error_log("Error in getBusinessSummary: " . $e->getMessage());
            return [];
        }
    }
}
?>
