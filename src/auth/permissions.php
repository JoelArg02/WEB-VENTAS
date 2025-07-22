<?php
class PermissionManager {
    
    public static function getRolePermissions($role) {
        $permissions = [
            'admin' => [
                'dashboard' => true,
                'users' => true,
                'products' => true,
                'categories' => true,
                'sales' => true,
                'reports' => true,
                'create_user' => true,
                'edit_user' => true,
                'delete_user' => true,
                'create_product' => true,
                'edit_product' => true,
                'delete_product' => true,
                'create_category' => true,
                'edit_category' => true,
                'delete_category' => true,
                'create_sale' => true,
                'view_reports' => true,
                'export_reports' => true
            ],
            'vendedor' => [
                'dashboard' => true,
                'users' => false,
                'products' => true, // Solo lectura
                'categories' => true, // Solo lectura
                'sales' => true,
                'reports' => true,
                'create_user' => false,
                'edit_user' => false,
                'delete_user' => false,
                'create_product' => false,
                'edit_product' => false,
                'delete_product' => false,
                'create_category' => false,
                'edit_category' => false,
                'delete_category' => false,
                'create_sale' => true,
                'view_reports' => true,
                'export_reports' => false
            ],
            'bodega' => [
                'dashboard' => true,
                'users' => false,
                'products' => true,
                'categories' => true,
                'sales' => false,
                'reports' => true,
                'create_user' => false,
                'edit_user' => false,
                'delete_user' => false,
                'create_product' => true,
                'edit_product' => true,
                'delete_product' => true,
                'create_category' => true,
                'edit_category' => true,
                'delete_category' => true,
                'create_sale' => false,
                'view_reports' => true,
                'export_reports' => false
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
    
    public static function hasPermission($userRole, $permission) {
        $permissions = self::getRolePermissions($userRole);
        return $permissions[$permission] ?? false;
    }
    
    public static function requirePermission($userRole, $permission) {
        if (!self::hasPermission($userRole, $permission)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ]);
            } else {
                echo '<h1>Acceso Denegado</h1><p>No tienes permisos para acceder a esta función.</p>';
            }
            exit();
        }
    }
    
    public static function getVisibleTabs($userRole) {
        $permissions = self::getRolePermissions($userRole);
        $tabs = [];
        
        if ($permissions['dashboard']) {
            $tabs[] = ['id' => 'dashboard', 'name' => 'Dashboard'];
        }
        
        if ($permissions['users']) {
            $tabs[] = ['id' => 'users', 'name' => 'Usuarios'];
        }
        
        if ($permissions['products']) {
            $tabs[] = ['id' => 'products', 'name' => 'Productos'];
        }
        
        if ($permissions['categories']) {
            $tabs[] = ['id' => 'categories', 'name' => 'Categorías'];
        }
        
        if ($permissions['sales']) {
            $tabs[] = ['id' => 'sales', 'name' => 'Ventas'];
        }
        
        if ($permissions['reports']) {
            $tabs[] = ['id' => 'reports', 'name' => 'Reportes'];
        }
        
        return $tabs;
    }
}
?>
