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
    // Restaurar valores por defecto
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    
    if (dateFromInput && dateToInput) {
        dateFromInput.value = formatDateForInput(lastMonth);
        dateToInput.value = formatDateForInput(endOfLastMonth);
    }
    
    // Limpiar otros filtros específicos
    const categorySelect = document.getElementById('categoryId');
    if (categorySelect) categorySelect.value = '';
    
    const sellerSelect = document.getElementById('sellerId');
    if (sellerSelect) sellerSelect.value = '';
    
    // Limpiar contenido del reporte
    document.getElementById('reportContent').innerHTML = getEmptyReportMessage();
    currentReportData = null;
}

// Generar reporte principal
async function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const categoryId = document.getElementById('categoryId')?.value || '';
    
    if (!dateFrom || !dateTo) {
        if (typeof showMessage === 'function') {
            showMessage('Por favor selecciona las fechas', 'warning');
        }
        return;
    }
    
    showReportLoading();
    
    try {
        const response = await fetch('../api/reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: reportType,
                date_from: dateFrom,
                date_to: dateTo,
                category_id: categoryId
            })
        });
        
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

// Renderizar reporte usando las clases modulares
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
