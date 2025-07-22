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
    
    // Agregar botones de exportación al inicio
    let reportHTML = ReportsRenderer.renderExportButtons(type, data);
    
    switch (type) {
        case 'sales':
            reportHTML += ReportsRenderer.renderSalesReport(data, filters);
            break;
        case 'products':
            reportHTML += ReportsRenderer.renderProductsReport(data, filters);
            break;
        case 'inventory':
            reportHTML += ReportsRenderer.renderInventoryReport(data, filters);
            break;
        case 'categories':
            reportHTML += ReportsRenderer.renderCategoriesReport(data, filters);
            break;
        default:
            reportHTML = getEmptyReportMessage();
    }
    
    container.innerHTML = reportHTML;
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
    
    let reportType = 'sales';
    let dateFrom = null;
    let dateTo = null;
    let categoryId = '';
    
    switch (type) {
        case 'sales_today':
            // Configurar para ventas del día
            reportType = 'sales';
            dateFrom = formatDateForInput(today);
            dateTo = formatDateForInput(today);
            break;
            
        case 'sales_month':
            // Configurar para ventas del mes
            reportType = 'sales';
            dateFrom = formatDateForInput(thisMonth);
            dateTo = formatDateForInput(today);
            break;
            
        case 'low_stock':
            // Configurar para productos con stock bajo
            reportType = 'inventory';
            dateFrom = formatDateForInput(today);
            dateTo = formatDateForInput(today);
            break;
            
        case 'expiring':
            // Configurar para productos próximos a caducar
            reportType = 'inventory';
            dateFrom = formatDateForInput(today);
            dateTo = formatDateForInput(today);
            break;
            
        default:
            console.warn('Unknown quick report type:', type);
            return;
    }
    
    // Actualizar el tipo de reporte
    currentReportType = reportType;
    
    // Actualizar la UI de filtros primero
    const filtersContainer = document.getElementById('reportFilters');
    if (filtersContainer) {
        filtersContainer.innerHTML = ReportsFilters.renderFilters(currentReportType);
    }
    
    // Cargar datos necesarios
    loadCategories();
    loadSellers();
    
    // Configurar las fechas después de actualizar la UI
    setTimeout(() => {
        const reportTypeSelect = document.getElementById('reportType');
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        
        if (reportTypeSelect) reportTypeSelect.value = reportType;
        if (dateFromInput) dateFromInput.value = dateFrom;
        if (dateToInput) dateToInput.value = dateTo;
        
        // Generar el reporte directamente con los parámetros
        generateQuickReportDirectly(type, reportType, dateFrom, dateTo, categoryId);
    }, 150);
}

// Función auxiliar para generar reportes rápidos directamente
async function generateQuickReportDirectly(quickType, reportType, dateFrom, dateTo, categoryId) {
    showReportLoading();
    
    try {
        const body = {
            type: reportType,
            date_from: dateFrom,
            date_to: dateTo,
            category_id: categoryId
        };
        
        // Para reportes especiales agregar parámetros extra
        if (quickType === 'low_stock') {
            body.quick_report = 'low_stock';
        } else if (quickType === 'expiring') {
            body.quick_report = 'expiring';
        }
        
        const response = await fetch('../api/reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentReportData = data.data;
            renderReport(reportType, data.data, { dateFrom, dateTo, categoryId });
        } else {
            showReportError(data.message || 'Error al generar el reporte rápido');
        }
    } catch (error) {
        console.error('Error generating quick report:', error);
        
        if (typeof showMessage === 'function') {
            showMessage('Error de conexión al generar el reporte rápido', 'error');
        }
        
        showReportError('Error de conexión al generar el reporte rápido');
    }
}

