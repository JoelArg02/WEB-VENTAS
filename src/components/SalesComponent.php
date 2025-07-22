<?php
class SalesComponent {
    private $userData;
    private $userRole;
    private $permissions;
    
    public function __construct($userData, $permissions) {
        $this->userData = $userData;
        $this->userRole = $userData['role'];
        $this->permissions = $permissions;
    }
    
    public function render() {
        ob_start();
        ?>
        <div class="space-y-6" id="salesTab">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Registro de Ventas</h2>
                <?php if ($this->permissions['create_sale']): ?>
                <button onclick="openCreateSaleModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    + Nueva Venta
                </button>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Ventas Recientes</h3>
                <div id="salesLoading" class="flex justify-center items-center h-32">
                    <div class="text-gray-500">Cargando ventas...</div>
                </div>
                <div class="overflow-x-auto hidden" id="salesTable">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">ID</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Cliente</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Productos</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Subtotal</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">IVA</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Total</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Fecha</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Vendedor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="salesTableBody">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
            async function loadSalesData() {
                try {
                    const result = await apiRequest('/api/sales.php');
                    
                    if (result.success) {
                        renderSalesTable(result.data);
                        document.getElementById('salesLoading').classList.add('hidden');
                        document.getElementById('salesTable').classList.remove('hidden');
                    } else {
                        showError('Error al cargar ventas: ' + result.message);
                    }
                } catch (error) {
                    showError('Error de conexión al cargar ventas');
                    console.error('Sales error:', error);
                }
            }
            
            function renderSalesTable(sales) {
                const tbody = document.getElementById('salesTableBody');
                let html = '';
                
                if (!sales || sales.length === 0) {
                    html = `
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No hay ventas registradas
                            </td>
                        </tr>`;
                } else {
                    sales.forEach(sale => {
                        // Crear una lista de productos para la venta
                        let productsInfo = 'Sin productos';
                        if (sale.items && Array.isArray(sale.items) && sale.items.length > 0) {
                            const productList = sale.items.map(item => 
                                `${item.product_name || 'Producto'} (${item.quantity || 1}x)`
                            ).join(', ');
                            productsInfo = productList;
                        }
                        
                        // Truncar lista de productos si es muy larga
                        const displayProductsInfo = productsInfo.length > 40 
                            ? productsInfo.substring(0, 40) + '...' 
                            : productsInfo;
                        
                        html += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-900 font-medium">#${sale.id || 'N/A'}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">${sale.client || 'N/A'}</td>
                                <td class="px-4 py-2 text-sm text-gray-600" title="${productsInfo}">
                                    ${displayProductsInfo}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(parseFloat(sale.subtotal) || 0)}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(parseFloat(sale.iva_amount) || 0)}</td>
                                <td class="px-4 py-2 text-sm text-gray-900 font-semibold">${formatCurrency(parseFloat(sale.total_amount) || 0)}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">${formatDate(sale.sale_date)}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">${sale.user_name || 'N/A'}</td>
                            </tr>`;
                    });
                }
                
                tbody.innerHTML = html;
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
