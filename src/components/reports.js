let currentReportData = null;
let currentReportType = 'sales';

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('reportFilters')) {
        initializeReports();
    }
});

function initializeReports() {


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


}

// Cambiar tipo de reporte
function changeReportType() {
    const reportTypeSelect = document.getElementById('reportType');
    if (!reportTypeSelect) return;

    currentReportType = reportTypeSelect.value;


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

    const minStockInput = document.getElementById('minStock');
    if (minStockInput) minStockInput.value = '';

    const stockStatusSelect = document.getElementById('stockStatus');
    if (stockStatusSelect) stockStatusSelect.value = '';

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
    const sellerId = document.getElementById('sellerId')?.value || '';
    const minStock = document.getElementById('minStock')?.value || '';
    const stockStatus = document.getElementById('stockStatus')?.value || '';

    if (!dateFrom || !dateTo) {
        if (typeof showMessage === 'function') {
            showMessage('Por favor selecciona las fechas', 'warning');
        }
        return;
    }

    showReportLoading();

    try {
        const requestBody = {
            type: reportType,
            date_from: dateFrom,
            date_to: dateTo,
            category_id: categoryId,
            seller_id: sellerId
        };

        // Agregar parámetros específicos según el tipo de reporte
        if (reportType === 'products' || reportType === 'inventory') {
            if (minStock) {
                requestBody.min_stock = parseInt(minStock);
            }
        }
        
        if (reportType === 'inventory' && stockStatus) {
            requestBody.stock_status = stockStatus;
        }

        const response = await fetch('../api/reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (data.success) {
            currentReportData = data.data;
            renderReport(reportType, data.data, { dateFrom, dateTo, categoryId, sellerId, minStock, stockStatus });
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
            body.quick_report = 'expiring_products';
        }

        const response = await fetch('../api/reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

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

        showReportError('Error de conexión al generar el reporte rápido. ' + error.message);
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
    let generatePromise;

    switch (reportType) {
        case 'sales':
            generateSalesPDF(doc, data, yPosition);
            generatePromise = Promise.resolve(doc);
            break;
        case 'products':
            generatePromise = generateProductsPDF(doc, data, yPosition);
            break;
        case 'inventory':
            generatePromise = generateInventoryPDF(doc, data, yPosition);
            break;
        case 'categories':
            generateCategoriesPDF(doc, data, yPosition);
            generatePromise = Promise.resolve(doc);
            break;
        default:
            generatePromise = Promise.resolve(doc);
    }

    // Guardar el PDF cuando esté listo
    generatePromise.then(finalDoc => {
        finalDoc.save(`reporte_${reportType}_${new Date().toISOString().split('T')[0]}.pdf`);
        showMessage('PDF generado exitosamente', 'success');
    }).catch(error => {
        console.error('Error generating PDF:', error);
        showMessage('Error al generar el PDF', 'error');
    });
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

        // Headers (sin cliente) con bordes
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        
        // Dibujar rectángulo para headers
        doc.setFillColor(34, 197, 94); // Color verde
        doc.setTextColor(255, 255, 255); // Texto blanco
        doc.rect(15, y - 5, 185, 12, 'F');
        doc.rect(15, y - 5, 185, 12, 'S');
        
        doc.text('ID', 20, y);
        doc.text('Vendedor', 50, y);
        doc.text('Total', 100, y);
        doc.text('Productos', 130, y);
        doc.text('Fecha', 170, y);
        doc.setFont(undefined, 'normal');
        doc.setTextColor(0, 0, 0); // Volver a texto negro
        y += 8;

        // Datos (sin cliente) con bordes alternados
        data.details.slice(0, 25).forEach((sale, index) => {
            // Dibujar fila con borde alternado
            if (index % 2 === 0) {
                doc.setFillColor(240, 253, 244); // Color verde claro para filas pares
                doc.rect(15, y - 3, 185, 8, 'F');
            }
            
            // Borde de la fila
            doc.setDrawColor(200, 200, 200);
            doc.rect(15, y - 3, 185, 8, 'S');
            
            doc.text(String(sale.id), 20, y);
            doc.text(sale.seller_name || 'N/A', 50, y);
            doc.text(formatCurrency(sale.total_amount), 100, y);
            doc.text(String(sale.total_items || 0), 130, y);
            doc.text(formatDate(sale.sale_date), 170, y);
            y += 6;

            if (y > 250) {
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

    // Tabla de productos con imágenes
    if (products.length > 0) {
        y += 10;
        doc.setFontSize(14);
        doc.text('Productos Más Vendidos', 20, y);
        y += 15;

        // Headers con bordes
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        
        // Dibujar rectángulo para headers
        doc.setFillColor(52, 58, 64); // Color gris oscuro
        doc.setTextColor(255, 255, 255); // Texto blanco
        doc.rect(15, y - 5, 185, 12, 'F');
        doc.rect(15, y - 5, 185, 12, 'S');
        
        doc.text('Imagen', 20, y);
        doc.text('Producto', 45, y);
        doc.text('Categoría', 100, y);
        doc.text('Precio', 140, y);
        doc.text('Vendidos', 165, y);
        doc.text('Ingresos', 185, y);
        doc.setFont(undefined, 'normal');
        doc.setTextColor(0, 0, 0); // Volver a texto negro
        y += 10;

        // Datos con imágenes
        const imagePromises = [];
        products.slice(0, 20).forEach((product, index) => {
            if (product.image) {
                const promise = loadImageForPDF(product.image).then(imageData => {
                    return { imageData, product, index };
                }).catch(() => {
                    return { imageData: null, product, index };
                });
                imagePromises.push(promise);
            } else {
                imagePromises.push(Promise.resolve({ imageData: null, product, index }));
            }
        });

        return Promise.all(imagePromises).then(results => {
            results.forEach(({ imageData, product, index }) => {
                const rowY = y + (index * 25);

                // Dibujar fila con borde alternado
                if (index % 2 === 0) {
                    doc.setFillColor(248, 249, 250); // Color gris claro para filas pares
                    doc.rect(15, rowY - 10, 185, 22, 'F');
                }
                
                // Borde de la fila
                doc.setDrawColor(200, 200, 200);
                doc.rect(15, rowY - 10, 185, 22, 'S');

                // Imagen
                if (imageData) {
                    try {
                        doc.addImage(imageData, 'JPEG', 20, rowY - 8, 20, 20);
                        // Borde para la imagen
                        doc.setDrawColor(100, 100, 100);
                        doc.rect(20, rowY - 8, 20, 20, 'S');
                    } catch (e) {
                        console.error('Error adding image to PDF:', e);
                    }
                }

                // Datos del producto
                const productName = product.name.length > 20 ? product.name.substring(0, 20) + '...' : product.name;
                doc.text(productName, 45, rowY);
                doc.text(product.category_name || 'N/A', 100, rowY);
                doc.text(formatCurrency(product.price), 140, rowY);
                doc.text(String(product.total_sold || 0), 165, rowY);
                doc.text(formatCurrency(product.total_revenue || 0), 185, rowY);

                // Verificar si necesitamos nueva página
                if (rowY > 230) {
                    doc.addPage();
                    y = 20;
                }
            });

            return doc;
        });
    }

    return Promise.resolve(doc);
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

    // Tabla de inventario con imágenes
    if (inventory.length > 0) {
        y += 10;
        doc.setFontSize(14);
        doc.text('Inventario Detallado', 20, y);
        y += 15;

        // Headers
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('Imagen', 20, y);
        doc.text('Producto', 45, y);
        doc.text('Categoría', 100, y);
        doc.text('Stock', 140, y);
        doc.text('Precio', 165, y);
        doc.text('Valor', 185, y);
        doc.setFont(undefined, 'normal');
        y += 10;

        // Datos con imágenes
        const imagePromises = [];
        inventory.slice(0, 20).forEach((item, index) => {
            if (item.image) {
                const promise = loadImageForPDF(item.image).then(imageData => {
                    return { imageData, item, index };
                }).catch(() => {
                    return { imageData: null, item, index };
                });
                imagePromises.push(promise);
            } else {
                imagePromises.push(Promise.resolve({ imageData: null, item, index }));
            }
        });

        return Promise.all(imagePromises).then(results => {
            results.forEach(({ imageData, item, index }) => {
                const rowY = y + (index * 25);

                // Imagen
                if (imageData) {
                    try {
                        doc.addImage(imageData, 'JPEG', 20, rowY - 8, 20, 20);
                    } catch (e) {
                        console.error('Error adding image to PDF:', e);
                    }
                }

                // Datos del producto
                const productName = item.name.length > 20 ? item.name.substring(0, 20) + '...' : item.name;
                doc.text(productName, 45, rowY);
                doc.text(item.category_name || 'N/A', 100, rowY);
                doc.text(String(item.stock), 140, rowY);
                doc.text(formatCurrency(item.price), 165, rowY);
                doc.text(formatCurrency(item.stock_value), 185, rowY);

                // Estado del stock con color
                const stockStatus = item.stock_status || 'Stock Bueno';
                if (stockStatus === 'Stock Crítico') {
                    doc.setTextColor(255, 0, 0); // Rojo
                } else if (stockStatus === 'Stock Bajo') {
                    doc.setTextColor(255, 165, 0); // Naranja
                } else {
                    doc.setTextColor(0, 0, 0); // Negro
                }

                // Verificar si necesitamos nueva página
                if (rowY > 230) {
                    doc.addPage();
                    y = 20;
                }
            });

            doc.setTextColor(0, 0, 0); // Resetear color
            return doc;
        });
    }

    return Promise.resolve(doc);
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
    // Crear hoja principal con formato mejorado
    const worksheet = XLSX.utils.aoa_to_sheet([]);
    
    // Información de la empresa (header)
    const companyInfo = [
        ['SISTEMA DE VENTAS - REPORTE DE VENTAS'],
        [''],
        ['Fecha de Generación:', new Date().toLocaleDateString('es-ES')],
        ['Tipo de Reporte:', 'Resumen de Ventas'],
        ['Período:', data.period ? `${formatDate(data.period.from)} - ${formatDate(data.period.to)}` : 'Todos los registros'],
        ['Vendedor:', data.seller_id ? 'Filtrado por vendedor' : 'Todos los vendedores'],
        [''],
        ['RESUMEN EJECUTIVO'],
        ['Total de Ventas:', data.summary?.total_sales || 0],
        ['Ingresos Totales:', formatCurrency(data.summary?.total_revenue || 0)],
        ['Venta Promedio:', formatCurrency(data.summary?.avg_sale_amount || 0)],
        ['Productos Vendidos:', data.summary?.total_items_sold || 0],
        [''],
        ['DETALLE DE VENTAS']
    ];
    
    // Agregar información de la empresa
    companyInfo.forEach((row, index) => {
        XLSX.utils.sheet_add_aoa(worksheet, [row], {origin: `A${index + 1}`});
    });
    
    // Headers de la tabla de ventas (fila 15)
    const headers = [
        'ID Venta', 'Vendedor', 'Subtotal', 'IVA', 'Total', 
        'Dinero Recibido', 'Cambio', 'Fecha', 'Items', 'Productos'
    ];
    XLSX.utils.sheet_add_aoa(worksheet, [headers], {origin: 'A15'});
    
    // Datos de ventas
    if (data.details && data.details.length > 0) {
        const salesData = data.details.map(sale => [
            sale.id,
            sale.seller_name || 'N/A',
            parseFloat(sale.subtotal || 0),
            parseFloat(sale.iva_amount || 0),
            parseFloat(sale.total_amount || 0),
            parseFloat(sale.money_received || 0),
            parseFloat(sale.change_amount || 0),
            formatDate(sale.sale_date),
            parseInt(sale.total_items || 0),
            sale.products_detail || 'N/A'
        ]);
        
        XLSX.utils.sheet_add_aoa(worksheet, salesData, {origin: 'A16'});
    }
    
    // Aplicar estilos y formato
    const range = XLSX.utils.decode_range(worksheet['!ref']);
    
    // Establecer anchos de columna
    worksheet['!cols'] = [
        {wch: 8},   // ID
        {wch: 15},  // Vendedor
        {wch: 12},  // Subtotal
        {wch: 10},  // IVA
        {wch: 12},  // Total
        {wch: 12},  // Recibido
        {wch: 10},  // Cambio
        {wch: 12},  // Fecha
        {wch: 8},   // Items
        {wch: 40}   // Productos
    ];
    
    // Formato para título principal
    if (worksheet['A1']) {
        worksheet['A1'].s = {
            font: { bold: true, sz: 16, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "059669" } },
            alignment: { horizontal: "center" }
        };
    }
    
    // Formato para headers de tabla
    for (let col = 0; col < headers.length; col++) {
        const cellAddress = XLSX.utils.encode_cell({r: 14, c: col});
        if (worksheet[cellAddress]) {
            worksheet[cellAddress].s = {
                font: { bold: true, color: { rgb: "FFFFFF" } },
                fill: { fgColor: { rgb: "047857" } },
                alignment: { horizontal: "center" },
                border: {
                    top: { style: "thin", color: { rgb: "000000" } },
                    bottom: { style: "thin", color: { rgb: "000000" } },
                    left: { style: "thin", color: { rgb: "000000" } },
                    right: { style: "thin", color: { rgb: "000000" } }
                }
            };
        }
    }
    
    // Formato para datos de ventas
    if (data.details && data.details.length > 0) {
        for (let row = 15; row < 15 + data.details.length; row++) {
            for (let col = 0; col < headers.length; col++) {
                const cellAddress = XLSX.utils.encode_cell({r: row, c: col});
                if (worksheet[cellAddress]) {
                    const isEvenRow = (row - 15) % 2 === 0;
                    worksheet[cellAddress].s = {
                        fill: { fgColor: { rgb: isEvenRow ? "F0FDF4" : "FFFFFF" } },
                        border: {
                            top: { style: "thin", color: { rgb: "E2E8F0" } },
                            bottom: { style: "thin", color: { rgb: "E2E8F0" } },
                            left: { style: "thin", color: { rgb: "E2E8F0" } },
                            right: { style: "thin", color: { rgb: "E2E8F0" } }
                        },
                        alignment: { horizontal: col === 1 || col === 9 ? "left" : "center" }
                    };
                    
                    // Formato especial para montos
                    if (col >= 2 && col <= 6) { // Subtotal, IVA, Total, Recibido, Cambio
                        worksheet[cellAddress].z = '"$"#,##0.00';
                    }
                    if (col === 8) { // Items
                        worksheet[cellAddress].z = '#,##0';
                    }
                }
            }
        }
    }
    
    // Agregar la hoja al workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte de Ventas');
}

function generateProductsExcel(workbook, data) {
    // Crear hoja principal con logo y formato mejorado
    const worksheet = XLSX.utils.aoa_to_sheet([]);
    
    // Información de la empresa (header con espacio para logo)
    const companyInfo = [
        ['', '', '', 'SISTEMA DE VENTAS - REPORTE DE PRODUCTOS'],
        [''],
        ['', '', '', 'Fecha de Generación:', new Date().toLocaleDateString('es-ES')],
        ['', '', '', 'Tipo de Reporte:', 'Productos Más Vendidos'],
        ['', '', '', 'Período:', data.period ? `${formatDate(data.period.from)} - ${formatDate(data.period.to)}` : 'Todos los registros'],
        [''],
        ['RESUMEN EJECUTIVO'],
        ['Total de Productos:', data.summary?.total_products || 0],
        ['Unidades Vendidas:', data.summary?.total_sold || 0],
        ['Ingresos por Productos:', formatCurrency(data.summary?.total_revenue || 0)],
        [''],
        ['DETALLE DE PRODUCTOS']
    ];
    
    // Agregar información de la empresa
    companyInfo.forEach((row, index) => {
        XLSX.utils.sheet_add_aoa(worksheet, [row], {origin: `A${index + 1}`});
    });
    
    // Intentar agregar logo de la empresa
    try {
        const logoPath = '../assets/logo.png';
        addLogoToExcelSheet(worksheet, logoPath);
    } catch (error) {
        console.log('No se pudo cargar el logo para Excel:', error);
    }
    
    // Formato para título principal con merge
    const titleRange = XLSX.utils.encode_range({s: {c: 3, r: 0}, e: {c: 7, r: 0}});
    worksheet['!merges'] = worksheet['!merges'] || [];
    worksheet['!merges'].push(XLSX.utils.decode_range(titleRange));
    
    // Headers de la tabla de productos (fila 13)
    const headers = [
        'ID', 'Nombre del Producto', 'Categoría', 'Precio Unitario', 
        'Stock Actual', 'Unidades Vendidas', 'Ingresos Generados', 'Total de Ventas'
    ];
    XLSX.utils.sheet_add_aoa(worksheet, [headers], {origin: 'A13'});
    
    // Datos de productos
    if (data.products && data.products.length > 0) {
        const productsData = data.products.map(product => [
            product.id,
            product.name,
            product.category_name || 'Sin categoría',
            parseFloat(product.price || 0),
            parseInt(product.stock || 0),
            parseInt(product.total_sold || 0),
            parseFloat(product.total_revenue || 0),
            parseInt(product.sales_count || 0)
        ]);
        
        XLSX.utils.sheet_add_aoa(worksheet, productsData, {origin: 'A14'});
    }
    
    // Aplicar estilos y formato
    const range = XLSX.utils.decode_range(worksheet['!ref']);
    
    // Establecer anchos de columna
    worksheet['!cols'] = [
        {wch: 8},   // ID
        {wch: 25},  // Nombre
        {wch: 15},  // Categoría
        {wch: 12},  // Precio
        {wch: 10},  // Stock
        {wch: 12},  // Vendidos
        {wch: 15},  // Ingresos
        {wch: 12}   // Ventas
    ];
    
    // Formato para títulos principales
    if (worksheet['A1']) {
        worksheet['A1'].s = {
            font: { bold: true, sz: 16, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "2563EB" } },
            alignment: { horizontal: "center" }
        };
    }
    
    // Formato para headers de tabla
    for (let col = 0; col < headers.length; col++) {
        const cellAddress = XLSX.utils.encode_cell({r: 12, c: col});
        if (worksheet[cellAddress]) {
            worksheet[cellAddress].s = {
                font: { bold: true, color: { rgb: "FFFFFF" } },
                fill: { fgColor: { rgb: "4F46E5" } },
                alignment: { horizontal: "center" },
                border: {
                    top: { style: "thin", color: { rgb: "000000" } },
                    bottom: { style: "thin", color: { rgb: "000000" } },
                    left: { style: "thin", color: { rgb: "000000" } },
                    right: { style: "thin", color: { rgb: "000000" } }
                }
            };
        }
    }
    
    // Formato para datos de productos (alternar colores)
    if (data.products && data.products.length > 0) {
        for (let row = 13; row < 13 + data.products.length; row++) {
            for (let col = 0; col < headers.length; col++) {
                const cellAddress = XLSX.utils.encode_cell({r: row, c: col});
                if (worksheet[cellAddress]) {
                    const isEvenRow = (row - 13) % 2 === 0;
                    worksheet[cellAddress].s = {
                        fill: { fgColor: { rgb: isEvenRow ? "F8FAFC" : "FFFFFF" } },
                        border: {
                            top: { style: "thin", color: { rgb: "E2E8F0" } },
                            bottom: { style: "thin", color: { rgb: "E2E8F0" } },
                            left: { style: "thin", color: { rgb: "E2E8F0" } },
                            right: { style: "thin", color: { rgb: "E2E8F0" } }
                        },
                        alignment: { horizontal: col === 1 ? "left" : "center" } // Nombre a la izquierda, resto centrado
                    };
                    
                    // Formato especial para montos
                    if (col === 3 || col === 6) { // Precio e Ingresos
                        worksheet[cellAddress].z = '"$"#,##0.00';
                    }
                    if (col === 4 || col === 5 || col === 7) { // Stock, Vendidos, Ventas
                        worksheet[cellAddress].z = '#,##0';
                    }
                }
            }
        }
    }
    
    // Agregar la hoja al workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte de Productos');
    
    // Hoja adicional con solo datos para análisis
    const dataOnlyWorksheet = XLSX.utils.aoa_to_sheet([
        ['ID', 'Producto', 'Categoría', 'Precio', 'Stock', 'Vendidos', 'Ingresos', 'Ventas']
    ]);
    
    if (data.products && data.products.length > 0) {
        const simpleData = data.products.map(product => [
            product.id,
            product.name,
            product.category_name || 'Sin categoría',
            product.price || 0,
            product.stock || 0,
            product.total_sold || 0,
            product.total_revenue || 0,
            product.sales_count || 0
        ]);
        
        XLSX.utils.sheet_add_aoa(dataOnlyWorksheet, simpleData, {origin: 'A2'});
    }
    
    XLSX.utils.book_append_sheet(workbook, dataOnlyWorksheet, 'Datos Productos');
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



    if (format === 'pdf') {
        exportToPDF(currentReportType, currentReportData);
    } else if (format === 'excel') {
        exportToExcel(currentReportType, currentReportData);
    }
}

// Función auxiliar para cargar imágenes para PDF
function loadImageForPDF(imageSrc) {
    return new Promise((resolve, reject) => {
        if (!imageSrc) {
            reject(new Error('No image source provided'));
            return;
        }

        const img = new Image();
        img.crossOrigin = 'anonymous';

        img.onload = function () {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // Ajustar tamaño del canvas
                canvas.width = 100;
                canvas.height = 100;

                // Dibujar la imagen redimensionada
                ctx.drawImage(img, 0, 0, 100, 100);

                // Convertir a base64
                const dataURL = canvas.toDataURL('image/jpeg', 0.8);
                resolve(dataURL);
            } catch (error) {
                console.error('Error processing image:', error);
                reject(error);
            }
        };

        img.onerror = function () {
            console.error('Error loading image:', imageSrc);
            reject(new Error('Failed to load image'));
        };

        // Manejar diferentes formatos de imagen
        let processedSrc = imageSrc;
        if (imageSrc.startsWith('data:image')) {
            // Si ya es base64, usar directamente
            processedSrc = imageSrc;
        } else if (imageSrc.startsWith('/') || imageSrc.startsWith('http')) {
            // URL absoluta o relativa
            processedSrc = imageSrc;
        } else {
            // Asumir que es base64 sin prefijo
            processedSrc = `data:image/jpeg;base64,${imageSrc}`;
        }

        img.src = processedSrc;
    });
}

// Función auxiliar para agregar logo al Excel
function addLogoToExcelSheet(worksheet, logoPath) {
    // Por ahora solo agregamos un placeholder para el logo
    // En una implementación completa, se podría usar una librería que soporte imágenes en Excel
    const logoCell = 'A1';
    if (worksheet[logoCell]) {
        worksheet[logoCell].v = 'LOGO';
        worksheet[logoCell].s = {
            font: { bold: true, sz: 14, color: { rgb: "2563EB" } },
            alignment: { horizontal: "center", vertical: "center" },
            fill: { fgColor: { rgb: "E3F2FD" } },
            border: {
                top: { style: "thin", color: { rgb: "2563EB" } },
                bottom: { style: "thin", color: { rgb: "2563EB" } },
                left: { style: "thin", color: { rgb: "2563EB" } },
                right: { style: "thin", color: { rgb: "2563EB" } }
            }
        };
    }
}
