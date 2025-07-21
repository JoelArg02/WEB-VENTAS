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
            async function openCreateProductModal() {
                <?php if (!$this->permissions['create_product']): ?>
                showError('No tienes permisos para crear productos');
                return;
                <?php endif; ?>
                
                try {
                    showMessage('Cargando formulario...', 'info');
                    
                    // Cargar categorías antes de mostrar el modal
                    const categoriesResult = await apiRequest('api/categories.php?active_only=1');
                    
                    let categoriesOptions = '<option value="">Seleccione una categoría</option>';
                    
                    if (categoriesResult.success && categoriesResult.data && categoriesResult.data.length > 0) {
                        categoriesResult.data.forEach(category => {
                            categoriesOptions += `<option value="${category.id}">${category.name}</option>`;
                        });
                    } else {
                        categoriesOptions += '<option value="" disabled>No hay categorías disponibles</option>';
                        showMessage('No se encontraron categorías activas. Debe crear al menos una categoría primero.', 'warning');
                    }
                    
                    const content = `
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-3xl font-bold text-gray-800">Crear Nuevo Producto</h2>
                                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                            </div>
                            
                            <form id="createProductForm" onsubmit="createProduct(event)">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Nombre del Producto *</label>
                                        <input type="text" name="name" 
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                               placeholder="Ingrese el nombre del producto" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Precio *</label>
                                        <input type="number" name="price" step="0.01" min="0"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                               placeholder="0.00" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Stock Inicial *</label>
                                        <input type="number" name="stock" min="0"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                               placeholder="0" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Categoría *</label>
                                        <select name="category_id" 
                                                class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                                            ${categoriesOptions}
                                        </select>
                                        <p class="text-sm text-gray-500 mt-1">Seleccione una categoría para el producto</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Fecha de Vencimiento</label>
                                        <input type="date" name="expiry_date"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <p class="text-sm text-gray-500 mt-1">Opcional - Para productos perecederos</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Imagen del Producto</label>
                                        <div class="flex items-center space-x-6">
                                            <div class="flex-shrink-0">
                                                <img id="imagePreview" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik00NS4wMDAzIDQ1SDc1LjAwMDNWNzVINDUuMDAwM1Y0NVoiIHN0cm9rZT0iIzlDQTNBRiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPHA+CjwvcGF0aD4KPC9zdmc+" 
                                                     class="h-28 w-28 rounded-lg border-2 border-gray-300 object-cover" alt="Vista previa">
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" id="imageInput" name="image" accept="image/*" 
                                                       class="hidden" onchange="handleImageUpload(event)">
                                                <label for="imageInput" 
                                                       class="cursor-pointer inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                    Seleccionar Imagen
                                                </label>
                                                <button type="button" onclick="clearImage()" 
                                                        class="ml-3 px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                                    Limpiar
                                                </button>
                                                <p class="text-sm text-gray-500 mt-2">Formatos: JPG, PNG, GIF. Máximo 2MB.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-6 mt-8">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Información Adicional</h3>
                                    <p class="text-gray-600">El producto será agregado al inventario y estará disponible inmediatamente para la venta. La imagen se guardará de forma segura en la base de datos.</p>
                                </div>
                                
                                <div class="flex justify-end space-x-4 mt-10 pt-6 border-t">
                                    <button type="button" onclick="closeModal()" 
                                            class="px-8 py-4 text-lg text-gray-600 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-semibold">
                                        Cancelar
                                    </button>
                                    <button type="submit" 
                                            class="px-8 py-4 text-lg bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold shadow-lg">
                                        Crear Producto
                                    </button>
                                </div>
                            </form>
                        </div>`;
                    
                    openModal(content);
                } catch (error) {
                    console.error('Error loading categories:', error);
                    showError('Error al cargar las categorías. Intente nuevamente.');
                }
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
                console.log('Creating product...');
                
                const form = event.target;
                const formData = new FormData(form);
                
                const productData = {
                    name: formData.get('name').trim(),
                    price: parseFloat(formData.get('price')),
                    stock: parseInt(formData.get('stock')),
                    category_id: formData.get('category_id') || null,
                    expiry_date: formData.get('expiry_date') || null,
                    image: document.getElementById('imageBase64')?.value || null
                };
                
                console.log('Product data to send:', productData);
                
                // Validaciones del frontend
                if (!productData.name || productData.name.length < 2) {
                    showMessage('El nombre del producto debe tener al menos 2 caracteres', 'error');
                    return;
                }
                
                if (isNaN(productData.price) || productData.price <= 0) {
                    showMessage('El precio debe ser un número mayor a 0', 'error');
                    return;
                }
                
                if (isNaN(productData.stock) || productData.stock < 0) {
                    showMessage('El stock debe ser un número mayor o igual a 0', 'error');
                    return;
                }
                
                if (!productData.category_id) {
                    showMessage('Por favor seleccione una categoría', 'error');
                    return;
                }
                
                // Deshabilitar el botón de envío
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creando...';
                
                try {
                    console.log('Making API request to create product...');
                    
                    const response = await fetch('api/products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(productData)
                    });
                    
                    console.log('Response status:', response.status);
                    
                    const responseText = await response.text();
                    console.log('Raw response text:', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                        console.log('Parsed response:', result);
                    } catch (parseError) {
                        console.error('JSON Parse Error:', parseError);
                        console.error('Response was:', responseText);
                        throw new Error('Error del servidor: La respuesta no es válida. Revise los logs de PHP para más detalles.');
                    }
                    
                    if (result.success) {
                        showMessage(result.message || 'Producto creado exitosamente', 'success');
                        closeModal();
                        // Recargar productos si estamos en esa pestaña
                        if (currentTab === 'products' && typeof loadProductsData === 'function') {
                            loadProductsData();
                        }
                    } else {
                        const errorMessage = result.message || 'Error desconocido al crear producto';
                        console.error('API Error:', errorMessage);
                        showMessage(errorMessage, 'error');
                    }
                } catch (error) {
                    console.error('Error creating product:', {
                        message: error.message,
                        stack: error.stack,
                        productData: productData
                    });
                    
                    let userFriendlyMessage = error.message;
                    
                    // Personalizar mensajes de error comunes
                    if (error.message.includes('Failed to fetch')) {
                        userFriendlyMessage = 'Error de conexión: No se pudo conectar con el servidor. Verifique su conexión a internet.';
                    } else if (error.message.includes('NetworkError')) {
                        userFriendlyMessage = 'Error de red: Problema de conectividad. Intente nuevamente.';
                    }
                    
                    showMessage(userFriendlyMessage, 'error');
                } finally {
                    // Rehabilitar el botón
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
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
            
            // Funciones para manejo de imágenes
            function handleImageUpload(event) {
                const file = event.target.files[0];
                
                if (!file) {
                    return;
                }
                
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showMessage('Por favor seleccione un archivo de imagen válido (JPG, PNG, GIF)', 'error');
                    clearImage();
                    return;
                }
                
                // Validar tamaño (máximo 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    showMessage('La imagen es demasiado grande. Máximo 2MB permitido.', 'error');
                    clearImage();
                    return;
                }
                
                console.log('Processing image:', {
                    name: file.name,
                    size: file.size,
                    type: file.type
                });
                
                // Leer archivo y convertir a base64
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64 = e.target.result;
                    
                    // Mostrar preview
                    const preview = document.getElementById('imagePreview');
                    if (preview) {
                        preview.src = base64;
                    }
                    
                    // Guardar base64 en un campo oculto para enviarlo
                    let hiddenInput = document.getElementById('imageBase64');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.id = 'imageBase64';
                        hiddenInput.name = 'imageBase64';
                        document.getElementById('createProductForm').appendChild(hiddenInput);
                    }
                    hiddenInput.value = base64;
                    
                    showMessage('Imagen cargada correctamente', 'success');
                    console.log('Image converted to base64, length:', base64.length);
                };
                
                reader.onerror = function() {
                    showMessage('Error al procesar la imagen', 'error');
                    clearImage();
                };
                
                reader.readAsDataURL(file);
            }
            
            function clearImage() {
                // Limpiar input
                const input = document.getElementById('imageInput');
                if (input) {
                    input.value = '';
                }
                
                // Restaurar preview por defecto
                const preview = document.getElementById('imagePreview');
                if (preview) {
                    preview.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik00NS4wMDAzIDQ1SDc1LjAwMDNWNzVINDUuMDAwM1Y0NVoiIHN0cm9rZT0iIzlDQTNBRiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPHA+CjwvcGF0aD4KPC9zdmc+';
                }
                
                // Limpiar campo oculto
                const hiddenInput = document.getElementById('imageBase64');
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
