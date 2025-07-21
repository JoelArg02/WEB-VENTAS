<?php
class CategoriesComponent {
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
        <div class="space-y-6" id="categoriesTab">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Categorías</h2>
                <?php if ($this->permissions['create_category']): ?>
                <button onclick="openCreateCategoryModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    + Nueva Categoría
                </button>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Categorías</h3>
                <div id="categoriesLoading" class="flex justify-center items-center h-32">
                    <div class="text-gray-500">Cargando categorías...</div>
                </div>
                <div class="overflow-x-auto hidden" id="categoriesTable">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Estado</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Productos</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Fecha Creación</th>
                                <?php if ($this->permissions['edit_category'] || $this->permissions['delete_category']): ?>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="categoriesTableBody">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
            async function loadCategoriesData() {
                try {
                    const result = await apiRequest('/api/categories.php');
                    
                    if (result.success) {
                        renderCategoriesTable(result.data);
                        document.getElementById('categoriesLoading').classList.add('hidden');
                        document.getElementById('categoriesTable').classList.remove('hidden');
                    } else {
                        showError('Error al cargar categorías: ' + result.message);
                    }
                } catch (error) {
                    showError('Error de conexión al cargar categorías');
                    console.error('Categories error:', error);
                }
            }
            
            function renderCategoriesTable(categories) {
                const tbody = document.getElementById('categoriesTableBody');
                let html = '';
                
                categories.forEach(category => {
                    const statusClass = category.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const statusText = category.status ? 'Activo' : 'Inactivo';
                    
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">${category.name}</td>
                            <td class="px-4 py-2 text-sm"><span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span></td>
                            <td class="px-4 py-2 text-sm text-gray-900">${category.product_count || 0}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${formatDate(category.created_at)}</td>`;
                            
                    <?php if ($this->permissions['edit_category'] || $this->permissions['delete_category']): ?>
                    html += '<td class="px-4 py-2 text-sm">';
                    <?php if ($this->permissions['edit_category']): ?>
                    html += `<button onclick="editCategory(${category.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    <?php endif; ?>
                    <?php if ($this->permissions['delete_category']): ?>
                    html += `<button onclick="deleteCategory(${category.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    <?php endif; ?>
                    html += '</td>';
                    <?php endif; ?>
                    
                    html += '</tr>';
                });
                
                tbody.innerHTML = html;
            }
            
            async function editCategory(id) {
                try {
                    // Primero obtenemos los datos de la categoría
                    const result = await apiRequest(`/api/categories.php?id=${id}`);
                    
                    if (result.success && result.data) {
                        const category = result.data;
                        
                        const content = `
                            <div class="p-6">
                                <h2 class="text-xl font-bold mb-4">Editar Categoría</h2>
                                <form onsubmit="updateCategory(event, ${id})">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Categoría</label>
                                            <input type="text" name="name" value="${category.name}" required class="w-full border rounded-lg p-2">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                            <select name="status" class="w-full border rounded-lg p-2">
                                                <option value="1" ${category.status == 1 ? 'selected' : ''}>Activo</option>
                                                <option value="0" ${category.status == 0 ? 'selected' : ''}>Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex justify-end space-x-2 mt-6">
                                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border rounded-lg">Cancelar</button>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Actualizar</button>
                                    </div>
                                </form>
                            </div>`;
                        
                        openModal(content);
                    }
                } catch (error) {
                    showError('Error al cargar datos de la categoría');
                }
            }
            
            async function updateCategory(event, id) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const result = await apiRequest(`/api/categories.php?id=${id}`, {
                        method: 'PUT',
                        body: JSON.stringify(data)
                    });
                    
                    if (result.success) {
                        showMessage('Categoría actualizada exitosamente', 'success');
                        closeModal();
                        loadCategoriesData();
                    }
                } catch (error) {
                    showError('Error al actualizar categoría');
                }
            }
            
            async function deleteCategory(id) {
                if (!confirm('¿Estás seguro de que deseas eliminar esta categoría?')) return;
                
                try {
                    const result = await apiRequest(`/api/categories.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    if (result.success) {
                        showMessage('Categoría eliminada exitosamente', 'success');
                        loadCategoriesData();
                    }
                } catch (error) {
                    showError('Error al eliminar categoría');
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