// Funciones de exportación
function exportToPDF(reportType, reportDataString) {
    try {
        const reportData = typeof reportDataString === 'string' ? JSON.parse(reportDataString) : reportDataString;
        
        // Verificar si jsPDF está disponible
        if (typeof window.jsPDF === 'undefined') {
            // Cargar jsPDF dinámicamente
            loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', () => {
                generatePDF(reportType, reportData);
            });
        } else {
            generatePDF(reportType, reportData);
        }
    } catch (error) {
        console.error('Error exporting to PDF:', error);
        showMessage('Error al exportar a PDF', 'error');
    }
}

function exportToExcel(reportType, reportDataString) {
    try {
        const reportData = typeof reportDataString === 'string' ? JSON.parse(reportDataString) : reportDataString;
        
        // Verificar si SheetJS está disponible
        if (typeof XLSX === 'undefined') {
            // Cargar SheetJS dinámicamente
            loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', () => {
                generateExcel(reportType, reportData);
            });
        } else {
            generateExcel(reportType, reportData);
        }
    } catch (error) {
        console.error('Error exporting to Excel:', error);
        showMessage('Error al exportar a Excel', 'error');
    }
}

function loadScript(src, callback) {
    const script = document.createElement('script');
    script.src = src;
    script.onload = callback;
    script.onerror = () => {
        console.error('Failed to load script:', src);
        showMessage('Error al cargar la librería de exportación', 'error');
    };
    document.head.appendChild(script);
}

function generatePDF(reportType, data) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Configuración inicial
    doc.setFontSize(20);
    doc.text('Reporte de ' + getReportTitle(reportType), 20, 20);
    
    // Información del reporte
    doc.setFontSize(12);
    doc.text('Generado el: ' + new Date().toLocaleDateString('es-ES'), 20, 30);
    
    let yPosition = 50;
    
    switch (reportType) {
        case 'sales':
            generateSalesPDF(doc, data, yPosition);
            break;
        case 'products':
            generateProductsPDF(doc, data, yPosition);
            break;
        case 'inventory':
            generateInventoryPDF(doc, data, yPosition);
            break;
        case 'categories':
            generateCategoriesPDF(doc, data, yPosition);
            break;
    }
    
    // Guardar el PDF
    doc.save(`reporte_${reportType}_${new Date().toISOString().split('T')[0]}.pdf`);
    showMessage('PDF generado exitosamente', 'success');
}

function generateExcel(reportType, data) {
    const workbook = XLSX.utils.book_new();
    
    switch (reportType) {
        case 'sales':
            generateSalesExcel(workbook, data);
            break;
        case 'products':
            generateProductsExcel(workbook, data);
            break;
        case 'inventory':
            generateInventoryExcel(workbook, data);
            break;
        case 'categories':
            generateCategoriesExcel(workbook, data);
            break;
    }
    
    // Guardar el archivo Excel
    XLSX.writeFile(workbook, `reporte_${reportType}_${new Date().toISOString().split('T')[0]}.xlsx`);
    showMessage('Excel generado exitosamente', 'success');
}

function generateSalesPDF(doc, data, startY) {
    const summary = data.summary || {};
    
    // Resumen de ventas
    doc.setFontSize(14);
    doc.text('Resumen de Ventas', 20, startY);
    
    const summaryData = [
        ['Total de Ventas:', summary.total_sales || 0],
        ['Ingresos Totales:', formatCurrency(summary.total_revenue || 0)],
        ['Venta Promedio:', formatCurrency(summary.avg_sale_amount || 0)],
        ['Productos Vendidos:', summary.total_items_sold || 0]
    ];
    
    let y = startY + 10;
    summaryData.forEach(([label, value]) => {
        doc.text(label, 20, y);
        doc.text(String(value), 100, y);
        y += 8;
    });
    
    // Tabla de ventas detalladas
    if (data.details && data.details.length > 0) {
        y += 10;
        doc.setFontSize(14);
        doc.text('Ventas Detalladas', 20, y);
        y += 10;
        
        // Headers
        doc.setFontSize(10);
        doc.text('ID', 20, y);
        doc.text('Cliente', 40, y);
        doc.text('Vendedor', 80, y);
        doc.text('Total', 120, y);
        doc.text('Fecha', 150, y);
        y += 8;
        
        // Datos
        data.details.slice(0, 25).forEach(sale => { // Limitar a 25 ventas para que quepa en la página
            doc.text(String(sale.id), 20, y);
            doc.text(sale.client || 'N/A', 40, y);
            doc.text(sale.seller_name || 'N/A', 80, y);
            doc.text(formatCurrency(sale.total_amount), 120, y);
            doc.text(formatDate(sale.sale_date), 150, y);
            y += 6;
            
            if (y > 250) { // Nueva página si es necesario
                doc.addPage();
                y = 20;
            }
        });
    }
}

