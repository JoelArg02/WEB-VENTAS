let currentReportData = null;
let currentReportType = 'sales';

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('reportFilters')) {
        initializeReports();
    }
});

function initializeReports() {
    console.log('Initializing reports...');
    
    // Renderizar filtros iniciales
    const filtersContainer = document.getElementById('reportFilters');
    if (filtersContainer) {
        filtersContainer.innerHTML = ReportsFilters.renderFilters(currentReportType);
    }
    
    // Renderizar reportes rápidos
    const quickReportsContainer = document.getElementById('quickReports');
    if (quickReportsContainer) {
        quickReportsContainer.innerHTML = ReportsFilters.renderQuickReports();
    }
    
    // Configurar fechas por defecto (último mes)
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    
    if (dateFromInput && dateToInput) {
        dateFromInput.value = formatDateForInput(lastMonth);
        dateToInput.value = formatDateForInput(endOfLastMonth);
    }
    
    // Cargar datos necesarios
    loadCategories();
    loadSellers();
    
    console.log('Reports initialized successfully');
}

// Cambiar tipo de reporte
function changeReportType() {
    const reportTypeSelect = document.getElementById('reportType');
    if (!reportTypeSelect) return;
    
    currentReportType = reportTypeSelect.value;
    console.log('Report type changed to:', currentReportType);
    
    // Actualizar filtros según el nuevo tipo
    const filtersContainer = document.getElementById('reportFilters');
    if (filtersContainer) {
        filtersContainer.innerHTML = ReportsFilters.renderFilters(currentReportType);
    }
    
    // Limpiar contenido previo
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.innerHTML = getEmptyReportMessage();
    }
    
    // Recargar datos necesarios
    loadCategories();
    loadSellers();
    
    // Resetear datos actuales
    currentReportData = null;
}

// Aplicar filtros (llamado cuando cambian las fechas)
function applyFilters() {
    // Solo regenerar si ya hay un reporte cargado
    if (currentReportData) {
        generateReport();
    }
}

// Limpiar filtros
function clearFilters() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('categoryId').value = '';
    currentReportData = null;
    document.getElementById('reportContent').innerHTML = getEmptyReportMessage();
}

// Generar reporte principal
async function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const categoryId = document.getElementById('categoryId').value;
    
    showReportLoading();
    
    try {
        const response = await fetch('../api/reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: reportType,
                dateFrom: dateFrom,
                dateTo: dateTo,
                categoryId: categoryId
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            currentReportData = data.data;
            renderReport(reportType, data.data, { dateFrom, dateTo, categoryId });
        } else {
            showReportError(data.message || 'Error al generar el reporte');
        }
    } catch (error) {
        console.error('Error:', error);
        
        // Usar el sistema de toast para errores de conexión
        if (typeof showMessage === 'function') {
            showMessage('Error de conexión al generar el reporte', 'error');
        }
        
        showReportError('Error de conexión al generar el reporte');
    }
}

// Renderizar reporte según el tipo
function renderReport(type, data, filters) {
    const container = document.getElementById('reportContent');
    
    switch (type) {
        case 'sales':
            container.innerHTML = ReportsRenderer.renderSalesReport(data, filters);
            break;
        case 'products':
            container.innerHTML = ReportsRenderer.renderProductsReport(data, filters);
            break;
        case 'inventory':
            container.innerHTML = ReportsRenderer.renderInventoryReport(data, filters);
            break;
        case 'categories':
            container.innerHTML = ReportsRenderer.renderCategoriesReport(data, filters);
            break;
        default:
            container.innerHTML = getEmptyReportMessage();
    }
}

