<?php
class DashboardComponent {
    private $userData;
    private $userRole;
    
    public function __construct($userData) {
        $this->userData = $userData;
        $this->userRole = $userData['role'];
    }
    
    public function render() {
        ob_start();
        ?>
        <div class="space-y-6" id="dashboardTab">
            <!-- Estadísticas principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="statsCards">
                <!-- Se cargan dinámicamente -->
            </div>
            
            <!-- Productos con stock bajo -->
            <div id="lowStockSection" class="hidden">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Productos con Stock Bajo</h3>
                    <div class="bg-yellow-50 border-yellow-200 text-yellow-800 border-l-4 p-4 mb-4 rounded">
                        <p>Los siguientes productos necesitan restock:</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white" id="lowStockTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Producto</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Stock</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Precio</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Categoría</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="lowStockBody">
                                <!-- Se carga dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Ventas recientes -->
            <div id="recentSalesSection" class="hidden">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Recientes</h3>
                    <div class="space-y-3" id="recentSalesList">
                        <!-- Se carga dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            async function loadDashboardData() {
                try {
                    showLoading('dashboardTab');
                    const result = await apiRequest('/api/dashboard.php');
                    
                    if (result.success) {
                        renderStatsCards(result.data);
                        renderLowStock(result.data.low_stock_products);
                        renderRecentSales(result.data.recent_sales);
                    } else {
                        showError('Error al cargar dashboard: ' + result.message);
                    }
                } catch (error) {
                    showError('Error de conexión al cargar dashboard');
                    console.error('Dashboard error:', error);
                }
            }
            
            function renderStatsCards(data) {
                const statsCards = document.getElementById('statsCards');
                let html = '';
                
                // Ventas hoy
                if (data.sales_stats) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Hoy</h3>
                            <div class="text-3xl font-bold text-blue-600">${formatCurrency(data.sales_stats.today?.total || 0)}</div>
                            <p class="text-gray-600">${data.sales_stats.today?.count || 0} ventas</p>
                        </div>`;
                }
                
                // Productos
                if (data.total_products !== undefined) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Productos</h3>
                            <div class="text-3xl font-bold text-green-600">${data.total_products}</div>
                            <p class="text-gray-600">En inventario</p>
                        </div>`;
                }
                
                // Usuarios
                if (data.total_users !== undefined) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Usuarios</h3>
                            <div class="text-3xl font-bold text-purple-600">${data.total_users}</div>
                            <p class="text-gray-600">Registrados</p>
                        </div>`;
                }
                
                // Stock bajo
                if (data.low_stock_products) {
                    html += `
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Stock Bajo</h3>
                            <div class="text-3xl font-bold text-red-600">${data.low_stock_products.length}</div>
                            <p class="text-gray-600">Productos</p>
                        </div>`;
                }
                
                statsCards.innerHTML = html;
            }
            
            function renderLowStock(products) {
                if (!products || products.length === 0) return;
                
                const section = document.getElementById('lowStockSection');
                const tbody = document.getElementById('lowStockBody');
                
                let html = '';
                products.forEach(product => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">${product.name}</td>
                            <td class="px-4 py-2 text-sm text-red-600 font-semibold">${product.stock}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(product.price)}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${product.category_name || 'Sin categoría'}</td>
                        </tr>`;
                });
                
                tbody.innerHTML = html;
                section.classList.remove('hidden');
            }
            
            function renderRecentSales(sales) {
                if (!sales || sales.length === 0) return;
                
                const section = document.getElementById('recentSalesSection');
                const list = document.getElementById('recentSalesList');
                
                let html = '';
                sales.slice(0, 5).forEach(sale => {
                    html += `
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <span class="font-medium">${sale.product_name}</span>
                                <span class="text-sm text-gray-500"> - ${sale.client}</span>
                            </div>
                            <span class="font-semibold">${formatCurrency(sale.price * sale.quantity)}</span>
                        </div>`;
                });
                
                list.innerHTML = html;
                section.classList.remove('hidden');
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
