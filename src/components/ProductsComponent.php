<?php
class ProductsComponent {
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
        <div class="space-y-6" id="productsTab">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Productos</h2>
                <?php if ($this->permissions['create_product']): ?>
                <button onclick="openCreateProductModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    + Nuevo Producto
                </button>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Productos</h3>
                <div id="productsLoading" class="flex justify-center items-center h-32">
                    <div class="text-gray-500">Cargando productos...</div>
                </div>
                <div class="overflow-x-auto hidden" id="productsTable">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Imagen</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Precio</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Stock</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Categoría</th>
                                <?php if ($this->permissions['edit_product'] || $this->permissions['delete_product']): ?>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="productsTableBody">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
            async function loadProductsData() {
                try {
                    const result = await apiRequest('/api/products.php');
                    
                    if (result.success) {
                        renderProductsTable(result.data);
                        document.getElementById('productsLoading').classList.add('hidden');
                        document.getElementById('productsTable').classList.remove('hidden');
                    } else {
                        showError('Error al cargar productos: ' + result.message);
                    }
                } catch (error) {
                    showError('Error de conexión al cargar productos');
                    console.error('Products error:', error);
                }
            }
            
            function renderProductsTable(products) {
                const tbody = document.getElementById('productsTableBody');
                let html = '';
                
                products.forEach(product => {
                    const stockClass = product.stock <= 10 ? 'text-red-600 font-semibold' : 'text-gray-900';
                    
                    // Generar imagen
                    let imageHtml = '';
                    if (product.image && product.image.trim()) {
                        imageHtml = `<img src="${product.image}" class="h-12 w-12 rounded-lg object-cover border" alt="${product.name}">`;
                    } else {
                        imageHtml = `<div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center border">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                     </div>`;
                    }
                    
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">${imageHtml}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 font-medium">${product.name}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${formatCurrency(product.price)}</td>
                            <td class="px-4 py-2 text-sm ${stockClass}">${product.stock}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${product.category_name || 'Sin categoría'}</td>`;
                            
                    <?php if ($this->permissions['edit_product'] || $this->permissions['delete_product']): ?>
                    html += '<td class="px-4 py-2 text-sm">';
                    <?php if ($this->permissions['edit_product']): ?>
                    html += `<button onclick="editProduct(${product.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    <?php endif; ?>
                    <?php if ($this->permissions['delete_product']): ?>
                    html += `<button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    <?php endif; ?>
                    html += '</td>';
                    <?php endif; ?>
                    
                    html += '</tr>';
                });
                
                tbody.innerHTML = html;
            }
            
            async function editProduct(id) {
                showMessage('Función de edición en desarrollo', 'info');
            }
            
            async function deleteProduct(id) {
                if (!confirm('¿Estás seguro de que deseas eliminar este producto?')) return;
                
                try {
                    const result = await apiRequest(`/api/products.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    if (result.success) {
                        showMessage('Producto eliminado exitosamente', 'success');
                        loadProductsData();
                    }
                } catch (error) {
                    showError('Error al eliminar producto');
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
