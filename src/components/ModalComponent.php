<?php
class ModalComponent
{
    private $permissions;

    public function __construct($permissions)
    {
        $this->permissions = $permissions;
    }

    public function render()
    {
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
                    <div class="p-6 w-full max-w-5xl mx-auto">
                        <h2 class="text-xl font-bold mb-4">Nueva Venta</h2>
                        <form onsubmit="createSale(event)">
                            <div class="space-y-6">
                            
                                <!-- Lista de productos -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold">Productos</h3>
                                        <button type="button" onclick="addProductLine()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">+ Agregar Producto</button>
                                    </div>
                                    <div id="productLines">
                                        <!-- Los productos se agregan aquí dinámicamente -->
                                    </div>
                                </div>
                                
                                <!-- Resumen totales -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex justify-between text-sm mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotalDisplay">$0</span>
                                    </div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span>IVA (15%):</span>
                                        <span id="ivaDisplay">$0</span>
                                    </div>
                                    <div class="flex justify-between font-bold text-lg border-t pt-2">
                                        <span>Total:</span>
                                        <span id="totalDisplay">$0</span>
                                    </div>
                                     <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dinero recibido (opcional)</label>
                                    <input type="number" name="money_received" step="0.01" min="0" class="w-full border rounded-lg p-3" onchange="calculateChange()" placeholder="Dejar vacío si es venta a crédito">
                                </div>
                                
                                <div id="changeSection" class="hidden bg-blue-50 p-4 rounded-lg">
                                    <div class="flex justify-between font-bold text-lg">
                                        <span>Vuelto:</span>
                                        <span id="changeAmount" class="text-blue-600">$0</span>
                                    </div>
                                </div>
                                
                                <div id="insufficientMoney" class="hidden bg-red-50 p-4 rounded-lg">
                                    <div class="text-red-600 font-semibold">
                                        ⚠️ Dinero insuficiente. Faltan: <span id="missingAmount">$0</span>
                                    </div>
                                </div>
                                </div>
                                
                               
                            </div>
                            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                                <button type="button" onclick="closeModal()" class="px-6 py-3 text-gray-600 border rounded-lg hover:bg-gray-50">Cancelar</button>
                                <button type="submit" id="submitSale" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">Registrar Venta</button>
                            </div>
                        </form>
                    </div>`;

                openModal(content);
                loadProductsForSale();
                addProductLine(); // Agregar la primera línea de producto
            }

            // Variable global para los productos seleccionados
            window.saleProducts = [];
            window.productCounter = 0;

            // Agregar línea de producto
            function addProductLine() {
                const productLines = document.getElementById('productLines');
                const lineId = ++window.productCounter;

                const productLine = document.createElement('div');
                productLine.className = 'border rounded-lg p-4 mb-4';
                productLine.id = `product-line-${lineId}`;

                productLine.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-1 flex items-center justify-center">
                            <img id="productImage-${lineId}" src="" alt="Imagen" class="w-20 h-20 object-cover border rounded-lg hidden">
                        </div>
                        
                        <div class="md:col-span-2 relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                            <input type="text" id="productSearch-${lineId}" placeholder="Buscar producto..." class="w-full border rounded-lg p-2" autocomplete="off">
                            <div id="productDropdown-${lineId}" class="hidden absolute z-50 w-full max-h-48 overflow-y-auto bg-white border rounded-lg shadow-lg mt-1"></div>
                            <input type="hidden" id="productId-${lineId}" name="products[${lineId}][product_id]">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                            <input type="number" id="quantity-${lineId}" name="products[${lineId}][quantity]" value="1" min="1" class="w-full border rounded-lg p-2" onchange="updateLineTotal(${lineId})">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                            <input type="number" id="price-${lineId}" name="products[${lineId}][price]" readonly class="w-full border rounded-lg p-2 bg-gray-100" placeholder="$0">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal</label>
                            <input type="text" id="lineSubtotal-${lineId}" readonly class="w-full border rounded-lg p-2 bg-gray-100" placeholder="$0">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="button" onclick="removeProductLine(${lineId})" class="w-full px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Eliminar</button>
                        </div>
                    </div>
                `;                productLines.appendChild(productLine);
                setupProductSearchForLine(lineId);
            }

