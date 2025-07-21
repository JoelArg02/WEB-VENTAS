<?php
require_once 'auth/session.php';
require_once 'auth/permissions.php';
SessionManager::requireLogin();

$userData = SessionManager::getUserData();
$userPermissions = PermissionManager::getRolePermissions($userData['role']);
$visibleTabs = PermissionManager::getVisibleTabs($userData['role']);
$title = 'Dashboard - Sistema de Ventas';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($userData['name'], 0, 1)); ?>
                </div>
                <h1 class="text-xl font-bold text-gray-800">Dashboard - <?php echo ucfirst($userData['role']); ?></h1>
            </div>
            <div class="relative">
                <button onclick="toggleUserDropdown()" class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-800">
                    <span>Bienvenido, <?php echo htmlspecialchars($userData['name']); ?></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border">
                    <div class="py-1">
                        <button onclick="logout()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Cerrar Sesión
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white border-b">
        <div class="px-6">
            <div class="flex space-x-8" id="tabContainer">
                <?php foreach ($visibleTabs as $tab): ?>
                <button onclick="switchTab('<?php echo $tab['id']; ?>')" 
                        data-tab="<?php echo $tab['id']; ?>"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <?php echo $tab['name']; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="p-6">
        <div id="contentContainer">
            <!-- El contenido se carga aquí dinámicamente -->
        </div>
    </main>

    <!-- Modal para formularios -->
    <div id="modalOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div id="modalContent" class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
        </div>
    </div>

    <script>
        // Global error handler para capturar errores JavaScript
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', {
                message: e.message,
                filename: e.filename,
                line: e.lineno,
                column: e.colno,
                error: e.error
            });
        });

        // Global handler para promesas rechazadas
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', {
                reason: e.reason,
                promise: e.promise
            });
        });

        // Configuración global
        const currentUser = <?php echo json_encode($userData); ?>;
        const userPermissions = <?php echo json_encode($userPermissions); ?>;
        
        // Variables para datos
        let currentTab = localStorage.getItem('activeTab') || '<?php echo $visibleTabs[0]['id']; ?>';
        
        // Utilidades
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            }).format(amount || 0);
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit', 
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showMessage(message, type = 'info') {
            console.log(`[${type.toUpperCase()}] ${message}`);
            
            const colors = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700'
            };
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `fixed top-4 right-4 p-4 border-l-4 rounded shadow-lg z-50 ${colors[type]}`;
            messageDiv.innerHTML = `<p>${message}</p>`;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
        
        async function apiRequest(url, options = {}) {
            try {
                const response = await fetch(url, {
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    },
                    ...options
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success && result.message) {
                    showMessage(result.message, 'error');
                }
                
                return result;
            } catch (error) {
                console.error('API Request Error:', error);
                showMessage('Error de conexión al servidor: ' + error.message, 'error');
                return { success: false, message: error.message };
            }
        }
        
        // Gestión de pestañas
        function switchTab(tabId) {
            currentTab = tabId;
            localStorage.setItem('activeTab', tabId);
            
            // Actualizar estilo de pestañas
            document.querySelectorAll('[data-tab]').forEach(tab => {
                if (tab.dataset.tab === tabId) {
                    tab.className = 'py-4 px-1 border-b-2 font-medium text-sm transition-colors border-blue-500 text-blue-600';
                } else {
                    tab.className = 'py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                }
            });
            
            // Cargar contenido
            loadTabContent(tabId);
        }
        
        async function loadTabContent(tabId) {
            const container = document.getElementById('contentContainer');
            container.innerHTML = '<div class="flex justify-center items-center h-32"><div class="text-gray-500">Cargando...</div></div>';
            
            try {
                switch (tabId) {
                    case 'dashboard':
                        await loadDashboard();
                        break;
                    case 'users':
                        await loadUsers();
                        break;
                    case 'products':
                        await loadProducts();
                        break;
                    case 'categories':
                        await loadCategories();
                        break;
                    case 'sales':
                        await loadSales();
                        break;
                    case 'reports':
                        await loadReports();
                        break;
                    default:
                        container.innerHTML = '<div class="text-center text-gray-500">Sección no disponible</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="text-center text-red-500">Error al cargar el contenido</div>';
                console.error('Error loading tab content:', error);
            }
        }
        
        async function loadDashboard() {
            const container = document.getElementById('contentContainer');
            
            try {
                const dashboardResult = await apiRequest('api/dashboard.php');
                
                if (!dashboardResult || !dashboardResult.success) {
                    container.innerHTML = `
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <div class="text-red-500 text-xl mb-4">⚠️</div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Error de Conexión</h3>
                            <p class="text-gray-600 mb-4">No se pudo conectar con el servidor de datos</p>
                            <button onclick="loadDashboard()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Reintentar
                            </button>
                        </div>
                    `;
                    return;
                }
                
                const data = dashboardResult.data || {};
                let html = '<div class="space-y-6">';
                
                // Tarjetas de estadísticas
                html += '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">';
                
                if (data.sales_stats) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Hoy</h3>
                            <div class="text-3xl font-bold text-blue-600">${formatCurrency(data.sales_stats.today?.total || 0)}</div>
                            <p class="text-gray-600">${data.sales_stats.today?.count || 0} ventas</p>
                        </div>`;
                }
                
                if (data.total_products !== undefined) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Productos</h3>
                            <div class="text-3xl font-bold text-green-600">${data.total_products}</div>
                            <p class="text-gray-600">En inventario</p>
                        </div>`;
                }
                
                if (data.total_users !== undefined) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Usuarios</h3>
                            <div class="text-3xl font-bold text-purple-600">${data.total_users}</div>
                            <p class="text-gray-600">Registrados</p>
                        </div>`;
                }
                
                if (data.low_stock_products) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Stock Bajo</h3>
                            <div class="text-3xl font-bold text-red-600">${data.low_stock_products.length}</div>
                            <p class="text-gray-600">Productos</p>
                        </div>`;
                }
                
                html += '</div>';
            
            // Productos con stock bajo
            if (data.low_stock_products && data.low_stock_products.length > 0) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Productos con Stock Bajo</h3>
                        <div class="bg-yellow-50 border-yellow-200 text-yellow-800 border-l-4 p-4 mb-4 rounded">
                            <p>Los siguientes productos necesitan restock:</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Producto</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Stock</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Precio</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Categoría</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">`;
                
                data.low_stock_products.forEach(product => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">${product.name}</td>
                            <td class="px-4 py-2 text-sm text-red-600 font-semibold">${product.stock}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(product.price)}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${product.category_name || 'Sin categoría'}</td>
                        </tr>`;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>`;
            }
            
            // Ventas recientes
            if (data.recent_sales && data.recent_sales.length > 0) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Recientes</h3>
                        <div class="space-y-3">`;
                
                data.recent_sales.slice(0, 5).forEach(sale => {
                    html += `
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <span class="font-medium">${sale.product_name}</span>
                                <span class="text-sm text-gray-500">- ${sale.client}</span>
                            </div>
                            <span class="font-semibold">${formatCurrency(sale.price * sale.quantity)}</span>
                        </div>`;
                });
                
                html += `
                        </div>
                    </div>`;
            }
            
            html += '</div>';
            container.innerHTML = html;
            
            } catch (error) {
                console.error('Error loading dashboard:', error);
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow p-6 text-center">
                        <div class="text-red-500 text-xl mb-4">❌</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Error Inesperado</h3>
                        <p class="text-gray-600 mb-4">Ocurrió un error al cargar el dashboard</p>
                        <button onclick="loadDashboard()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Reintentar
                        </button>
                    </div>
                `;
            }
        }
        
        async function loadUsers() {
            const result = await apiRequest('/api/users.php');
            const container = document.getElementById('contentContainer');
            
            if (!result.success) return;
            
            let html = `
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h2>`;
                        
            if (userPermissions.create_user) {
                html += `<button onclick="openCreateUserModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Nuevo Usuario</button>`;
            }
            
            html += `
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Usuarios</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Rol</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Estado</th>`;
                                        
            if (userPermissions.edit_user || userPermissions.delete_user) {
                html += '<th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>';
            }
            
            html += `
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">`;
            
            result.data.forEach(user => {
                const statusClass = user.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                const statusText = user.status ? 'Activo' : 'Inactivo';
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900">${user.name}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${user.email}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${user.role}</td>
                        <td class="px-4 py-2 text-sm"><span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span></td>`;
                        
                if (userPermissions.edit_user || userPermissions.delete_user) {
                    html += '<td class="px-4 py-2 text-sm">';
                    if (userPermissions.edit_user) {
                        html += `<button onclick="editUser(${user.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    }
                    if (userPermissions.delete_user) {
                        html += `<button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    }
                    html += '</td>';
                }
                
                html += '</tr>';
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
            
            container.innerHTML = html;
        }
        
        async function loadProducts() {
            const result = await apiRequest('/api/products.php');
            const container = document.getElementById('contentContainer');
            
            if (!result.success) return;
            
            let html = `
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Gestión de Productos</h2>`;
                        
            if (userPermissions.create_product) {
                html += `<button onclick="openCreateProductModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Nuevo Producto</button>`;
            }
            
            html += `
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Productos</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Precio</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Stock</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Categoría</th>`;
                                        
            if (userPermissions.edit_product || userPermissions.delete_product) {
                html += '<th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>';
            }
            
            html += `
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">`;
            
            result.data.forEach(product => {
                const stockClass = product.stock <= 10 ? 'text-red-600 font-semibold' : 'text-gray-900';
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900">${product.name}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(product.price)}</td>
                        <td class="px-4 py-2 text-sm ${stockClass}">${product.stock}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${product.category_name || 'Sin categoría'}</td>`;
                        
                if (userPermissions.edit_product || userPermissions.delete_product) {
                    html += '<td class="px-4 py-2 text-sm">';
                    if (userPermissions.edit_product) {
                        html += `<button onclick="editProduct(${product.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    }
                    if (userPermissions.delete_product) {
                        html += `<button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    }
                    html += '</td>';
                }
                
                html += '</tr>';
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
            
            container.innerHTML = html;
        }
        
        async function loadCategories() {
            const result = await apiRequest('/api/categories.php');
            const container = document.getElementById('contentContainer');
            
            if (!result.success) return;
            
            let html = `
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Gestión de Categorías</h2>`;
                        
            if (userPermissions.create_category) {
                html += `<button onclick="openCreateCategoryModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Nueva Categoría</button>`;
            }
            
            html += `
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Categorías</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Estado</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Productos</th>`;
                                        
            if (userPermissions.edit_category || userPermissions.delete_category) {
                html += '<th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>';
            }
            
            html += `
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">`;
            
            result.data.forEach(category => {
                const statusClass = category.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                const statusText = category.status ? 'Activo' : 'Inactivo';
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900">${category.name}</td>
                        <td class="px-4 py-2 text-sm"><span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span></td>
                        <td class="px-4 py-2 text-sm text-gray-900">${category.product_count || 0}</td>`;
                        
                if (userPermissions.edit_category || userPermissions.delete_category) {
                    html += '<td class="px-4 py-2 text-sm">';
                    if (userPermissions.edit_category) {
                        html += `<button onclick="editCategory(${category.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    }
                    if (userPermissions.delete_category) {
                        html += `<button onclick="deleteCategory(${category.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    }
                    html += '</td>';
                }
                
                html += '</tr>';
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
            
            container.innerHTML = html;
        }
        
        async function loadSales() {
            const result = await apiRequest('/api/sales.php');
            const container = document.getElementById('contentContainer');
            
            if (!result.success) return;
            
            let html = `
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Registro de Ventas</h2>`;
                        
            if (userPermissions.create_sale) {
                html += `<button onclick="openCreateSaleModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Nueva Venta</button>`;
            }
            
            html += `
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Recientes</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Cliente</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Producto</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Cantidad</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Total</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Fecha</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Vendedor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">`;
            
            result.data.forEach(sale => {
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900">${sale.client}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${sale.product_name}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${sale.quantity}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(sale.price * sale.quantity)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${formatDate(sale.sale_date)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">${sale.user_name}</td>
                    </tr>`;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
            
            container.innerHTML = html;
        }
        
        async function loadReports() {
            const container = document.getElementById('contentContainer');
            
            let html = `
                <div class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-800">Reportes del Sistema</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <button onclick="loadReport('sales')" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow text-left">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Reporte de Ventas</h3>
                            <p class="text-gray-600">Estadísticas y análisis de ventas</p>
                        </button>
                        
                        <button onclick="loadReport('inventory')" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow text-left">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Reporte de Inventario</h3>
                            <p class="text-gray-600">Estado actual del inventario</p>
                        </button>`;
                        
            if (userPermissions.users) {
                html += `
                        <button onclick="loadReport('users')" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow text-left">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Reporte de Usuarios</h3>
                            <p class="text-gray-600">Estadísticas de usuarios del sistema</p>
                        </button>`;
            }
            
            html += `
                    </div>
                    
                    <div id="reportContainer" class="hidden">
                        <!-- Los reportes se cargan aquí -->
                    </div>
                </div>`;
            
            container.innerHTML = html;
        }
        
        async function loadReport(type) {
            const result = await apiRequest(`/api/reports.php?type=${type}`);
            const reportContainer = document.getElementById('reportContainer');
            
            if (!result.success) return;
            
            const data = result.data;
            let html = '';
            
            switch (type) {
                case 'sales':
                    html = `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Reporte de Ventas</h3>
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-blue-50 p-4 rounded">
                                    <h4 class="font-semibold">Ventas Hoy</h4>
                                    <p class="text-2xl font-bold text-blue-600">${formatCurrency(data.stats?.today?.total || 0)}</p>
                                    <p class="text-sm text-gray-600">${data.stats?.today?.count || 0} transacciones</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded">
                                    <h4 class="font-semibold">Ventas del Mes</h4>
                                    <p class="text-2xl font-bold text-green-600">${formatCurrency(data.stats?.month?.total || 0)}</p>
                                    <p class="text-sm text-gray-600">${data.stats?.month?.count || 0} transacciones</p>
                                </div>
                            </div>
                        </div>`;
                    break;
                    
                case 'inventory':
                    html = `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Reporte de Inventario</h3>
                            <p class="mb-4">Total de productos: ${data.all_products?.length || 0}</p>
                            <p class="mb-4 text-red-600">Productos con stock bajo: ${data.low_stock?.length || 0}</p>
                        </div>`;
                    break;
                    
                case 'users':
                    if (data.users_by_role) {
                        html = `
                            <div class="bg-white rounded-lg shadow p-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-800">Reporte de Usuarios</h3>
                                <p class="mb-4">Total de usuarios: ${data.total_users}</p>
                                <h4 class="font-semibold mb-2">Usuarios por rol:</h4>`;
                        
                        for (const [role, count] of Object.entries(data.users_by_role)) {
                            html += `<p>${role}: ${count} usuarios</p>`;
                        }
                        
                        html += '</div>';
                    }
                    break;
            }
            
            reportContainer.innerHTML = html;
            reportContainer.classList.remove('hidden');
        }
        
        // Funciones de modal
        function openModal(content) {
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('modalOverlay').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('modalOverlay').classList.add('hidden');
        }
        
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        function logout() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                window.location.href = '/auth/logout.php';
            }
        }
        
        // Funciones CRUD (simplificadas para demostración)
        function openCreateUserModal() {
            if (!userPermissions.create_user) return;
            
            const content = `
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Crear Usuario</h2>
                    <form onsubmit="createUser(event)">
                        <div class="space-y-4">
                            <input type="text" name="name" placeholder="Nombre" required class="w-full border rounded-lg p-2">
                            <input type="email" name="email" placeholder="Email" required class="w-full border rounded-lg p-2">
                            <input type="tel" name="phone" placeholder="Teléfono" class="w-full border rounded-lg p-2">
                            <input type="password" name="password" placeholder="Contraseña" required class="w-full border rounded-lg p-2">
                            <select name="role" required class="w-full border rounded-lg p-2">
                                <option value="">Seleccionar rol</option>
                                <option value="admin">Administrador</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="bodega">Bodega</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border rounded-lg">Cancelar</button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Crear</button>
                        </div>
                    </form>
                </div>`;
            
            openModal(content);
        }
        
        async function createUser(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            const result = await apiRequest('/api/users.php', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (result.success) {
                showMessage('Usuario creado exitosamente', 'success');
                closeModal();
                loadUsers();
            }
        }
        
        function openCreateProductModal() {
            // Similar implementación para productos
            showMessage('Funcionalidad en desarrollo', 'info');
        }
        
        function openCreateCategoryModal() {
            // Similar implementación para categorías
            showMessage('Funcionalidad en desarrollo', 'info');
        }
        
        function openCreateSaleModal() {
            // Similar implementación para ventas
            showMessage('Funcionalidad en desarrollo', 'info');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleUserDropdown') === -1) {
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                }
            }
        });
        
        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            // Activar la pestaña correcta basada en localStorage
            const savedTab = localStorage.getItem('activeTab');
            const tabExists = visibleTabs.some(tab => tab.id === savedTab);
            
            if (savedTab && tabExists) {
                currentTab = savedTab;
            }
            
            switchTab(currentTab);
        });
    </script>
</body>
</html>