function generateProductsPDF(doc, data, startY) {
    const summary = data.summary || {};
    const products = data.products || [];
    
    // Resumen
    doc.setFontSize(14);
    doc.text('Resumen de Productos', 20, startY);
    
    const summaryData = [
        ['Total de Productos:', summary.total_products || 0],
        ['Unidades Vendidas:', summary.total_sold || 0],
        ['Ingresos:', formatCurrency(summary.total_revenue || 0)]
    ];
    
    let y = startY + 10;
    summaryData.forEach(([label, value]) => {
        doc.text(label, 20, y);
        doc.text(String(value), 100, y);
        y += 8;
    });
    
    // Tabla de productos
    if (products.length > 0) {
        y += 10;
        doc.setFontSize(14);
        doc.text('Productos Más Vendidos', 20, y);
        y += 10;
        
        // Headers
        doc.setFontSize(10);
        doc.text('Producto', 20, y);
        doc.text('Categoría', 70, y);
        doc.text('Precio', 110, y);
        doc.text('Vendidos', 140, y);
        doc.text('Ingresos', 170, y);
        y += 8;
        
        // Datos
        products.slice(0, 30).forEach(product => {
            const productName = product.name.length > 25 ? product.name.substring(0, 25) + '...' : product.name;
            doc.text(productName, 20, y);
            doc.text(product.category_name || 'N/A', 70, y);
            doc.text(formatCurrency(product.price), 110, y);
            doc.text(String(product.total_sold || 0), 140, y);
            doc.text(formatCurrency(product.total_revenue || 0), 170, y);
            y += 6;
            
            if (y > 250) {
                doc.addPage();
                y = 20;
            }
        });
    }
}

function generateInventoryPDF(doc, data, startY) {
    const summary = data.summary || {};
    const inventory = data.inventory || [];
    
    // Resumen
    doc.setFontSize(14);
    doc.text('Resumen de Inventario', 20, startY);
    
    const summaryData = [
        ['Total de Productos:', summary.total_products || 0],
        ['Valor del Inventario:', formatCurrency(summary.total_stock_value || 0)],
        ['Unidades en Stock:', summary.total_stock || 0],
        ['Productos con Stock Bajo:', summary.low_stock_count || 0]
    ];
    
    let y = startY + 10;
    summaryData.forEach(([label, value]) => {
        doc.text(label, 20, y);
        doc.text(String(value), 100, y);
        y += 8;
    });
    
    // Tabla de inventario
    if (inventory.length > 0) {
        y += 10;
        doc.setFontSize(14);
        doc.text('Inventario Detallado', 20, y);
        y += 10;
        
        // Headers
        doc.setFontSize(10);
        doc.text('Producto', 20, y);
        doc.text('Categoría', 70, y);
        doc.text('Stock', 110, y);
        doc.text('Precio', 140, y);
        doc.text('Valor', 170, y);
        y += 8;
        
        // Datos
        inventory.slice(0, 30).forEach(item => {
            const productName = item.name.length > 25 ? item.name.substring(0, 25) + '...' : item.name;
            doc.text(productName, 20, y);
            doc.text(item.category_name || 'N/A', 70, y);
            doc.text(String(item.stock), 110, y);
            doc.text(formatCurrency(item.price), 140, y);
            doc.text(formatCurrency(item.stock_value), 170, y);
            y += 6;
            
            if (y > 250) {
                doc.addPage();
                y = 20;
            }
        });
    }
}

