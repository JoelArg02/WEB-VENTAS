<?php
class UsersComponent {
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
        <div class="space-y-6" id="usersTab">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h2>
                <?php if ($this->permissions['create_user']): ?>
                <button onclick="openCreateUserModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    + Nuevo Usuario
                </button>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Usuarios</h3>
                <div id="usersLoading" class="flex justify-center items-center h-32">
                    <div class="text-gray-500">Cargando usuarios...</div>
                </div>
                <div class="overflow-x-auto hidden" id="usersTable">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Rol</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Estado</th>
                                <?php if ($this->permissions['edit_user'] || $this->permissions['delete_user']): ?>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="usersTableBody">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
            async function loadUsersData() {
                try {
                    const result = await apiRequest('/api/users.php');
                    
                    if (result.success) {
                        renderUsersTable(result.data);
                        document.getElementById('usersLoading').classList.add('hidden');
                        document.getElementById('usersTable').classList.remove('hidden');
                    } else {
                        showError('Error al cargar usuarios: ' + result.message);
                    }
                } catch (error) {
                    showError('Error de conexión al cargar usuarios');
                    console.error('Users error:', error);
                }
            }
            
            function renderUsersTable(users) {
                const tbody = document.getElementById('usersTableBody');
                let html = '';
                
                users.forEach(user => {
                    const statusClass = user.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const statusText = user.status ? 'Activo' : 'Inactivo';
                    
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">${user.name}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${user.email}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${user.role}</td>
                            <td class="px-4 py-2 text-sm"><span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span></td>`;
                            
                    <?php if ($this->permissions['edit_user'] || $this->permissions['delete_user']): ?>
                    html += '<td class="px-4 py-2 text-sm">';
                    <?php if ($this->permissions['edit_user']): ?>
                    html += `<button onclick="editUser(${user.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    <?php endif; ?>
                    <?php if ($this->permissions['delete_user']): ?>
                    html += `<button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    <?php endif; ?>
                    html += '</td>';
                    <?php endif; ?>
                    
                    html += '</tr>';
                });
                
                tbody.innerHTML = html;
            }
            
            async function editUser(id) {
                <?php if (!$this->permissions['edit_user']): ?>
                showError('No tienes permisos para editar usuarios');
                return;
                <?php endif; ?>
                
                try {
                    showMessage('Cargando datos del usuario...', 'info');
                    
                    // Obtener los datos del usuario
                    const result = await apiRequest(`/api/users.php?id=${id}`);
                    
                    if (!result.success || !result.data) {
                        throw new Error('No se pudieron cargar los datos del usuario');
                    }
                    
                    const user = result.data;
                    
                    // Convertir status numérico a string para el formulario
                    const statusValue = user.status == 1 || user.status === 'active' ? 'active' : 'inactive';
                    
                    const content = `
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-3xl font-bold text-gray-800">Editar Usuario</h2>
                                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                            </div>
                            <form id="editUserForm" onsubmit="updateUser(event, ${user.id})">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Nombre Completo *</label>
                                        <input type="text" id="editUserName" name="name" value="${user.name || ''}"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                               placeholder="Ingrese el nombre completo" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Email *</label>
                                        <input type="email" id="editUserEmail" name="email" value="${user.email || ''}"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                               placeholder="ejemplo@email.com" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Teléfono</label>
                                        <input type="tel" id="editUserPhone" name="phone" value="${user.phone || ''}"
                                               class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                               placeholder="+56 9 1234 5678">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Rol *</label>
                                        <select id="editUserRole" name="role" required
                                                class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                            <option value="">Seleccione un rol</option>
                                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrador</option>
                                            <option value="vendedor" ${user.role === 'vendedor' ? 'selected' : ''}>Vendedor</option>
                                            <option value="bodega" ${user.role === 'bodega' ? 'selected' : ''}>Bodega</option>
                                            <option value="cajero" ${user.role === 'cajero' ? 'selected' : ''}>Cajero</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Estado *</label>
                                        <select id="editUserStatus" name="status" required onchange="toggleReasonField()"
                                                class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                            <option value="active" ${statusValue === 'active' ? 'selected' : ''}>Activo</option>
                                            <option value="inactive" ${statusValue === 'inactive' ? 'selected' : ''}>Inactivo</option>
                                        </select>
                                    </div>
                                    
                                    <div class="md:col-span-2" id="reasonContainer" style="display: ${statusValue === 'inactive' ? 'block' : 'none'};">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Motivo *</label>
                                        <textarea id="editUserReason" name="reason" rows="3"
                                                  class="w-full p-4 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                                                  placeholder="Motivo por el cual se desactiva el usuario">${user.reason || ''}</textarea>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-4 mt-10">
                                    <button type="button" onclick="closeModal()" 
                                            class="px-8 py-4 text-lg font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="submit" 
                                            class="px-8 py-4 text-lg font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                        Actualizar Usuario
                                    </button>
                                </div>
                            </form>
                            
                            <script>
                                function toggleReasonField() {
                                    const statusSelect = document.getElementById('editUserStatus');
                                    const reasonContainer = document.getElementById('reasonContainer');
                                    const reasonField = document.getElementById('editUserReason');
                                    
                                    if (statusSelect.value === 'inactive') {
                                        reasonContainer.style.display = 'block';
                                        reasonField.required = true;
                                    } else {
                                        reasonContainer.style.display = 'none';
                                        reasonField.required = false;
                                        reasonField.value = '';
                                    }
                                }
                            </script>
                        </div>
                    `;
                    
                    openModal(content);
                    
                } catch (error) {
                    showError('Error al cargar los datos del usuario: ' + error.message);
                }
            }
            
            async function updateUser(event, id) {
                event.preventDefault();
                
                const submitBtn = event.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Actualizando...';
                
                try {
                    const formData = new FormData(event.target);
                    const data = Object.fromEntries(formData.entries());
                    data.id = id; // Agregar el ID del usuario
                    
                    // Validar que el motivo sea requerido solo si el estado es inactivo
                    if (data.status === 'inactive' && (!data.reason || data.reason.trim() === '')) {
                        throw new Error('El motivo es requerido cuando se desactiva un usuario');
                    }
                    
                    const result = await apiRequest('/api/users.php', {
                        method: 'PUT',
                        body: JSON.stringify(data)
                    });
                    
                    if (result.success) {
                        showMessage('Usuario actualizado exitosamente', 'success');
                        closeModal();
                        if (typeof loadUsersData === 'function') {
                            loadUsersData();
                        }
                    } else {
                        throw new Error(result.message || 'Error al actualizar usuario');
                    }
                } catch (error) {
                    let userFriendlyMessage = error.message;
                    
                    // Personalizar mensajes de error comunes
                    if (error.message.includes('Failed to fetch')) {
                        userFriendlyMessage = 'Error de conexión: No se pudo conectar con el servidor.';
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
            
            async function deleteUser(id) {
                if (!confirm('¿Estás seguro de que deseas eliminar este usuario?')) return;
                
                try {
                    const result = await apiRequest(`/api/users.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    if (result.success) {
                        showMessage('Usuario eliminado exitosamente', 'success');
                        loadUsersData();
                    }
                } catch (error) {
                    showError('Error al eliminar usuario');
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