// Renderizar reporte de ventas
function renderSalesReport(data, filters) {
    const summary = data.summary || {};
    const details = data.details || [];
    
    return `
        <div class="space-y-6">
            <!-- Resumen de ventas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-blue-600 text-sm font-medium">Total Ventas</div>
                    <div class="text-2xl font-bold text-blue-800">${summary.total_sales || 0}</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-green-600 text-sm font-medium">Ingresos Totales</div>
                    <div class="text-2xl font-bold text-green-800">${formatCurrency(summary.total_revenue || 0)}</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="text-yellow-600 text-sm font-medium">Venta Promedio</div>
                    <div class="text-2xl font-bold text-yellow-800">${formatCurrency(summary.avg_sale_amount || 0)}</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="text-purple-600 text-sm font-medium">Productos Vendidos</div>
                    <div class="text-2xl font-bold text-purple-800">${summary.total_items_sold || 0}</div>
                </div>
            </div>
            
            <!-- Tabla de ventas detalladas -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Ventas Detalladas</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendedor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${details.map(sale => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatDate(sale.sale_date)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${sale.client || 'Cliente General'}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${sale.seller_name || ''}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${sale.total_items || 0}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${formatCurrency(sale.total_amount)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

// Cargar categorías para el filtro
async function loadCategories() {
    try {
        const response = await fetch('../api/categories.php');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('categoryId');
            if (select) {
                select.innerHTML = '<option value="">Todas las categorías</option>';
                
                data.data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        if (typeof showMessage === 'function') {
            showMessage('Error al cargar categorías', 'error');
        }
    }
}

// Cargar vendedores para el filtro
async function loadSellers() {
    try {
        const response = await fetch('../api/users.php');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('sellerId');
            if (select) {
                select.innerHTML = '<option value="">Todos los vendedores</option>';
                
                data.data.forEach(user => {
                    // Solo incluir usuarios con roles que pueden hacer ventas
                    if (['admin', 'vendedor', 'cajero'].includes(user.role)) {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = `${user.name} (${user.role})`;
                        select.appendChild(option);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error loading sellers:', error);
        if (typeof showMessage === 'function') {
            showMessage('Error al cargar vendedores', 'error');
        }
    }
}

// Utilidades
function showReportLoading() {
    document.getElementById('reportContent').innerHTML = `
        <div class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-500 mt-4">Generando reporte...</p>
        </div>
    `;
}

function showReportError(message) {
    // Mostrar el error usando el sistema de toast global
    if (typeof showMessage === 'function') {
        showMessage(message, 'error');
    }
    
    // También mostrar en el contenido del reporte
    document.getElementById('reportContent').innerHTML = `
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-red-500">${message}</p>
            <button onclick="generateReport()" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Intentar de nuevo
            </button>
        </div>
    `;
}

function getEmptyReportMessage() {
    return `
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500">Selecciona un tipo de reporte y haz clic en "Generar Reporte" para comenzar</p>
        </div>
    `;
}

function formatDateForInput(date) {
    return date.toISOString().split('T')[0];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Renderizar reporte de productos
function renderProductsReport(data, filters) {
    const summary = data.summary || {};
    const products = data.products || [];
    
    return `
        <div class="space-y-6">
            <!-- Resumen de productos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-blue-600 text-sm font-medium">Productos Analizados</div>
                    <div class="text-2xl font-bold text-blue-800">${summary.total_products || 0}</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-green-600 text-sm font-medium">Unidades Vendidas</div>
                    <div class="text-2xl font-bold text-green-800">${summary.total_sold || 0}</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="text-purple-600 text-sm font-medium">Ingresos por Productos</div>
                    <div class="text-2xl font-bold text-purple-800">${formatCurrency(summary.total_revenue || 0)}</div>
                </div>
            </div>
            
            <!-- Tabla de productos -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Productos Más Vendidos</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendidos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ingresos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${products.map(product => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.category_name || 'Sin categoría'}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(product.price)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.total_sold || 0}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(product.total_revenue || 0)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 py-1 text-xs rounded-full ${getStockStatusColor(product.stock)}">
                                            ${product.stock}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

// Renderizar reporte de inventario
function renderInventoryReport(data, filters) {
    const summary = data.summary || {};
    const inventory = data.inventory || [];
    
    return `
        <div class="space-y-6">
            <!-- Resumen de inventario -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-blue-600 text-sm font-medium">Total Productos</div>
                    <div class="text-2xl font-bold text-blue-800">${summary.total_products || 0}</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-green-600 text-sm font-medium">Valor Inventario</div>
                    <div class="text-2xl font-bold text-green-800">${formatCurrency(summary.total_stock_value || 0)}</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="text-yellow-600 text-sm font-medium">Unidades en Stock</div>
                    <div class="text-2xl font-bold text-yellow-800">${summary.total_stock || 0}</div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="text-red-600 text-sm font-medium">Stock Bajo</div>
                    <div class="text-2xl font-bold text-red-800">${summary.low_stock_count || 0}</div>
                </div>
            </div>
            
            <!-- Tabla de inventario -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Inventario Detallado</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${inventory.map(item => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.category_name || 'Sin categoría'}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(item.price)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.stock}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(item.stock_value)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 py-1 text-xs rounded-full ${getStockStatusColor(item.stock)}">
                                            ${item.stock_status || 'Normal'}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

// Renderizar reporte de categorías
function renderCategoriesReport(data, filters) {
    const summary = data.summary || {};
    const categories = data.categories || [];
    
    return `
        <div class="space-y-6">
            <!-- Resumen de categorías -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-blue-600 text-sm font-medium">Total Categorías</div>
                    <div class="text-2xl font-bold text-blue-800">${summary.total_categories || 0}</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-green-600 text-sm font-medium">Ingresos Totales</div>
                    <div class="text-2xl font-bold text-green-800">${formatCurrency(summary.total_revenue || 0)}</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="text-purple-600 text-sm font-medium">Productos Totales</div>
                    <div class="text-2xl font-bold text-purple-800">${summary.total_products || 0}</div>
                </div>
            </div>
            
            <!-- Tabla de categorías -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Rendimiento por Categoría</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Productos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendidos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ingresos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Promedio</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${categories.map(category => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${category.category_name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${category.total_products}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${category.total_stock || 0}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${category.total_sold || 0}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(category.total_revenue || 0)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(category.avg_product_price || 0)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

// Utilidad para obtener color del estado del stock
function getStockStatusColor(stock) {
    if (stock <= 5) return 'bg-red-100 text-red-800';
    if (stock <= 10) return 'bg-yellow-100 text-yellow-800';
    if (stock <= 20) return 'bg-blue-100 text-blue-800';
    return 'bg-green-100 text-green-800';
}

// Función para generar reportes rápidos
function generateQuickReport(type) {
    console.log('Generating quick report:', type);
    
    const today = new Date();
    const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    switch (type) {
        case 'sales_today':
            // Configurar para ventas del día
            document.getElementById('reportType').value = 'sales';
            document.getElementById('dateFrom').value = formatDateForInput(today);
            document.getElementById('dateTo').value = formatDateForInput(today);
            currentReportType = 'sales';
            break;
            
        case 'sales_month':
            // Configurar para ventas del mes
            document.getElementById('reportType').value = 'sales';
            document.getElementById('dateFrom').value = formatDateForInput(thisMonth);
            document.getElementById('dateTo').value = formatDateForInput(today);
            currentReportType = 'sales';
            break;
            
        case 'low_stock':
            // Configurar para productos con stock bajo
            document.getElementById('reportType').value = 'inventory';
            currentReportType = 'inventory';
            break;
            
        case 'expiring':
            // Configurar para productos próximos a caducar
            document.getElementById('reportType').value = 'inventory';
            currentReportType = 'inventory';
            break;
            
        default:
            console.warn('Unknown quick report type:', type);
            return;
    }
    
    // Actualizar la UI de filtros
    const filtersContainer = document.getElementById('reportFilters');
    if (filtersContainer) {
        filtersContainer.innerHTML = ReportsFilters.renderFilters(currentReportType);
    }
    
    // Cargar datos necesarios y generar el reporte
    loadCategories();
    loadSellers();
    
    // Agregar un pequeño delay para asegurar que los filtros se actualicen
    setTimeout(() => {
        generateReport();
    }, 100);
}

// Funciones de exportación (preparadas para PDF y Excel)
function exportCurrentReport(format) {
    if (!currentReportData) {
        if (typeof showMessage === 'function') {
            showMessage('No hay datos para exportar. Genera un reporte primero.', 'warning');
        } else {
            alert('No hay datos para exportar. Genera un reporte primero.');
        }
        return;
    }
    
    console.log(`Exportando reporte en formato ${format}...`);
    // TODO: Implementar exportación real
    if (typeof showMessage === 'function') {
        showMessage(`Funcionalidad de exportación ${format.toUpperCase()} lista para implementar`, 'info');
    } else {
        alert(`Funcionalidad de exportación ${format.toUpperCase()} lista para implementar`);
    }
}
