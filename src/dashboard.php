<?php
require_once 'auth/session.php';
require_once 'auth/permissions.php';
require_once 'components/DashboardComponent.php';
require_once 'components/UsersComponent.php';
require_once 'components/ProductsComponent.php';
require_once 'components/SalesComponent.php';
require_once 'components/CategoriesComponent.php';
require_once 'components/ReportsComponent.php';
require_once 'components/ModalComponent.php';

SessionManager::requireLogin();

$userData = SessionManager::getUserData();
$userPermissions = PermissionManager::getRolePermissions($userData['role']);
$visibleTabs = PermissionManager::getVisibleTabs($userData['role']);
$title = 'Dashboard - Sistema de Ventas';

$dashboardComponent = new DashboardComponent($userData);
$usersComponent = new UsersComponent($userData, $userPermissions);
$productsComponent = new ProductsComponent($userData, $userPermissions);
$salesComponent = new SalesComponent($userData, $userPermissions);
$categoriesComponent = new CategoriesComponent($userData, $userPermissions);
$reportsComponent = new ReportsComponent($userData, $userPermissions);
$modalComponent = new ModalComponent($userPermissions);

include 'includes/header.php';
?>

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
            <!-- Dashboard Tab -->
            <div id="dashboard-content" class="tab-content">
                <?php echo $dashboardComponent->render(); ?>
            </div>
            
            <!-- Users Tab -->
            <?php if (in_array('users', array_column($visibleTabs, 'id'))): ?>
            <div id="users-content" class="tab-content hidden">
                <?php echo $usersComponent->render(); ?>
            </div>
            <?php endif; ?>
            
            <!-- Products Tab -->
            <?php if (in_array('products', array_column($visibleTabs, 'id'))): ?>
            <div id="products-content" class="tab-content hidden">
                <?php echo $productsComponent->render(); ?>
            </div>
            <?php endif; ?>
            
            <!-- Sales Tab -->
            <?php if (in_array('sales', array_column($visibleTabs, 'id'))): ?>
            <div id="sales-content" class="tab-content hidden">
                <?php echo $salesComponent->render(); ?>
            </div>
            <?php endif; ?>
            
            <!-- Categories Tab -->
            <?php if (in_array('categories', array_column($visibleTabs, 'id'))): ?>
            <div id="categories-content" class="tab-content hidden">
                <?php echo $categoriesComponent->render(); ?>
            </div>
            <?php endif; ?>
            
            <!-- Reports Tab -->
            <?php if (in_array('reports', array_column($visibleTabs, 'id'))): ?>
            <div id="reports-content" class="tab-content hidden">
                <?php echo $reportsComponent->render(); ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para formularios -->
    <div id="modalOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div id="modalContent" class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[95vh] overflow-y-auto">
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
        const visibleTabs = <?php echo json_encode($visibleTabs); ?>;
        
        let currentTab = localStorage.getItem('activeTab') || '<?php echo $visibleTabs[0]['id'] ?? 'dashboard'; ?>';
        
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
        
        function showError(message) {
            showMessage(message, 'error');
        }
        
        function showLoading(containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '<div class="flex justify-center items-center h-32"><div class="text-gray-500">Cargando...</div></div>';
            }
        }
        
        async function apiRequest(url, options = {}) {
            console.log('API Request:', url, options);
            
            try {
                const response = await fetch(url, {
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    },
                    ...options
                });
                
                console.log('API Response Status:', response.status, response.statusText);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('API Response Data:', result);
                
                if (!result.success && result.message) {
                    console.error('API Error:', result.message);
                    showMessage(result.message, 'error');
                }
                
                return result;
            } catch (error) {
                console.error('API Request Failed:', {
                    url: url,
                    error: error.message,
                    stack: error.stack
                });
                showMessage('Error de conexión: ' + error.message, 'error');
                throw error;
            }
        }
        
        // Gestión de pestañas
        function switchTab(tabId) {
            currentTab = tabId;
            localStorage.setItem('activeTab', tabId);
            console.log('Switched to tab:', tabId, '- Saved to localStorage');
            
            // Actualizar estilo de pestañas
            document.querySelectorAll('[data-tab]').forEach(tab => {
                if (tab.dataset.tab === tabId) {
                    tab.className = 'py-4 px-1 border-b-2 font-medium text-sm transition-colors border-blue-500 text-blue-600';
                } else {
                    tab.className = 'py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                }
            });
            
            // Mostrar/ocultar contenido
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            const activeContent = document.getElementById(tabId + '-content');
            if (activeContent) {
                activeContent.classList.remove('hidden');
                loadTabData(tabId);
            }
        }
        
        // Cargar datos según la pestaña activa
        function loadTabData(tabId) {
            console.log('Loading tab data for:', tabId);
            
            try {
                switch (tabId) {
                    case 'dashboard':
                        console.log('Loading dashboard data...');
                        loadDashboardData();
                        break;
                    case 'users':
                        console.log('Loading users data...');
                        if (typeof loadUsersData === 'function') loadUsersData();
                        break;
                    case 'products':
                        console.log('Loading products data...');
                        if (typeof loadProductsData === 'function') loadProductsData();
                        break;
                    case 'sales':
                        console.log('Loading sales data...');
                        if (typeof loadSalesData === 'function') loadSalesData();
                        break;
                    case 'categories':
                        console.log('Loading categories data...');
                        if (typeof loadCategoriesData === 'function') loadCategoriesData();
                        break;
                    case 'reports':
                        console.log('Loading reports data...');
                        if (typeof initializeReports === 'function') {
                            initializeReports();
                        } else {
                            console.warn('initializeReports function not found');
                        }
                        break;
                    default:
                        console.warn('Unknown tab:', tabId);
                }
            } catch (error) {
                console.error('Error loading tab data:', error);
                showMessage('Error al cargar los datos de la pestaña', 'error');
            }
        }
        
        // Función para cargar y dibujar los datos del dashboard
        async function loadDashboardData() {
            const container = document.getElementById('dashboard-content');
            if (!container) return;
            
            try {
                showLoading('dashboard-content');
                
                const response = await apiRequest('api/dashboard.php');
                
                if (!response.success) {
                    container.innerHTML = '<div class="text-center text-red-500">Error al cargar datos del dashboard</div>';
                    return;
                }
                
                renderDashboardData(response.data);
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                container.innerHTML = '<div class="text-center text-red-500">Error de conexión al cargar dashboard</div>';
            }
        }
        
        // Función para renderizar los datos del dashboard
        function renderDashboardData(data) {
            const container = document.getElementById('dashboard-content');
            if (!container) return;
            
            let html = '<div class="space-y-6">';
            
            // Tarjetas de estadísticas principales
            html += '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">';
            
            // Ventas de hoy
            if (data.sales_stats && data.sales_stats.today) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-2 text-gray-800">Ventas Hoy</h3>
                        <div class="text-3xl font-bold text-blue-600">${formatCurrency(parseFloat(data.sales_stats.today.total))}</div>
                        <p class="text-gray-600">${data.sales_stats.today.count} ventas</p>
                    </div>
                `;
            }
            
            // Ventas del mes
            if (data.sales_stats && data.sales_stats.month) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-2 text-gray-800">Ventas del Mes</h3>
                        <div class="text-3xl font-bold text-green-600">${formatCurrency(parseFloat(data.sales_stats.month.total))}</div>
                        <p class="text-gray-600">${data.sales_stats.month.count} ventas</p>
                    </div>
                `;
            }
            
            // Total de productos
            if (data.total_products !== undefined) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-2 text-gray-800">Productos</h3>
                        <div class="text-3xl font-bold text-purple-600">${data.total_products}</div>
                        <p class="text-gray-600">En inventario</p>
                    </div>
                `;
            }
            
            // Total de usuarios
            if (data.total_users !== undefined) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-2 text-gray-800">Usuarios</h3>
                        <div class="text-3xl font-bold text-indigo-600">${data.total_users}</div>
                        <p class="text-gray-600">Registrados</p>
                    </div>
                `;
            }
            
            html += '</div>';
            
            // Segunda fila con información detallada
            html += '<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">';
            
            // Productos con stock bajo
            if (data.low_stock_products && data.low_stock_products.length > 0) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Stock Bajo (${data.low_stock_products.length})</h3>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                `;
                
                data.low_stock_products.forEach(product => {
                    html += `
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded border-l-4 border-red-400">
                            <div>
                                <div class="font-medium text-red-800">${product.name}</div>
                                <div class="text-sm text-red-600">${product.category_name || 'Sin categoría'}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-red-800 font-bold">${product.stock}</div>
                                <div class="text-xs text-red-600">${formatCurrency(parseFloat(product.price))}</div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div></div>';
            }
            
            // Productos próximos a caducar
            if (data.expiring_products && data.expiring_products.length > 0) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Próximos a Caducar (${data.expiring_products.length})</h3>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                `;
                
                data.expiring_products.forEach(product => {
                    const daysLeft = product.days_until_expiration;
                    const urgencyClass = daysLeft <= 7 ? 'border-red-400 bg-red-50 text-red-800' : 'border-yellow-400 bg-yellow-50 text-yellow-800';
                    
                    html += `
                        <div class="flex justify-between items-center p-3 rounded border-l-4 ${urgencyClass}">
                            <div>
                                <div class="font-medium">${product.name}</div>
                                <div class="text-sm opacity-75">${product.category_name || 'Sin categoría'}</div>
                                <div class="text-xs opacity-75">Vence: ${new Date(product.expiration_date).toLocaleDateString('es-CO')}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold">${daysLeft}d</div>
                                <div class="text-xs opacity-75">Stock: ${product.stock}</div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div></div>';
            }
            
            if (data.recent_sales && data.recent_sales.length > 0) {
                html += `
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Recientes</h3>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                `;
                
                data.recent_sales.slice(0, 5).forEach(sale => {
                    // Crear información de productos para mostrar
                    let productsInfo = 'Sin productos';
                    let totalItems = 0;
                    
                    if (sale.items && Array.isArray(sale.items) && sale.items.length > 0) {
                        const productNames = sale.items.map(item => 
                            `${item.product_name || 'Producto'} (${item.quantity || 1}x)`
                        ).join(', ');
                        productsInfo = productNames.length > 50 ? productNames.substring(0, 50) + '...' : productNames;
                        totalItems = sale.items.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
                    }
                    
                    html += `
                        <div class="flex justify-between items-center border-b pb-2">
                            <div class="flex-1">
                                <div class="font-medium text-blue-600">#${sale.id || 'N/A'}</div>
                                <div class="text-sm text-gray-700">${sale.client || 'Cliente no especificado'}</div>
                                <div class="text-xs text-gray-500" title="${productsInfo}">${productsInfo}</div>
                                <div class="text-xs text-gray-400">${formatDate(sale.sale_date)} - ${sale.user_name || 'N/A'}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-green-600">${formatCurrency(parseFloat(sale.total_amount) || 0)}</div>
                                <div class="text-xs text-gray-500">${totalItems} productos</div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div></div>';
            }
            
            html += '</div>';
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        // Funciones para modal de usuarios
        function openCreateUserModal() {
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = `
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-3xl font-bold text-gray-800">Crear Nuevo Usuario</h2>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>
                    
                    <form id="createUserForm" onsubmit="createUser(event)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Nombre Completo *</label>
                                <input type="text" id="userName" name="name" 
                                       class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                       placeholder="Ingrese el nombre completo" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Correo Electrónico *</label>
                                <input type="email" id="userEmail" name="email" 
                                       class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                       placeholder="usuario@empresa.com" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Teléfono</label>
                                <input type="tel" id="userPhone" name="phone" 
                                       class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                       placeholder="0999999999">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Rol del Sistema *</label>
                                <select id="userRole" name="role" 
                                        class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                                    <option value="">Seleccionar rol</option>
                                    <option value="admin">Administrador</option>
                                    <option value="vendedor">Vendedor</option>
                                    <option value="bodega">Bodega</option>
                                    <option value="cajero">Cajero</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Contraseña Temporal *</label>
                                <input type="password" id="userPassword" name="password" 
                                       class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                       placeholder="Mínimo 6 caracteres" required minlength="6">
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-6 mt-8">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Información Adicional</h3>
                            <p class="text-gray-600">El usuario recibirá un correo con sus credenciales de acceso. Podrá cambiar su contraseña en el primer inicio de sesión.</p>
                        </div>
                        
                        <div class="flex justify-end space-x-4 mt-10 pt-6 border-t">
                            <button type="button" onclick="closeModal()" 
                                    class="px-8 py-4 text-lg text-gray-600 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-semibold">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-8 py-4 text-lg bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold shadow-lg">
                                Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            `;
            document.getElementById('modalOverlay').classList.remove('hidden');
        }
        
        async function createUser(event) {
            event.preventDefault();
            console.log('Creating user...');
            
            const form = event.target;
            const formData = new FormData(form);
            
            const userData = {
                name: formData.get('name').trim(),
                email: formData.get('email').trim(),
                phone: formData.get('phone').trim(),
                role: formData.get('role'),
                password: formData.get('password'),
                status: 1
            };
            
            console.log('User data to send:', userData);
            
            // Validaciones del frontend
            if (!userData.name || userData.name.length < 2) {
                showMessage('El nombre debe tener al menos 2 caracteres', 'error');
                return;
            }
            
            if (!userData.email || !userData.email.includes('@')) {
                showMessage('Por favor ingrese un email válido', 'error');
                return;
            }
            
            if (!userData.role) {
                showMessage('Por favor seleccione un rol', 'error');
                return;
            }
            
            if (!userData.password || userData.password.length < 6) {
                showMessage('La contraseña debe tener al menos 6 caracteres', 'error');
                return;
            }
            
            // Deshabilitar el botón de envío
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creando...';
            
            try {
                console.log('Making API request to create user...');
                
                const response = await fetch('api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(userData)
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                
                const responseText = await response.text();
                console.log('Raw response text:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log('Parsed response:', result);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response was:', responseText);
                    
                    // Tratar de extraer el error real de HTML si existe
                    if (responseText.includes('Fatal error') || responseText.includes('Parse error')) {
                        const errorMatch = responseText.match(/Fatal error[^<]*/i) || responseText.match(/Parse error[^<]*/i);
                        if (errorMatch) {
                            throw new Error('Error del servidor: ' + errorMatch[0]);
                        }
                    }
                    
                    throw new Error('Error del servidor: La respuesta no es válida. Revise los logs de PHP para más detalles.');
                }
                
                if (result.success) {
                    showMessage(result.message || 'Usuario creado correctamente', 'success');
                    closeModal();
                    // Recargar usuarios si estamos en esa pestaña
                    if (currentTab === 'users') {
                        loadTabData('users');
                    }
                } else {
                    const errorMessage = result.message || 'Error desconocido al crear usuario';
                    console.error('API Error:', errorMessage);
                    showMessage(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error creating user:', {
                    message: error.message,
                    stack: error.stack,
                    userData: userData
                });
                
                let userFriendlyMessage = error.message;
                
                // Personalizar mensajes de error comunes
                if (error.message.includes('Failed to fetch')) {
                    userFriendlyMessage = 'Error de conexión: No se pudo conectar con el servidor. Verifique su conexión a internet.';
                } else if (error.message.includes('NetworkError')) {
                    userFriendlyMessage = 'Error de red: Problema de conectividad. Intente nuevamente.';
                } else if (error.message.includes('timeout')) {
                    userFriendlyMessage = 'Error: El servidor tardó demasiado en responder. Intente nuevamente.';
                }
                
                showMessage(userFriendlyMessage, 'error');
            } finally {
                // Rehabilitar el botón
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
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
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                if (typeof closeModal === 'function') closeModal();
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
            console.log('DOM loaded, initializing dashboard...');
            console.log('Available tabs:', visibleTabs);
            console.log('Saved tab from localStorage:', localStorage.getItem('activeTab'));
            console.log('Current tab to activate:', currentTab);
            
            // Verificar que la pestaña guardada existe y está disponible para el usuario
            const savedTab = localStorage.getItem('activeTab');
            const tabExists = visibleTabs.some(tab => tab.id === savedTab);
            
            if (savedTab && tabExists) {
                currentTab = savedTab;
                console.log('Using saved tab:', currentTab);
            } else {
                console.log('Saved tab not available, using default:', currentTab);
            }
            
            switchTab(currentTab);
        });
    </script>

    <!-- JavaScript específico para reportes -->
    <?php if (in_array('reports', array_column($visibleTabs, 'id'))): ?>
    <script src="components/ReportsFilters.js"></script>
    <script src="components/ReportsRenderer.js"></script>
    <script src="components/reports.js"></script>
    <?php endif; ?>

    <?php echo $modalComponent->render(); ?>
</body>
</html>
