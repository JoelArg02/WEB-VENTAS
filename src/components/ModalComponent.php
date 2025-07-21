<?php
class ModalComponent {
    private $permissions;
    
    public function __construct($permissions) {
        $this->permissions = $permissions;
    }
    
    public function render() {
        ob_start();
        ?>
        <script>
            // Funciones de utilidad para modales
            function openModal(content) {
                document.getElementById('modalContent').innerHTML = content;
                document.getElementById('modalOverlay').classList.remove('hidden');
            }

            function closeModal() {
                document.getElementById('modalOverlay').classList.add('hidden');
            }

            // Modal para crear usuario
            function openCreateUserModal() {
                <?php if (!$this->permissions['create_user']): ?>
                showError('No tienes permisos para crear usuarios');
                return;
                <?php endif; ?>
                
                const content = `
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4">Crear Usuario</h2>
                        <form onsubmit="createUser(event)">
                            <div class="space-y-4">
                                <input type="text" name="name" placeholder="Nombre" required class="w-full border rounded-lg p-2">
                                <input type="email" name="email" placeholder="Email" required class="w-full border rounded-lg p-2">
                                <input type="tel" name="phone" placeholder="Teléfono" class="w-full border rounded-lg p-2">
                                <input type="password" name="password" placeholder="Contraseña" required class="w-full border rounded-lg p-2">
                                <select name="role" required class="w-full border rounded-lg p-2">
                                    <option value="">Seleccionar rol</option>
                                    <option value="admin">Administrador</option>
                                    <option value="vendedor">Vendedor</option>
                                    <option value="bodega">Bodega</option>
                                </select>
                            </div>
                            <div class="flex justify-end space-x-2 mt-6">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border rounded-lg">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Crear</button>
                            </div>
                        </form>
                    </div>`;
                
                openModal(content);
            }
            
            // Modal para crear producto
            function openCreateProductModal() {
                <?php if (!$this->permissions['create_product']): ?>
                showError('No tienes permisos para crear productos');
                return;
                <?php endif; ?>
                
                const content = `
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4">Crear Producto</h2>
                        <form onsubmit="createProduct(event)">
                            <div class="space-y-4">
                                <input type="text" name="name" placeholder="Nombre" required class="w-full border rounded-lg p-2">
                                <input type="number" name="price" step="0.01" placeholder="Precio" required class="w-full border rounded-lg p-2">
                                <input type="number" name="stock" placeholder="Stock inicial" required class="w-full border rounded-lg p-2">
                                <select name="category_id" class="w-full border rounded-lg p-2">
                                    <option value="">Sin categoría</option>
                                    <!-- Se cargarán dinámicamente las categorías -->
                                </select>
                                <input type="date" name="expiry_date" placeholder="Fecha de vencimiento (opcional)" class="w-full border rounded-lg p-2">
                            </div>
                            <div class="flex justify-end space-x-2 mt-6">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border rounded-lg">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Crear</button>
                            </div>
                        </form>
                    </div>`;
                
                openModal(content);
            }
            
            // Modal para crear venta
            function openCreateSaleModal() {
                <?php if (!$this->permissions['create_sale']): ?>
                showError('No tienes permisos para crear ventas');
                return;
                <?php endif; ?>
                
                const content = `
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4">Nueva Venta</h2>
                        <form onsubmit="createSale(event)">
                            <div class="space-y-4">
                                <input type="text" name="client" placeholder="Nombre del cliente" required class="w-full border rounded-lg p-2">
                                <select name="product_id" required class="w-full border rounded-lg p-2" onchange="updateSalePrice()">
                                    <option value="">Seleccionar producto</option>
                                    <!-- Se cargarán dinámicamente los productos -->
                                </select>
                                <input type="number" name="quantity" placeholder="Cantidad" value="1" min="1" required class="w-full border rounded-lg p-2" onchange="updateSalePrice()">
                                <input type="number" name="price" step="0.01" placeholder="Precio unitario" required readonly class="w-full border rounded-lg p-2 bg-gray-100">
                                <div class="text-lg font-semibold">
                                    Total: <span id="saleTotal">$0</span>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2 mt-6">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border rounded-lg">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Registrar Venta</button>
                            </div>
                        </form>
                    </div>`;
                
                openModal(content);
                loadProductsForSale();
            }
            
            // Modal para crear categoría
            function openCreateCategoryModal() {
                <?php if (!$this->permissions['create_category']): ?>
                showError('No tienes permisos para crear categorías');
                return;
                <?php endif; ?>
                
                const content = `
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4">Crear Categoría</h2>
                        <form onsubmit="createCategory(event)">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Categoría</label>
                                    <input type="text" name="name" placeholder="Ej: Bebidas, Comida, etc." required class="w-full border rounded-lg p-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                    <select name="status" class="w-full border rounded-lg p-2">
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2 mt-6">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border rounded-lg">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Crear</button>
                            </div>
                        </form>
                    </div>`;
                
                openModal(content);
            }
            
            // Cargar productos para el modal de ventas
            async function loadProductsForSale() {
                // Función placeholder - implementar si es necesario
            }
            
            // Funciones para manejar formularios
            async function createUser(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const result = await apiRequest('/api/users.php', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (result.success) {
                        showMessage('Usuario creado exitosamente', 'success');
                        closeModal();
                        if (typeof loadUsersData === 'function') loadUsersData();
                    }
                } catch (error) {
                    showError('Error al crear usuario');
                }
            }
            
            async function createProduct(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const result = await apiRequest('/api/products.php', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (result.success) {
                        showMessage('Producto creado exitosamente', 'success');
                        closeModal();
                        if (typeof loadProductsData === 'function') loadProductsData();
                    }
                } catch (error) {
                    showError('Error al crear producto');
                }
            }
            
            async function createSale(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const result = await apiRequest('/api/sales.php', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (result.success) {
                        showMessage('Venta registrada exitosamente', 'success');
                        closeModal();
                        if (typeof loadSalesData === 'function') loadSalesData();
                        if (typeof loadDashboardData === 'function') loadDashboardData();
                    }
                } catch (error) {
                    showError('Error al registrar venta');
                }
            }
            
            async function createCategory(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const result = await apiRequest('/api/categories.php', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (result.success) {
                        showMessage('Categoría creada exitosamente', 'success');
                        closeModal();
                        if (typeof loadCategoriesData === 'function') loadCategoriesData();
                    }
                } catch (error) {
                    showError('Error al crear categoría');
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
