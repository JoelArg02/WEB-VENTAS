class ReportsFilters {
    static renderFilters(type) {
        const commonFilters = `
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Reporte</label>
                    <select id="reportType" onchange="changeReportType()" 
                            class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="sales" ${type === 'sales' ? 'selected' : ''}>Ventas</option>
                        <option value="products" ${type === 'products' ? 'selected' : ''}>Productos</option>
                        <option value="inventory" ${type === 'inventory' ? 'selected' : ''}>Inventario</option>
                        <option value="categories" ${type === 'categories' ? 'selected' : ''}>Categorías</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                    <input type="date" id="dateFrom" onchange="applyFilters()" 
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                    <input type="date" id="dateTo" onchange="applyFilters()" 
                           class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                ${this.getSpecificFilters(type)}
            </div>
        `;
        
        return commonFilters;
    }
    
    static getSpecificFilters(type) {
        switch(type) {
            case 'sales':
                return `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vendedor</label>
                        <select id="sellerId" onchange="applyFilters()" 
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos los vendedores</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monto Mínimo</label>
                        <input type="number" id="minAmount" onchange="applyFilters()" placeholder="0" 
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                `;
            case 'products':
                return `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select id="categoryId" onchange="applyFilters()" 
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock Mínimo</label>
                        <input type="number" id="minStock" onchange="applyFilters()" placeholder="0" 
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                `;
            case 'inventory':
                return `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select id="categoryId" onchange="applyFilters()" 
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado Stock</label>
                        <select id="stockStatus" onchange="applyFilters()" 
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos</option>
                            <option value="critical">Crítico (≤5)</option>
                            <option value="low">Bajo (≤10)</option>
                            <option value="medium">Medio (≤20)</option>
                            <option value="good">Bueno (>20)</option>
                        </select>
                    </div>
                `;
            default:
                return `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filtro</label>
                        <select disabled class="w-full border-gray-300 rounded-lg bg-gray-100">
                            <option>Sin filtros adicionales</option>
                        </select>
                    </div>
                `;
        }
    }
    
    static renderQuickReports() {
        return `
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Reportes Rápidos</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <button onclick="generateQuickReport('sales_today')" 
                            class="bg-blue-100 text-blue-700 px-3 py-2 rounded text-sm hover:bg-blue-200 transition-colors">
                        Ventas Hoy
                    </button>
                    <button onclick="generateQuickReport('sales_month')" 
                            class="bg-green-100 text-green-700 px-3 py-2 rounded text-sm hover:bg-green-200 transition-colors">
                        Ventas del Mes
                    </button>
                    <button onclick="generateQuickReport('low_stock')" 
                            class="bg-orange-100 text-orange-700 px-3 py-2 rounded text-sm hover:bg-orange-200 transition-colors">
                        Stock Bajo
                    </button>
                    <button onclick="generateQuickReport('expiring')" 
                            class="bg-red-100 text-red-700 px-3 py-2 rounded text-sm hover:bg-red-200 transition-colors">
                        Por Caducar
                    </button>
                </div>
            </div>
        `;
    }
}
