<?php

class ReportsComponent {
    private $userData;
    private $permissions;
    
    public function __construct($userData, $permissions) {
        $this->userData = $userData;
        $this->permissions = $permissions;
    }
    
    public function render() {
        return '
        <div class="space-y-6" id="reportsContainer">
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-gray-800">Centro de Reportes</h2>
                <div class="text-sm text-gray-600">
                    Sistema avanzado de reportes y análisis
                </div>
            </div>
            
            <!-- Filtros Globales -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filtros de Fecha</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                        <input type="date" id="dateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                        <input type="date" id="dateTo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Periodo</label>
                        <select id="periodSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="today">Hoy</option>
                            <option value="yesterday">Ayer</option>
                            <option value="this_week">Esta Semana</option>
                            <option value="last_week">Semana Pasada</option>
                            <option value="this_month" selected>Este Mes</option>
                            <option value="last_month">Mes Pasado</option>
                            <option value="this_year">Este Año</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="applyDateFilters()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Aplicar Filtros
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de Reportes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                
                <!-- Reporte de Ventas -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Reporte de Ventas</h3>
                                    <p class="text-sm text-gray-600">Análisis detallado de ventas por periodo</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button onclick="generateSalesReport(\'pdf\')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Generar PDF</span>
                            </button>
                            <button onclick="generateSalesReport(\'excel\')" class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Generar Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Reporte de Productos -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Reporte de Productos</h3>
                                    <p class="text-sm text-gray-600">Inventario, stock y productos más vendidos</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button onclick="generateProductsReport(\'pdf\')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Generar PDF</span>
                            </button>
                            <button onclick="generateProductsReport(\'excel\')" class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Generar Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Reporte de Stock Bajo -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Stock Bajo</h3>
                                    <p class="text-sm text-gray-600">Productos que necesitan reposición</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button onclick="generateLowStockReport(\'pdf\')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Generar PDF</span>
                            </button>
                            <button onclick="generateLowStockReport(\'excel\')" class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Generar Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Reporte por Categorías -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Por Categorías</h3>
                                    <p class="text-sm text-gray-600">Ventas y productos por categoría</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button onclick="generateCategoriesReport(\'pdf\')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Generar PDF</span>
                            </button>
                            <button onclick="generateCategoriesReport(\'excel\')" class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Generar Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Reporte de Vendedores -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Por Vendedores</h3>
                                    <p class="text-sm text-gray-600">Performance de ventas por usuario</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button onclick="generateSellersReport(\'pdf\')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Generar PDF</span>
                            </button>
                            <button onclick="generateSellersReport(\'excel\')" class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Generar Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Reporte de Productos Próximos a Caducar -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Próximos a Caducar</h3>
                                    <p class="text-sm text-gray-600">Productos por vencer en los próximos días</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button onclick="generateExpiringReport(\'pdf\')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Generar PDF</span>
                            </button>
                            <button onclick="generateExpiringReport(\'excel\')" class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Generar Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Vista Previa del Reporte -->
            <div id="reportPreview" class="hidden bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Vista Previa del Reporte</h3>
                    <button onclick="hideReportPreview()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="reportPreviewContent">
                    <!-- Aquí se mostrará la vista previa del reporte -->
                </div>
            </div>
            
            <!-- Loading overlay -->
            <div id="reportLoading" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <div class="text-lg">Generando reporte...</div>
                </div>
            </div>
        </div>
        
        <script>
        let currentDateFilter = {
            from: null,
            to: null,
            period: "this_month"
        };
        
        // Aplicar filtros de fecha
        function applyDateFilters() {
            const periodSelect = document.getElementById("periodSelect");
            const dateFrom = document.getElementById("dateFrom");
            const dateTo = document.getElementById("dateTo");
            
            currentDateFilter.period = periodSelect.value;
            
            if (periodSelect.value === "custom") {
                currentDateFilter.from = dateFrom.value;
                currentDateFilter.to = dateTo.value;
                
                if (!currentDateFilter.from || !currentDateFilter.to) {
                    showMessage("Por favor seleccione ambas fechas para el periodo personalizado", "error");
                    return;
                }
            } else {
                // Calcular fechas basadas en el periodo seleccionado
                const dates = calculatePeriodDates(periodSelect.value);
                currentDateFilter.from = dates.from;
                currentDateFilter.to = dates.to;
                
                dateFrom.value = currentDateFilter.from;
                dateTo.value = currentDateFilter.to;
            }
            
            showMessage("Filtros de fecha aplicados correctamente", "success");
        }
        
        // Calcular fechas basadas en el periodo
        function calculatePeriodDates(period) {
            const today = new Date();
            const year = today.getFullYear();
            const month = today.getMonth();
            const day = today.getDate();
            
            switch (period) {
                case "today":
                    return {
                        from: formatDate(today),
                        to: formatDate(today)
                    };
                case "yesterday":
                    const yesterday = new Date(today);
                    yesterday.setDate(day - 1);
                    return {
                        from: formatDate(yesterday),
                        to: formatDate(yesterday)
                    };
                case "this_week":
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(day - today.getDay());
                    return {
                        from: formatDate(startOfWeek),
                        to: formatDate(today)
                    };
                case "last_week":
                    const startOfLastWeek = new Date(today);
                    startOfLastWeek.setDate(day - today.getDay() - 7);
                    const endOfLastWeek = new Date(startOfLastWeek);
                    endOfLastWeek.setDate(startOfLastWeek.getDate() + 6);
                    return {
                        from: formatDate(startOfLastWeek),
                        to: formatDate(endOfLastWeek)
                    };
                case "this_month":
                    return {
                        from: formatDate(new Date(year, month, 1)),
                        to: formatDate(today)
                    };
                case "last_month":
                    return {
                        from: formatDate(new Date(year, month - 1, 1)),
                        to: formatDate(new Date(year, month, 0))
                    };
                case "this_year":
                    return {
                        from: formatDate(new Date(year, 0, 1)),
                        to: formatDate(today)
                    };
                default:
                    return {
                        from: formatDate(new Date(year, month, 1)),
                        to: formatDate(today)
                    };
            }
        }
        
        // Formatear fecha para input
        function formatDate(date) {
            return date.toISOString().split("T")[0];
        }
        
        // Mostrar loading
        function showReportLoading() {
            document.getElementById("reportLoading").classList.remove("hidden");
        }
        
        // Ocultar loading
        function hideReportLoading() {
            document.getElementById("reportLoading").classList.add("hidden");
        }
        
        // Funciones de generación de reportes
        async function generateSalesReport(format) {
            showReportLoading();
            try {
                const response = await fetch("api/reports.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        type: "sales",
                        format: format,
                        filters: currentDateFilter
                    })
                });
                
