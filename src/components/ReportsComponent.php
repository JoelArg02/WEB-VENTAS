<?php
require_once __DIR__ . '/../models/ReportsManager.php';

class ReportsComponent {
    private $userData;
    private $permissions;
    private $reportsManager;
    
    public function __construct($userData, $permissions) {
        $this->userData = $userData;
        $this->permissions = $permissions;
        $this->reportsManager = new ReportsManager();
    }
    
    public function render() {
        if (!$this->hasPermission('view_reports')) {
            return '<div class="text-center py-8"><p class="text-gray-500">No tienes permisos para ver reportes.</p></div>';
        }
        
        return $this->renderReportsInterface();
    }
    
    private function hasPermission($permission) {
        return isset($this->permissions[$permission]) && $this->permissions[$permission];
    }
    
    private function renderReportsInterface() {
        ob_start();
        ?>
        <div class="space-y-6">
            <!-- Header del módulo de reportes -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Reportes</h2>
                        <p class="text-gray-600 mt-1">Genera y visualiza reportes de ventas, productos e inventario</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="exportCurrentReport('pdf')" 
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            PDF
                        </button>
                        <button onclick="exportCurrentReport('excel')" 
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Excel
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filtros y controles -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Tipo de reporte -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Reporte</label>
                        <select id="reportType" onchange="changeReportType()" 
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="sales">Ventas</option>
                            <option value="products">Productos</option>
                            <option value="inventory">Inventario</option>
                            <option value="categories">Categorías</option>
                        </select>
                    </div>
                    
                    <!-- Fecha desde -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input type="date" id="dateFrom" onchange="applyFilters()" 
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <!-- Fecha hasta -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input type="date" id="dateTo" onchange="applyFilters()" 
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <!-- Categoría (solo para reportes relevantes) -->
                    <div id="categoryFilter" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select id="categoryId" onchange="applyFilters()" 
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4 flex justify-between items-center">
                    <button onclick="clearFilters()" 
                            class="text-gray-600 hover:text-gray-800 underline">
                        Limpiar filtros
                    </button>
                    <button onclick="generateReport()" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Generar Reporte
                    </button>
                </div>
            </div>
            
            <!-- Área de contenido del reporte -->
            <div id="reportContent" class="bg-white rounded-lg shadow-sm border p-6">
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500">Selecciona un tipo de reporte y haz clic en "Generar Reporte" para comenzar</p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
