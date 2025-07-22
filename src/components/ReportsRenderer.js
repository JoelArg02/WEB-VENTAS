class ReportsRenderer {
    static renderProductsReport(data, filters) {
        const summary = data.summary || {};
        const products = data.products || [];
        
        return `
            <div class="space-y-6">
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
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Productos Más Vendidos</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            ${product.image ? 
                                                `<img src="${this.formatImageSrc(product.image)}" class="w-12 h-12 object-cover rounded-lg" alt="${product.name}" onerror="this.parentElement.innerHTML='<div class=\\'w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center\\'><svg class=\\'w-6 h-6 text-gray-400\\' fill=\\'none\\' stroke=\\'currentColor\\' viewBox=\\'0 0 24 24\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'2\\' d=\\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\\'></path></svg></div>'">` : 
                                                `<div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>`
                                            }
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.category_name || 'Sin categoría'}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(product.price)}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.total_sold || 0}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(product.total_revenue || 0)}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="px-2 py-1 text-xs rounded-full ${this.getStockStatusColor(product.stock)}">
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

    static renderInventoryReport(data, filters) {
        const summary = data.summary || {};
        const inventory = data.inventory || [];
        
        return `
            <div class="space-y-6">
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
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Inventario Detallado</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caducidad</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${inventory.map(item => `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            ${item.image ? 
                                                `<img src="${this.formatImageSrc(item.image)}" class="w-12 h-12 object-cover rounded-lg" alt="${item.name}" onerror="this.parentElement.innerHTML='<div class=\\'w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center\\'><svg class=\\'w-6 h-6 text-gray-400\\' fill=\\'none\\' stroke=\\'currentColor\\' viewBox=\\'0 0 24 24\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'2\\' d=\\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\\'></path></svg></div>'">` : 
                                                `<div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>`
                                            }
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.name}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.category_name || 'Sin categoría'}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(item.price)}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.stock}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(item.stock_value)}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="px-2 py-1 text-xs rounded-full ${this.getStockStatusColor(item.stock)}">
                                                ${item.stock_status || 'Normal'}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${item.expiration_date ? 
                                                `<span class="px-2 py-1 text-xs rounded-full ${this.getExpirationColor(item.expiration_date)}">
                                                    ${formatDate(item.expiration_date)}
                                                </span>` : 
                                                '<span class="text-gray-400">Sin fecha</span>'
                                            }
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

    static getStockStatusColor(stock) {
        if (stock <= 5) return 'bg-red-100 text-red-800';
        if (stock <= 10) return 'bg-yellow-100 text-yellow-800';
        if (stock <= 20) return 'bg-blue-100 text-blue-800';
        return 'bg-green-100 text-green-800';
    }

    static getExpirationColor(expirationDate) {
        const today = new Date();
        const expDate = new Date(expirationDate);
        const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
        
        if (diffDays <= 7) return 'bg-red-100 text-red-800';
        if (diffDays <= 15) return 'bg-orange-100 text-orange-800';
        if (diffDays <= 30) return 'bg-yellow-100 text-yellow-800';
        return 'bg-green-100 text-green-800';
    }
    
    static renderSalesReport(data, filters) {
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(sale.total_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    static renderCategoriesReport(data, filters) {
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

    // Método para formatear correctamente las imágenes base64
    static formatImageSrc(imageData) {
        if (!imageData) return '';
        
        // Si ya tiene el prefijo data:image, lo devolvemos tal como está
        if (imageData.startsWith('data:image/')) {
            return imageData;
        }
        
        // Si es solo el string base64, agregamos el prefijo
        return `data:image/jpeg;base64,${imageData}`;
    }

    // Método para generar botones de exportación
    static renderExportButtons(reportType, reportData) {
        return `
            <div class="flex space-x-3 mb-4">
                <button 
                    onclick="exportToPDF('${reportType}', JSON.stringify(reportData))"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Exportar PDF</span>
                </button>
                
                <button 
                    onclick="exportToExcel('${reportType}', JSON.stringify(reportData))"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Exportar Excel</span>
                </button>
                
                <button 
                    onclick="window.print()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span>Imprimir</span>
                </button>
            </div>
        `;
    }
}