            // Configurar búsqueda para una línea específica
            function setupProductSearchForLine(lineId) {
                const searchInput = document.getElementById(`productSearch-${lineId}`);
                const dropdown = document.getElementById(`productDropdown-${lineId}`);
                const hiddenInput = document.getElementById(`productId-${lineId}`);

                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    if (searchTerm.length < 1) {
                        dropdown.classList.add('hidden');
                        hiddenInput.value = '';
                        clearLineData(lineId);
                        return;
                    }

                    const filteredProducts = window.productsData.filter(product =>
                        product.name.toLowerCase().includes(searchTerm)
                    );

                    showProductDropdownForLine(filteredProducts, dropdown, lineId);
                });
            }

            function showProductDropdownForLine(products, dropdown, lineId) {
                if (products.length === 0) {
                    dropdown.innerHTML = '<div class="p-3 text-gray-500">No se encontraron productos</div>';
                } else {
                    dropdown.innerHTML = products.map(product => {
                        let imageHtml = '';
                        if (product.image && product.image.trim()) {
                            imageHtml = `<img src="${product.image}" class="w-10 h-10 rounded object-cover border mr-3" alt="${product.name}">`;
                        } else {
                            imageHtml = `<div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center border mr-3">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"></path>
                                            </svg>
                                         </div>`;
                        }
                        
                        return `
                            <div class="p-3 hover:bg-gray-100 cursor-pointer border-b flex items-center" onclick="selectProductForLine(${lineId}, ${product.id}, '${product.name}', ${product.price}, ${product.stock}, '${product.image || ''}')">
                                ${imageHtml}
                                <div class="flex-1">
                                    <div class="font-medium">${product.name}</div>
                                    <div class="text-sm text-gray-600">${formatCurrency(product.price)} - Stock: ${product.stock}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
                dropdown.classList.remove('hidden');
            }

            function selectProductForLine(lineId, id, name, price, stock, image) {
                const searchInput = document.getElementById(`productSearch-${lineId}`);
                const dropdown = document.getElementById(`productDropdown-${lineId}`);
                const hiddenInput = document.getElementById(`productId-${lineId}`);
                const productImage = document.getElementById(`productImage-${lineId}`);

                searchInput.value = name;
                hiddenInput.value = id;
                dropdown.classList.add('hidden');

                // Mostrar imagen del producto
                if (productImage) {
                    if (image && image.trim() && image !== 'undefined') {
                        productImage.src = image;
                        productImage.classList.remove('hidden');
                    } else {
                        // Mostrar imagen por defecto si no hay imagen
                        productImage.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iOTYiIGhlaWdodD0iOTYiIHZpZXdCb3g9IjAgMCA5NiA5NiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9Ijk2IiBoZWlnaHQ9Ijk2IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0zNiAzNkg2MFY2MEgzNlYzNloiIHN0cm9rZT0iIzlDQTNBRiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+';
                        productImage.classList.remove('hidden');
                    }
                }

                // Actualizar datos de la línea
                window.saleProducts[lineId] = {
                    id,
                    name,
                    price,
                    stock,
                    image: image || ''
                };
                updateLineTotal(lineId);
            }

            function clearLineData(lineId) {
                document.getElementById(`price-${lineId}`).value = '';
                document.getElementById(`lineSubtotal-${lineId}`).value = '';
                
                // Ocultar imagen del producto
                const productImage = document.getElementById(`productImage-${lineId}`);
                if (productImage) {
                    productImage.classList.add('hidden');
                    productImage.src = '';
                }
                
                delete window.saleProducts[lineId];
                updateSaleTotals();
            }

            function updateLineTotal(lineId) {
                const product = window.saleProducts[lineId];
                if (!product) return;

                const quantity = parseInt(document.getElementById(`quantity-${lineId}`).value) || 0;
                const price = product.price;

                // Actualizar campos
                document.getElementById(`price-${lineId}`).value = price.toFixed(2);

                // Verificar stock
                if (quantity > product.stock) {
                    document.getElementById(`quantity-${lineId}`).value = product.stock;
                    showError(`Solo hay ${product.stock} unidades disponibles de ${product.name}`);
                }

                const finalQuantity = Math.min(quantity, product.stock);
                const lineSubtotal = price * finalQuantity;

                document.getElementById(`lineSubtotal-${lineId}`).value = formatCurrency(lineSubtotal);

                updateSaleTotals();
            }

            function removeProductLine(lineId) {
                const line = document.getElementById(`product-line-${lineId}`);
                if (line) {
                    line.remove();
                    delete window.saleProducts[lineId];
                    updateSaleTotals();
                }
            }

            function updateSaleTotals() {
                let totalSubtotal = 0;

                Object.keys(window.saleProducts).forEach(lineId => {
                    const product = window.saleProducts[lineId];
                    const quantityInput = document.getElementById(`quantity-${lineId}`);
                    if (quantityInput && product) {
                        const quantity = parseInt(quantityInput.value) || 0;
                        totalSubtotal += product.price * quantity;
                    }
                });

                const ivaAmount = totalSubtotal * 0.15;
                const total = totalSubtotal + ivaAmount;

                // Actualizar displays
                document.getElementById('subtotalDisplay').textContent = formatCurrency(totalSubtotal);
                document.getElementById('ivaDisplay').textContent = formatCurrency(ivaAmount);
                document.getElementById('totalDisplay').textContent = formatCurrency(total);

                // Guardar el total para uso en cálculo de vuelto
                window.saleTotal = total;

                calculateChange();
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

            // Modal para editar producto
            async function openEditProductModal(id) {
                <?php if (!$this->permissions['edit_product']): ?>
                    showError('No tienes permisos para editar productos');
                    return;
                <?php endif; ?>

                try {
                    showMessage('Cargando producto...', 'info');

                    // Obtener datos del producto
                    const productResult = await apiRequest(`api/products.php?id=${id}`);
                    
                    if (!productResult.success) {
                        showError('Error al cargar el producto: ' + productResult.message);
                        return;
                    }
                    
                    const product = productResult.data;

                    // Cargar categorías
                    const categoriesResult = await apiRequest('api/categories.php?active_only=1');
                    let categoriesOptions = '<option value="">Seleccione una categoría</option>';
                    
                    if (categoriesResult.success && categoriesResult.data) {
                        categoriesResult.data.forEach(category => {
                            const selected = category.id == product.category_id ? 'selected' : '';
                            categoriesOptions += `<option value="${category.id}" ${selected}>${category.name}</option>`;
                        });
                    }

                    const content = `
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-3xl font-bold text-gray-800">Editar Producto</h2>
                                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                            </div>
                            
                            <form id="editProductForm" onsubmit="updateProduct(event, ${id})">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Nombre del Producto *</label>
                                        <input type="text" name="name" value="${product.name}" 
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                               placeholder="Ingrese el nombre del producto" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Precio *</label>
                                        <input type="number" name="price" step="0.01" min="0" value="${product.price}"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                               placeholder="0.00" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Stock *</label>
                                        <input type="number" name="stock" min="0" value="${product.stock}"
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
                                        <input type="date" name="expiration_date" value="${product.expiration_date || ''}"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <p class="text-sm text-gray-500 mt-1">Opcional - Para productos perecederos</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Imagen del Producto</label>
                                        <div class="flex items-center space-x-6">
                                            <div class="flex-shrink-0">
                                                <img id="editImagePreview" src="${product.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik00NS4wMDAzIDQ1SDc1LjAwMDNWNzVINDUuMDAwM1Y0NVoiIHN0cm9rZT0iIzlDQTNBRiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPHA+CjwvcGF0aD4KPC9zdmc+'}" 
                                                     class="h-28 w-28 rounded-lg border-2 border-gray-300 object-cover" alt="Vista previa">
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" id="editImageInput" name="image" accept="image/*" 
                                                       class="hidden" onchange="handleEditImageUpload(event)">
                                                <label for="editImageInput" 
                                                       class="cursor-pointer inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                    Cambiar Imagen
                                                </label>
                                                <button type="button" onclick="clearEditImage()" 
                                                        class="ml-3 px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                                    Limpiar
                                                </button>
                                                <p class="text-sm text-gray-500 mt-2">Formatos: JPG, PNG, GIF. Máximo 2MB.</p>
                                            </div>
                                        </div>
                                        <input type="hidden" id="editImageBase64" name="imageBase64" value="${product.image || ''}">
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-6 mt-8">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Información Adicional</h3>
                                    <p class="text-gray-600">Los cambios se aplicarán inmediatamente al producto en el inventario.</p>
                                </div>
                                
                                <div class="flex justify-end space-x-4 mt-10 pt-6 border-t">
                                    <button type="button" onclick="closeModal()" 
                                            class="px-8 py-4 text-lg text-gray-600 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-semibold">
                                        Cancelar
                                    </button>
                                    <button type="submit" 
                                            class="px-8 py-4 text-lg bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-lg">
                                        Actualizar Producto
                                    </button>
                                </div>
                            </form>
                        </div>`;

                    openModal(content);
                } catch (error) {
                    console.error('Error loading product for edit:', error);
                    showError('Error al cargar el producto para editar');
                }
            }

            // Cargar productos para el modal de ventas
            async function loadProductsForSale() {
                try {
                    const result = await apiRequest('api/products.php');

                    if (result.success) {
                        window.productsData = result.data.filter(product => product.stock > 0);
                        setupProductSearch();
                    } else {
                        showError('Error al cargar productos: ' + result.message);
                    }
                } catch (error) {
                    showError('Error al cargar productos');
                    console.error('Error loading products for sale:', error);
                }
            }

            // Configurar búsqueda de productos
            function setupProductSearch() {
                const searchInput = document.getElementById('productSearch');
                const dropdown = document.getElementById('productDropdown');
                const hiddenInput = document.querySelector('input[name="product_id"]');

                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    if (searchTerm.length < 1) {
                        dropdown.classList.add('hidden');
                        hiddenInput.value = '';
                        clearProductData();
                        return;
                    }

                    const filteredProducts = window.productsData.filter(product =>
                        product.name.toLowerCase().includes(searchTerm)
                    );

                    showProductDropdown(filteredProducts, dropdown, hiddenInput, searchInput);
                });

                // Ocultar dropdown cuando se hace clic fuera
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('#productSearch') && !e.target.closest('#productDropdown')) {
                        dropdown.classList.add('hidden');
                    }
                });
            }

            function showProductDropdown(products, dropdown, hiddenInput, searchInput) {
                if (products.length === 0) {
                    dropdown.innerHTML = '<div class="p-3 text-gray-500">No se encontraron productos</div>';
                } else {
                    dropdown.innerHTML = products.map(product => `
                        <div class="p-3 hover:bg-gray-100 cursor-pointer border-b" onclick="selectProduct(${product.id}, '${product.name}', ${product.price}, ${product.stock})">
                            <div class="font-medium">${product.name}</div>
                            <div class="text-sm text-gray-600">${formatCurrency(product.price)} - Stock: ${product.stock}</div>
                        </div>
                    `).join('');
                }
                dropdown.classList.remove('hidden');
            }

            function selectProduct(id, name, price, stock) {
                const searchInput = document.getElementById('productSearch');
                const dropdown = document.getElementById('productDropdown');
                const hiddenInput = document.querySelector('input[name="product_id"]');

                searchInput.value = name;
                hiddenInput.value = id;
                dropdown.classList.add('hidden');

                // Actualizar campos del producto seleccionado
                window.selectedProduct = {
                    id,
                    name,
                    price,
                    stock
                };
                updateSalePrice();
            }

            function clearProductData() {
                document.querySelector('input[name="price"]').value = '';
                document.getElementById('availableStock').value = '';
                document.getElementById('subtotal').value = '';
                clearTotals();
                calculateChange();
            }

            // Actualizar precio de la venta
            function updateSalePrice() {
                const quantityInput = document.querySelector('input[name="quantity"]');
                const priceInput = document.querySelector('input[name="price"]');
                const availableStockInput = document.getElementById('availableStock');
                const subtotalInput = document.getElementById('subtotal');

                if (window.selectedProduct) {
                    const unitPrice = window.selectedProduct.price;
                    const availableStock = window.selectedProduct.stock;
                    const quantity = parseInt(quantityInput.value) || 0;

                    // Actualizar campos
                    priceInput.value = unitPrice.toFixed(2);
                    availableStockInput.value = availableStock;

                    // Verificar si hay suficiente stock
                    if (quantity > availableStock) {
                        quantityInput.value = availableStock;
                        showError(`Solo hay ${availableStock} unidades disponibles`);
                    }

                    // Calcular subtotal
                    const finalQuantity = Math.min(quantity, availableStock);
                    const subtotal = unitPrice * finalQuantity;

                    subtotalInput.value = formatCurrency(subtotal);

                    // Calcular totales con IVA
                    calculateTotals(subtotal);
                } else {
                    clearProductData();
                }

                // Recalcular vuelto si ya hay dinero ingresado
                calculateChange();
            }

            // Calcular totales con IVA
            function calculateTotals(subtotal) {
                const ivaRate = 0.15; // 15% de IVA
                const ivaAmount = subtotal * ivaRate;
                const total = subtotal + ivaAmount;

                // Actualizar displays
                document.getElementById('subtotalDisplay').textContent = formatCurrency(subtotal);
                document.getElementById('ivaDisplay').textContent = formatCurrency(ivaAmount);
                document.getElementById('totalDisplay').textContent = formatCurrency(total);

                // Guardar el total para usar en cálculo de vuelto
                window.saleTotal = total;
            }

            // Limpiar totales
            function clearTotals() {
                document.getElementById('subtotalDisplay').textContent = '$0';
                document.getElementById('ivaDisplay').textContent = '$0';
                document.getElementById('totalDisplay').textContent = '$0';
                window.saleTotal = 0;
            }

            // Calcular vuelto
            function calculateChange() {
                const moneyReceivedInput = document.querySelector('input[name="money_received"]');
                const changeSection = document.getElementById('changeSection');
                const insufficientMoneySection = document.getElementById('insufficientMoney');
                const changeAmountSpan = document.getElementById('changeAmount');
                const missingAmountSpan = document.getElementById('missingAmount');
                const submitButton = document.getElementById('submitSale');

                const moneyReceived = parseFloat(moneyReceivedInput.value) || 0;
                const total = window.saleTotal || 0;

                // Si no hay dinero ingresado, es venta a crédito
                if (moneyReceived === 0) {
                    changeSection.classList.add('hidden');
                    insufficientMoneySection.classList.add('hidden');
                    submitButton.disabled = false; // Permitir venta a crédito
                    return;
                }

                if (total === 0) {
                    changeSection.classList.add('hidden');
                    insufficientMoneySection.classList.add('hidden');
                    submitButton.disabled = true;
                    return;
                }

                if (moneyReceived >= total) {
                    // Dinero suficiente - mostrar vuelto
                    const change = moneyReceived - total;
                    changeAmountSpan.textContent = formatCurrency(change);
                    changeSection.classList.remove('hidden');
                    insufficientMoneySection.classList.add('hidden');
                    submitButton.disabled = false;
                } else {
                    // Dinero insuficiente - mostrar faltante
                    const missing = total - moneyReceived;
                    missingAmountSpan.textContent = formatCurrency(missing);
                    insufficientMoneySection.classList.remove('hidden');
                    changeSection.classList.add('hidden');
                    submitButton.disabled = true;
                }
            }

            // Funciones para manejar formularios
            async function createUser(event) {
                event.preventDefault();

                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());

                try {
                    const result = await apiRequest('api/users.php', {
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

                console.log('Creating sale...');

                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());

                console.log('Form data:', data);

                // Validar que hay al menos un producto
                const validProducts = Object.keys(window.saleProducts).filter(lineId => {
                    const product = window.saleProducts[lineId];
                    const quantityInput = document.getElementById(`quantity-${lineId}`);
                    return product && quantityInput && parseInt(quantityInput.value) > 0;
                });

                if (validProducts.length === 0) {
                    showError('Por favor agregue al menos un producto a la venta');
                    return;
                }

                // Validar stock para todos los productos
                for (const lineId of validProducts) {
                    const product = window.saleProducts[lineId];
                    const quantity = parseInt(document.getElementById(`quantity-${lineId}`).value);

                    if (quantity > product.stock) {
                        showError(`Stock insuficiente para ${product.name}. Solo hay ${product.stock} unidades disponibles.`);
                        return;
                    }
                }

                // Validar dinero si se ingresó
                const moneyReceived = parseFloat(data.money_received) || 0;
                const total = window.saleTotal;

                console.log('Money received:', moneyReceived, 'Total:', total);

                if (moneyReceived > 0 && moneyReceived < total) {
                    showError('El dinero recibido es insuficiente para completar la venta.');
                    return;
                }

                // Calcular totales
                let totalSubtotal = 0;
                const products = [];

                validProducts.forEach(lineId => {
                    const product = window.saleProducts[lineId];
                    const quantity = parseInt(document.getElementById(`quantity-${lineId}`).value);
                    const lineSubtotal = product.price * quantity;
                    totalSubtotal += lineSubtotal;

                    products.push({
                        product_id: product.id,
                        quantity: quantity,
                        price: product.price,
                        subtotal: lineSubtotal
                    });
                });

                const iva = totalSubtotal * 0.15;
                const finalTotal = totalSubtotal + iva;
                const change = moneyReceived > 0 ? moneyReceived - finalTotal : 0;

                // Preparar datos para enviar
                const saleData = {
                    client: 'SIN CLIENTE',
                    products: products,
                    subtotal: totalSubtotal,
                    iva_amount: iva,
                    total_amount: finalTotal,
                    money_received: moneyReceived,
                    change_amount: change
                };

                console.log('Sale data to send:', saleData);

                try {
                    const submitButton = document.getElementById('submitSale');
                    submitButton.disabled = true;
                    submitButton.textContent = 'Procesando...';

                    console.log('Making API request...');
                    const result = await apiRequest('api/sales.php', {
                        method: 'POST',
                        body: JSON.stringify(saleData)
                    });

                    console.log('API response:', result);

                    if (result.success) {
                        const message = moneyReceived > 0 ?
                            `Venta registrada exitosamente. Vuelto: ${formatCurrency(change)}` :
                            'Venta a crédito registrada exitosamente';
                        showMessage(message, 'success');
                        closeModal();
                        if (typeof loadSalesData === 'function') loadSalesData();
                        if (typeof loadDashboardData === 'function') loadDashboardData();
                    } else {
                        showError('Error al registrar venta: ' + (result.message || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Sale creation error:', error);
                    showError('Error al registrar venta: ' + error.message);
                } finally {
                    const submitButton = document.getElementById('submitSale');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Registrar Venta';
                    }
                }
            }

            async function createCategory(event) {
                event.preventDefault();

                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());

                try {
                    const result = await apiRequest('api/categories.php', {
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

            // Función para actualizar producto
            async function updateProduct(event, id) {
                event.preventDefault();
                console.log('Updating product...');

                const form = event.target;
                const formData = new FormData(form);

                const productData = {
                    name: formData.get('name').trim(),
                    price: parseFloat(formData.get('price')),
                    stock: parseInt(formData.get('stock')),
                    category_id: formData.get('category_id') || null,
                    expiration_date: formData.get('expiration_date') || null,
                    image: document.getElementById('editImageBase64')?.value || null,
                    status: 1 // Mantener activo
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
                submitBtn.textContent = 'Actualizando...';

                try {
                    console.log('Making API request to update product...');

                    const response = await fetch(`api/products.php?id=${id}`, {
                        method: 'PUT',
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
                        showMessage(result.message || 'Producto actualizado exitosamente', 'success');
                        closeModal();
                        // Recargar productos si estamos en esa pestaña
                        if (typeof loadProductsData === 'function') {
                            loadProductsData();
                        }
                    } else {
                        const errorMessage = result.message || 'Error desconocido al actualizar producto';
                        console.error('API Error:', errorMessage);
                        showMessage(errorMessage, 'error');
                    }
                } catch (error) {
                    console.error('Error updating product:', {
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

            // Funciones para manejo de imágenes en edición
            function handleEditImageUpload(event) {
                const file = event.target.files[0];

                if (!file) {
                    return;
                }

                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showMessage('Por favor seleccione un archivo de imagen válido (JPG, PNG, GIF)', 'error');
                    clearEditImage();
                    return;
                }

                // Validar tamaño (máximo 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    showMessage('La imagen es demasiado grande. Máximo 2MB permitido.', 'error');
                    clearEditImage();
                    return;
                }

                console.log('Processing edit image:', {
                    name: file.name,
                    size: file.size,
                    type: file.type
                });

                // Leer archivo y convertir a base64
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64 = e.target.result;

                    // Mostrar preview
                    const preview = document.getElementById('editImagePreview');
                    if (preview) {
                        preview.src = base64;
                    }

                    // Guardar base64 en el campo oculto
                    const hiddenInput = document.getElementById('editImageBase64');
                    if (hiddenInput) {
                        hiddenInput.value = base64;
                    }

                    showMessage('Imagen actualizada correctamente', 'success');
                    console.log('Edit image converted to base64, length:', base64.length);
                };

                reader.onerror = function() {
                    showMessage('Error al procesar la imagen', 'error');
                    clearEditImage();
                };

                reader.readAsDataURL(file);
            }

            function clearEditImage() {
                // Limpiar input
                const input = document.getElementById('editImageInput');
                if (input) {
                    input.value = '';
                }

                // Restaurar preview por defecto
                const preview = document.getElementById('editImagePreview');
                if (preview) {
                    preview.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik00NS4wMDAzIDQ1SDc1LjAwMDNWNzVINDUuMDAwM1Y0NVoiIHN0cm9rZT0iIzlDQTNBRiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPHA+CjwvcGF0aD4KPC9zdmc+';
                }

                // Limpiar campo oculto
                const hiddenInput = document.getElementById('editImageBase64');
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