                if (response.ok) {
                    if (format === "pdf" || format === "excel") {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.style.display = "none";
                        a.href = url;
                        a.download = `reporte_ventas_${new Date().toISOString().split("T")[0]}.${format === "pdf" ? "pdf" : "xlsx"}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                    showMessage("Reporte de ventas generado correctamente", "success");
                }
            } catch (error) {
                console.error("Error generating sales report:", error);
                showMessage("Error al generar el reporte de ventas", "error");
            } finally {
                hideReportLoading();
            }
        }
        
        async function generateProductsReport(format) {
            showReportLoading();
            try {
                const response = await fetch("api/reports.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        type: "products",
                        format: format,
                        filters: currentDateFilter
                    })
                });
                
                if (response.ok) {
                    if (format === "pdf" || format === "excel") {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.style.display = "none";
                        a.href = url;
                        a.download = `reporte_productos_${new Date().toISOString().split("T")[0]}.${format === "pdf" ? "pdf" : "xlsx"}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                    showMessage("Reporte de productos generado correctamente", "success");
                }
            } catch (error) {
                console.error("Error generating products report:", error);
                showMessage("Error al generar el reporte de productos", "error");
            } finally {
                hideReportLoading();
            }
        }
        
        async function generateLowStockReport(format) {
            showReportLoading();
            try {
                const response = await fetch("api/reports.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        type: "low_stock",
                        format: format
                    })
                });
                
                if (response.ok) {
                    if (format === "pdf" || format === "excel") {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.style.display = "none";
                        a.href = url;
                        a.download = `reporte_stock_bajo_${new Date().toISOString().split("T")[0]}.${format === "pdf" ? "pdf" : "xlsx"}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                    showMessage("Reporte de stock bajo generado correctamente", "success");
                }
            } catch (error) {
                console.error("Error generating low stock report:", error);
                showMessage("Error al generar el reporte de stock bajo", "error");
            } finally {
                hideReportLoading();
            }
        }
        
        async function generateCategoriesReport(format) {
            showReportLoading();
            try {
                const response = await fetch("api/reports.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        type: "categories",
                        format: format,
                        filters: currentDateFilter
                    })
                });
                
                if (response.ok) {
                    if (format === "pdf" || format === "excel") {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.style.display = "none";
                        a.href = url;
                        a.download = `reporte_categorias_${new Date().toISOString().split("T")[0]}.${format === "pdf" ? "pdf" : "xlsx"}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                    showMessage("Reporte por categorías generado correctamente", "success");
                }
            } catch (error) {
                console.error("Error generating categories report:", error);
                showMessage("Error al generar el reporte por categorías", "error");
            } finally {
                hideReportLoading();
            }
        }
        
        async function generateSellersReport(format) {
            showReportLoading();
            try {
                const response = await fetch("api/reports.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        type: "sellers",
                        format: format,
                        filters: currentDateFilter
                    })
                });
                
                if (response.ok) {
                    if (format === "pdf" || format === "excel") {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.style.display = "none";
                        a.href = url;
                        a.download = `reporte_vendedores_${new Date().toISOString().split("T")[0]}.${format === "pdf" ? "pdf" : "xlsx"}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                    showMessage("Reporte de vendedores generado correctamente", "success");
                }
            } catch (error) {
                console.error("Error generating sellers report:", error);
                showMessage("Error al generar el reporte de vendedores", "error");
            } finally {
                hideReportLoading();
            }
        }
        
        async function generateExpiringReport(format) {
            showReportLoading();
            try {
                const response = await fetch("api/reports.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        type: "expiring",
                        format: format
                    })
                });
                
                if (response.ok) {
                    if (format === "pdf" || format === "excel") {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.style.display = "none";
                        a.href = url;
                        a.download = `reporte_proximos_caducar_${new Date().toISOString().split("T")[0]}.${format === "pdf" ? "pdf" : "xlsx"}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                    showMessage("Reporte de productos próximos a caducar generado correctamente", "success");
                }
            } catch (error) {
                console.error("Error generating expiring products report:", error);
                showMessage("Error al generar el reporte de productos próximos a caducar", "error");
            } finally {
                hideReportLoading();
            }
        }
        
        function hideReportPreview() {
            document.getElementById("reportPreview").classList.add("hidden");
        }
        
        // Inicializar filtros al cargar
        document.addEventListener("DOMContentLoaded", function() {
            const periodSelect = document.getElementById("periodSelect");
            if (periodSelect) {
                periodSelect.addEventListener("change", function() {
                    if (this.value === "custom") {
                        document.getElementById("dateFrom").parentElement.style.display = "block";
                        document.getElementById("dateTo").parentElement.style.display = "block";
                    } else {
                        const dates = calculatePeriodDates(this.value);
                        document.getElementById("dateFrom").value = dates.from;
                        document.getElementById("dateTo").value = dates.to;
                    }
                });
                
                // Aplicar filtros iniciales
                applyDateFilters();
            }
        });
        </script>
        ';
    }
}