function generateCategoriesPDF(doc, data, startY) {
    const summary = data.summary || {};
    const categories = data.categories || [];
    
    // Resumen
    doc.setFontSize(14);
    doc.text('Resumen de Categorías', 20, startY);
    
    const summaryData = [
        ['Total de Categorías:', summary.total_categories || 0],
        ['Ingresos Totales:', formatCurrency(summary.total_revenue || 0)],
        ['Total de Productos:', summary.total_products || 0]
    ];
    
    let y = startY + 10;
    summaryData.forEach(([label, value]) => {
        doc.text(label, 20, y);
        doc.text(String(value), 100, y);
        y += 8;
    });
    
    // Tabla de categorías
    if (categories.length > 0) {
        y += 10;
        doc.setFontSize(14);
        doc.text('Rendimiento por Categoría', 20, y);
        y += 10;
        
        // Headers
        doc.setFontSize(10);
        doc.text('Categoría', 20, y);
        doc.text('Productos', 70, y);
        doc.text('Vendidos', 110, y);
        doc.text('Ingresos', 140, y);
        y += 8;
        
        // Datos
        categories.forEach(category => {
            doc.text(category.category_name, 20, y);
            doc.text(String(category.total_products), 70, y);
            doc.text(String(category.total_sold || 0), 110, y);
            doc.text(formatCurrency(category.total_revenue || 0), 140, y);
            y += 6;
            
            if (y > 250) {
                doc.addPage();
                y = 20;
            }
        });
    }
}

function generateSalesExcel(workbook, data) {
    // Hoja de resumen
    const summaryData = [
        ['Métrica', 'Valor'],
        ['Total de Ventas', data.summary?.total_sales || 0],
        ['Ingresos Totales', data.summary?.total_revenue || 0],
        ['Venta Promedio', data.summary?.avg_sale_amount || 0],
        ['Productos Vendidos', data.summary?.total_items_sold || 0]
    ];
    
    const summaryWorksheet = XLSX.utils.aoa_to_sheet(summaryData);
    XLSX.utils.book_append_sheet(workbook, summaryWorksheet, 'Resumen');
    
    // Hoja de ventas detalladas
    if (data.details && data.details.length > 0) {
        const detailsData = [
            ['ID', 'Cliente', 'Vendedor', 'Subtotal', 'IVA', 'Total', 'Recibido', 'Cambio', 'Fecha', 'Productos']
        ];
        
        data.details.forEach(sale => {
            detailsData.push([
                sale.id,
                sale.client || 'N/A',
                sale.seller_name || 'N/A',
                sale.subtotal || 0,
                sale.iva_amount || 0,
                sale.total_amount || 0,
                sale.money_received || 0,
                sale.change_amount || 0,
                formatDate(sale.sale_date),
                sale.products_detail || 'N/A'
            ]);
        });
        
        const detailsWorksheet = XLSX.utils.aoa_to_sheet(detailsData);
        XLSX.utils.book_append_sheet(workbook, detailsWorksheet, 'Ventas Detalladas');
    }
}

function generateProductsExcel(workbook, data) {
    // Hoja de resumen
    const summaryData = [
        ['Métrica', 'Valor'],
        ['Total de Productos', data.summary?.total_products || 0],
        ['Unidades Vendidas', data.summary?.total_sold || 0],
        ['Ingresos por Productos', data.summary?.total_revenue || 0]
    ];
    
    const summaryWorksheet = XLSX.utils.aoa_to_sheet(summaryData);
    XLSX.utils.book_append_sheet(workbook, summaryWorksheet, 'Resumen');
    
    // Hoja de productos
    if (data.products && data.products.length > 0) {
        const productsData = [
            ['ID', 'Producto', 'Categoría', 'Precio', 'Stock', 'Vendidos', 'Ingresos', 'Ventas']
        ];
        
        data.products.forEach(product => {
            productsData.push([
                product.id,
                product.name,
                product.category_name || 'Sin categoría',
                product.price || 0,
                product.stock || 0,
                product.total_sold || 0,
                product.total_revenue || 0,
                product.sales_count || 0
            ]);
        });
        
        const productsWorksheet = XLSX.utils.aoa_to_sheet(productsData);
        XLSX.utils.book_append_sheet(workbook, productsWorksheet, 'Productos');
    }
}

function generateInventoryExcel(workbook, data) {
    // Hoja de resumen
    const summaryData = [
        ['Métrica', 'Valor'],
        ['Total de Productos', data.summary?.total_products || 0],
        ['Valor del Inventario', data.summary?.total_stock_value || 0],
        ['Unidades en Stock', data.summary?.total_stock || 0],
        ['Productos con Stock Bajo', data.summary?.low_stock_count || 0]
    ];
    
    const summaryWorksheet = XLSX.utils.aoa_to_sheet(summaryData);
    XLSX.utils.book_append_sheet(workbook, summaryWorksheet, 'Resumen');
    
    // Hoja de inventario
    if (data.inventory && data.inventory.length > 0) {
        const inventoryData = [
            ['ID', 'Producto', 'Categoría', 'Precio', 'Stock', 'Valor Stock', 'Estado Stock', 'Fecha Caducidad', 'Días para Caducar']
        ];
        
        data.inventory.forEach(item => {
            let daysUntilExpiration = '';
            if (item.expiration_date) {
                const today = new Date();
                const expDate = new Date(item.expiration_date);
                const diffTime = expDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                daysUntilExpiration = diffDays;
            }
            
            inventoryData.push([
                item.id,
                item.name,
                item.category_name || 'Sin categoría',
                item.price || 0,
                item.stock || 0,
                item.stock_value || 0,
                item.stock_status || 'Normal',
                item.expiration_date ? formatDate(item.expiration_date) : 'Sin fecha',
                daysUntilExpiration
            ]);
        });
        
        const inventoryWorksheet = XLSX.utils.aoa_to_sheet(inventoryData);
        XLSX.utils.book_append_sheet(workbook, inventoryWorksheet, 'Inventario');
    }
}

function generateCategoriesExcel(workbook, data) {
    // Hoja de resumen
    const summaryData = [
        ['Métrica', 'Valor'],
        ['Total de Categorías', data.summary?.total_categories || 0],
        ['Ingresos Totales', data.summary?.total_revenue || 0],
        ['Total de Productos', data.summary?.total_products || 0]
    ];
    
    const summaryWorksheet = XLSX.utils.aoa_to_sheet(summaryData);
    XLSX.utils.book_append_sheet(workbook, summaryWorksheet, 'Resumen');
    
    // Hoja de categorías
    if (data.categories && data.categories.length > 0) {
        const categoriesData = [
            ['ID', 'Categoría', 'Total Productos', 'Stock Total', 'Vendidos', 'Ingresos', 'Ventas', 'Precio Promedio']
        ];
        
        data.categories.forEach(category => {
            categoriesData.push([
                category.id,
                category.category_name,
                category.total_products || 0,
                category.total_stock || 0,
                category.total_sold || 0,
                category.total_revenue || 0,
                category.sales_count || 0,
                category.avg_product_price || 0
            ]);
        });
        
        const categoriesWorksheet = XLSX.utils.aoa_to_sheet(categoriesData);
        XLSX.utils.book_append_sheet(workbook, categoriesWorksheet, 'Categorías');
    }
}

function getReportTitle(reportType) {
    const titles = {
        'sales': 'Ventas',
        'products': 'Productos',
        'inventory': 'Inventario',
        'categories': 'Categorías'
    };
    return titles[reportType] || 'Reporte';
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
    
    if (format === 'pdf') {
        exportToPDF(currentReportType, currentReportData);
    } else if (format === 'excel') {
        exportToExcel(currentReportType, currentReportData);
    }
}
